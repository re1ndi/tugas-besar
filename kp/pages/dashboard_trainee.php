<?php
// pages/dashboard_trainee.php
session_start();
require_once '_template_sidebar.php';

if ($role !== 'trainee') { header("Location: ../index.php"); exit(); }

// 1. AMBIL DATA SISWA & PROGRAM (Kunci Filtering)
$q_siswa = $conn->query("SELECT trainee_id, nis, nama_lengkap, program_pendidikan, status_pendaftaran FROM trainee WHERE user_id='$user_id'");
$data_siswa = $q_siswa->fetch_assoc();
$program_saya = $data_siswa['program_pendidikan'] ?? 'Program Tidak Ditemukan';
$status_saya = $data_siswa['status_pendaftaran'] ?? 'Belum Aktif';

// 2. LOGIKA UNTUK TAMPILAN
$bg_status = ($status_saya == 'Aktif' || $status_saya == 'Lulus') ? 'success' : 'warning';
$status_icon = ($status_saya == 'Aktif' || $status_saya == 'Lulus') ? 'bi-patch-check' : 'bi-clock-fill';

// Tidak perlu query nilai di sini

$conn->close();
?>

<h2 class="fw-bold text-dark mt-4 mb-4">
    <i class="bi bi-grid-1x2-fill me-2 text-primary"></i> Selamat Datang, <?php echo $data_siswa['nama_lengkap'] ?? 'Siswa'; ?>
</h2>

<div class="alert alert-info py-2 small border-0 shadow-sm">
    <i class="bi bi-info-circle-fill me-2"></i> Gunakan Sidebar untuk mengakses Materi, Nilai, dan Riwayat Kehadiran.
</div>

<h3 class="fw-bold mb-4">Status Program Anda</h3>

<div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-info fw-bold small text-uppercase mb-1">Program Pendidikan</p>
                        <h4 class="fw-bold mb-0"><?php echo $program_saya; ?></h4>
                    </div>
                    <i class="bi bi-mortarboard-fill text-info opacity-50 fs-2"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-start border-4 border-<?php echo $bg_status; ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-<?php echo $bg_status; ?> fw-bold small text-uppercase mb-1">Status Keaktifan</p>
                        <h4 class="fw-bold mb-0"><?php echo $status_saya; ?></h4>
                    </div>
                    <i class="bi <?php echo $status_icon; ?> text-<?php echo $bg_status; ?> opacity-50 fs-2"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>