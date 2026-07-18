<?php
// pages/absensi_trainee.php
session_start();
require_once '_template_sidebar.php';

if ($role !== 'trainee') { header("Location: ../index.php"); exit(); }

// 1. AMBIL TRAINEE ID
$q_siswa = $conn->query("SELECT trainee_id FROM trainee WHERE user_id='$user_id'");
$trainee_id = $q_siswa->fetch_assoc()['trainee_id'] ?? 0;

// 2. AMBIL RIWAYAT ABSENSI
$sql_absensi = "SELECT * FROM absensi WHERE trainee_id='$trainee_id' ORDER BY tanggal DESC";
$result_absensi = $conn->query($sql_absensi);

$conn->close();
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-calendar-event-fill me-2 text-primary"></i> Riwayat Kehadiran</h2>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 fw-bold">Daftar Kehadiran Anda</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tanggal</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_absensi->num_rows > 0): while($row = $result_absensi->fetch_assoc()): ?>
                        <?php 
                            $status = $row['status_kehadiran'];
                            $bg_color = ($status == 'Hadir') ? 'success' : (($status == 'Alpha') ? 'danger' : 'warning');
                        ?>
                        <tr>
                            <td class="ps-3 fw-bold"><?php echo date('d F Y', strtotime($row['tanggal'])); ?></td>
                            <td><span class="badge bg-<?php echo $bg_color; ?>"><?php echo $status; ?></span></td>
                            <td><?php echo $row['keterangan'] ?: '-'; ?></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada riwayat kehadiran tercatat.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>