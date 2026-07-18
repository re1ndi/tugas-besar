<?php
// pages/unggah_bayar.php
session_start();
require_once '_template_sidebar.php'; 

if ($role !== 'trainee') { header("Location: ../index.php"); exit(); }

$upload_dir = '../uploads/pembayaran/';
// Pastikan folder upload ada dan bisa ditulis (ini sering jadi masalah)
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); } 

// Ambil ID Siswa
$q_siswa = $conn->query("SELECT trainee_id FROM trainee WHERE user_id='$user_id'");
$trainee_id = $q_siswa->fetch_assoc()['trainee_id'] ?? 0;
$msg = "";

// --- LOGIKA UPLOAD BUKTI ---
if (isset($_POST['upload_bukti'])) {
    
    // Check 1: Pastikan Trainee ID ditemukan
    if ($trainee_id == 0) {
        $msg = "<div class='alert alert-danger'><i class='bi bi-x-circle-fill me-2'></i>Error: Data Siswa tidak terdaftar dengan benar di sistem. Silakan hubungi Admin.</div>";
        goto skip_upload;
    }

    $jumlah = floatval(sanitize_input($conn, $_POST['jumlah']));
    $tgl_bayar = sanitize_input($conn, $_POST['tanggal_bayar']);

    // Check 2: Cek apakah ada pembayaran PENDING
    $cek_pending = $conn->query("SELECT * FROM pembayaran WHERE trainee_id='$trainee_id' AND status='PENDING'");
    if($cek_pending->num_rows > 0) {
        $msg = "<div class='alert alert-danger'><i class='bi bi-info-circle-fill me-2'></i>Anda memiliki transaksi yang masih **PENDING** (Menunggu verifikasi).</div>";
    } elseif ($jumlah <= 0) {
        $msg = "<div class='alert alert-danger'><i class='bi bi-x-circle-fill me-2'></i>Jumlah pembayaran tidak valid.</div>";
    } elseif (!empty($_FILES['bukti']['name'])) {
        
        $file_tmp = $_FILES['bukti']['tmp_name'];
        $original_name = basename($_FILES['bukti']['name']);
        $file_extension = pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION);
        
        // Buat nama file unik untuk keamanan
        $new_filename = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;

        if (move_uploaded_file($file_tmp, $target_file)) {
            // INSERT KE DATABASE
            $q = $conn->prepare("INSERT INTO pembayaran (trainee_id, tanggal_bayar, jumlah, bukti_path, status) VALUES (?, ?, ?, ?, 'PENDING')");
            $q->bind_param("isds", $trainee_id, $tgl_bayar, $jumlah, $new_filename);
            
            if ($q->execute()) {
                $msg = "<div class='alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>Bukti pembayaran berhasil diunggah! Menunggu verifikasi Admin.</div>";
            } else {
                $msg = "<div class='alert alert-danger'>Gagal menyimpan data transaksi ke DB. Error: " . $conn->error . "</div>";
                unlink($target_file); // Hapus file jika gagal insert ke DB
            }
        } else {
            $msg = "<div class='alert alert-danger'><i class='bi bi-x-circle-fill me-2'></i>Gagal mengunggah file. Cek izin folder 'uploads/pembayaran'.</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger'>Harap lampirkan file bukti pembayaran.</div>";
    }
}

skip_upload:

// Riwayat Transaksi
$riwayat = $conn->query("SELECT * FROM pembayaran WHERE trainee_id='$trainee_id' ORDER BY tanggal_bayar DESC");
$conn->close();
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-cloud-upload-fill me-2 text-primary"></i> Unggah Bukti Pembayaran</h2>

<?php echo $msg; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-light fw-bold"><i class="bi bi-upload me-2"></i> Kirim Bukti Pembayaran Baru</div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <p class="small text-muted">Pastikan jumlah dan tanggal pembayaran sesuai dengan bukti transfer Anda.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tanggal Bayar</label>
                        <input type="date" name="tanggal_bayar" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jumlah Pembayaran (Rp)</label>
                        <input type="number" name="jumlah" class="form-control" required placeholder="Contoh: 5000000" min="1000">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Bukti Transfer/Setor (JPG/PNG/PDF)</label>
                        <input type="file" name="bukti" class="form-control" required accept="image/*, application/pdf">
                    </div>
                    <button type="submit" name="upload_bukti" class="btn btn-success w-100 fw-bold"><i class="bi bi-check-circle-fill me-2"></i> Kirim untuk Verifikasi</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white fw-bold py-3"><i class="bi bi-list-columns-reverse me-2"></i> Riwayat Transaksi Anda</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Tgl</th>
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
                                    <td>
                                        <a href="<?php echo $upload_dir . $row['bukti_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-image"></i> Lihat</a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada riwayat pembayaran.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>