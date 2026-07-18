<?php
// pages/verifikasi_pembayaran.php
session_start();
require_once '_template_sidebar.php'; 

if ($role !== 'admin') { header("Location: dashboard_admin.php"); exit(); }

$upload_dir = '../uploads/pembayaran/';
$msg = "";

// --- LOGIKA PROSES VERIFIKASI ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = sanitize_input($conn, $_GET['id']);
    $status = ($action == 'verify') ? 'VERIFIED' : 'REJECTED';
    $waktu = date('Y-m-d H:i:s');
    $admin_id = $_SESSION['user_id'];

    $q = $conn->prepare("UPDATE pembayaran SET status=?, admin_id=?, tanggal_verifikasi=? WHERE pembayaran_id=?");
    $q->bind_param("sisi", $status, $admin_id, $waktu, $id);
    
    if ($q->execute()) {
        echo "<script>alert('Pembayaran berhasil diproses ($status)!'); window.location='verifikasi_pembayaran.php';</script>";
    } else {
        $msg = "<div class='alert alert-danger'>Gagal memproses pembayaran.</div>";
    }
}

// --- LOGIKA AMBIL DATA ---
$sql = "SELECT p.*, t.nis, t.nama_lengkap, t.program_pendidikan 
        FROM pembayaran p 
        JOIN trainee t ON p.trainee_id = t.trainee_id
        ORDER BY p.status DESC, p.tanggal_bayar ASC";

$riwayat = $conn->query($sql);
$conn->close();
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-wallet-fill me-2 text-primary"></i> Verifikasi Pembayaran</h2>
<?php echo $msg; ?>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-header bg-white py-3 fw-bold">Daftar Transaksi (Pending & Riwayat)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Tgl Transaksi</th>
                        <th>NIS & Nama</th>
                        <th>Program</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
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
                            <td>
                                <span class="fw-bold"><?php echo $row['nama_lengkap']; ?></span><br>
                                <small class="text-muted"><?php echo $row['nis']; ?></small>
                            </td>
                            <td><?php echo $row['program_pendidikan']; ?></td>
                            <td><strong>Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></strong></td>
                            <td><span class="badge bg-<?php echo $badge; ?>"><?php echo $st; ?></span></td>
                            <td class="text-center text-nowrap">
                                <a href="<?php echo $upload_dir . $row['bukti_path']; ?>" target="_blank" class="btn btn-sm btn-outline-info me-1" title="Lihat Bukti"><i class="bi bi-eye"></i></a>
                                
                                <?php if ($st == 'PENDING'): ?>
                                    <a href="?action=verify&id=<?php echo $row['pembayaran_id']; ?>" class="btn btn-sm btn-success me-1" onclick="return confirm('Yakin verifikasi pembayaran ini?')">Verifikasi</a>
                                    <a href="?action=reject&id=<?php echo $row['pembayaran_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin menolak transaksi ini?')">Tolak</a>
                                <?php else: ?>
                                    <span class="small text-muted">Diproses oleh Admin <?php echo $row['admin_id']; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada transaksi yang perlu diverifikasi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>