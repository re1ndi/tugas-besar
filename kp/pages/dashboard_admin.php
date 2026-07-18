<?php
// pages/dashboard_admin.php
session_start();
require_once '_template_sidebar.php'; 

if ($role !== 'admin') { header("Location: ../index.php"); exit(); }

$total_siswa = $conn->query("SELECT COUNT(*) as total FROM trainee")->fetch_assoc()['total'] ?? 0;
$total_pengajar = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='pengajar'")->fetch_assoc()['total'] ?? 0;
$cek_absen = $conn->query("SHOW TABLES LIKE 'absensi'");
$total_hadir = ($cek_absen->num_rows > 0) ? ($conn->query("SELECT COUNT(*) as total FROM absensi WHERE tanggal = CURDATE() AND status_kehadiran='Hadir'")->fetch_assoc()['total'] ?? 0) : 0;
?>

<h2 class="fw-bold text-dark mt-4 mb-4"><i class="bi bi-grid-1x2-fill me-2 text-primary"></i> Dashboard Administrator</h2>

<div class="row g-4 mb-4">
    <div class="col-lg-4 col-md-6">
        <div class="card bg-primary text-white shadow-sm h-100 border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><div class="text-uppercase fw-bold small opacity-75">Total Siswa</div><div class="fs-1 fw-bold"><?php echo $total_siswa; ?></div></div>
                <i class="bi bi-people-fill fs-1 opacity-50"></i>
            </div>
            <div class="card-footer bg-primary-subtle border-0"><a href="tambah_trainee.php" class="text-primary fw-bold text-decoration-none small">Kelola Data Siswa <i class="bi bi-arrow-right ms-1"></i></a></div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="card bg-success text-white shadow-sm h-100 border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><div class="text-uppercase fw-bold small opacity-75">Hadir Hari Ini</div><div class="fs-1 fw-bold"><?php echo $total_hadir; ?></div></div>
                <i class="bi bi-calendar-check fs-1 opacity-50"></i>
            </div>
            <div class="card-footer bg-success-subtle border-0"><a href="absensi_admin.php" class="text-success fw-bold text-decoration-none small">Lihat Absensi <i class="bi bi-arrow-right ms-1"></i></a></div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="card bg-warning text-dark shadow-sm h-100 border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div><div class="text-uppercase fw-bold small opacity-75">Total Pengajar</div><div class="fs-1 fw-bold"><?php echo $total_pengajar; ?></div></div>
                <i class="bi bi-person-workspace fs-1 opacity-50"></i>
            </div>
            <div class="card-footer bg-warning-subtle border-0"><a href="kelola_pengajar.php" class="text-dark fw-bold text-decoration-none small">Kelola Pengajar <i class="bi bi-arrow-right ms-1"></i></a></div>
        </div>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>