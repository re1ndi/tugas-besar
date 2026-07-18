<?php
// pages/status_biaya.php
session_start();
require_once '_template_sidebar.php'; 

if ($role !== 'trainee') { header("Location: ../index.php"); exit(); }

$upload_dir = '../uploads/pembayaran/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

// KONSTANTA BIAYA (Hardcode untuk Demo)
define("TOTAL_BIAYA_PROGRAM", 15000000.00);

// Ambil ID & Program Siswa
$q_siswa = $conn->query("SELECT trainee_id FROM trainee WHERE user_id='$user_id'");
$trainee_id = $q_siswa->fetch_assoc()['trainee_id'] ?? 0;
$program_saya = $conn->query("SELECT program_pendidikan FROM trainee WHERE user_id='$user_id'")->fetch_assoc()['program_pendidikan'] ?? 'N/A';

$msg = "";

// --- LOGIKA UPLOAD BUKTI ---
if (isset($_POST['upload_bukti']) && $trainee_id != 0) {
    $jumlah = floatval(sanitize_input($conn, $_POST['jumlah']));
    $tgl_bayar = sanitize_input($conn, $_POST['tanggal_bayar']);

    if (!empty($_FILES['bukti']['name'])) {
        $file_tmp = $_FILES['bukti']['tmp_name'];
        $original_name = basename($_FILES['bukti']['name']);
        $new_filename = time() . '_' . uniqid() . '_' . $original_name;
        $target_file = $upload_dir . $new_filename;

        if (move_uploaded_file($file_tmp, $target_file)) {
            $q = $conn->prepare("INSERT INTO pembayaran (trainee_id, tanggal_bayar, jumlah, bukti_path, status) VALUES (?, ?, ?, ?, 'PENDING')");
            $q->bind_param("isds", $trainee_id, $tgl_bayar, $jumlah, $new_filename);
            
            if ($q->execute()) {
                $msg = "<div class='alert alert-success'>Bukti pembayaran berhasil diunggah! Menunggu verifikasi Admin.</div>";
            } else {
                $msg = "<div class='alert alert-danger'>Gagal menyimpan data transaksi.</div>";
                unlink($target_file);
            }
        } else {
            $msg = "<div class='alert alert-danger'>Gagal mengunggah file bukti.</div>";
        }
    }
}

// --- LOGIKA CEK STATUS KEUANGAN ---
$q_bayar = $conn->query("SELECT SUM(jumlah) AS total FROM pembayaran WHERE trainee_id='$trainee_id' AND status='VERIFIED'");
$total_sudah_bayar = $q_bayar->fetch_assoc()['total'] ?? 0;
$sisa_tagihan = TOTAL_BIAYA_PROGRAM - $total_sudah_bayar;
$persentase = round(($total_sudah_bayar / TOTAL_BIAYA_PROGRAM) * 100, 1);

// Riwayat Transaksi
$riwayat = $conn->query("SELECT * FROM pembayaran WHERE trainee_id='$trainee_id' ORDER BY tanggal_bayar DESC");
$conn->close();
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-receipt-cutoff me-2 text-primary"></i> Status Administrasi Biaya</h2>

<?php echo $msg; ?>

<div class="row g-4 mb-5">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <p class="text-info fw-bold small mb-1">Total Biaya Program (<?php echo $program_saya; ?>)</p>
                <h4 class="fw-bold">Rp <?php echo number_format(TOTAL_BIAYA_PROGRAM, 0, ',', '.'); ?></h4>
                <div class="mt-3">
                    <p class="mb-1 small">Sisa Tagihan: <span class="fw-bold text-danger">Rp <?php echo number_format($sisa_tagihan, 0, ',', '.'); ?></span></p>
                    <div class="progress" role="progressbar" style="height: 15px;">
                        <div class="progress-bar bg-primary" style="width: <?php echo $persentase; ?>%"></div>
                    </div>
                    <p class="small text-muted mt-1"><?php echo $persentase; ?>% telah dibayarkan</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light fw-bold">Unggah Bukti Pembayaran</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tanggal Bayar</label>
                        <input type="date" name="tanggal_bayar" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jumlah Pembayaran</label>
                        <input type="number" name="jumlah" class="form-control" required placeholder="Rp">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Bukti Transfer/Setor</label>
                        <input type="file" name="bukti" class="form-control" required accept="image/*, application/pdf">
                    </div>
                    <button type="submit" name="upload_bukti" class="btn btn-success w-100 fw-bold">Kirim untuk Verifikasi</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-header bg-white fw-bold py-3">Riwayat Transaksi Anda</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Tanggal</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                        <th>Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($riwayat->num_rows > 0): while($row = $riwayat->fetch_assoc()): ?>
                        <?php 
                            $st = $row['status'];
                            $badge = ($st == 'VERIFIED') ? 'success' : (($st == 'REJECTED') ? 'danger' : 'warning');
                        ?>
                        <tr>
                            <td class="ps-4"><?php echo date('d M Y', strtotime($row['tanggal_bayar'])); ?></td>
                            <td>Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                            <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $st; ?></span></td>
                            <td><a href="<?php echo $upload_dir . $row['bukti_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-image"></i> Lihat</a></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada riwayat pembayaran.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>