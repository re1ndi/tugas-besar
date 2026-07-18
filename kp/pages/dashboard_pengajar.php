<?php
// pages/dashboard_pengajar.php
session_start();
require_once '_template_sidebar.php';

// CEK AKSES
if ($role !== 'pengajar') { header("Location: ../index.php"); exit(); }

// 1. CARI TAHU PENGAJAR INI MENGAJAR PROGRAM APA
$id_user_login = $_SESSION['user_id'];
$q_guru = $conn->query("SELECT program_ajar, nama_lengkap FROM pengajar WHERE user_id='$id_user_login'");

if ($q_guru->num_rows > 0) {
    $data_guru = $q_guru->fetch_assoc();
    $program_saya = $data_guru['program_ajar']; // Contoh: "Staff Airlines"
    $nama_saya = $data_guru['nama_lengkap'];
} else {
    // Jika data pengajar tidak ditemukan (error data)
    $program_saya = "Tidak Diketahui";
    $nama_saya = "Pengajar";
}

// 2. HITUNG STATISTIK (HANYA UNTUK PROGRAM SAYA)
// Total Siswa di program saya
$q_total = $conn->query("SELECT COUNT(*) as total FROM trainee WHERE program_pendidikan='$program_saya' AND status_pendaftaran='Aktif'");
$total_siswa = $q_total->fetch_assoc()['total'] ?? 0;

// Siswa yang sudah saya nilai (Cek di tabel nilai yang terhubung ke siswa program saya)
$q_nilai = $conn->query("SELECT COUNT(DISTINCT n.trainee_id) as total 
                         FROM nilai n 
                         JOIN trainee t ON n.trainee_id = t.trainee_id 
                         WHERE t.program_pendidikan='$program_saya'");
$sudah_dinilai = $q_nilai->fetch_assoc()['total'] ?? 0;

// 3. AMBIL DAFTAR SISWA (HANYA KELAS SAYA)
$list_siswa = $conn->query("SELECT * FROM trainee WHERE program_pendidikan='$program_saya' AND status_pendaftaran='Aktif' ORDER BY nama_lengkap ASC");
?>

<div class="d-flex justify-content-between align-items-center mt-4 mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1"><i class="bi bi-grid-1x2-fill me-2 text-primary"></i> Dashboard Pengajar</h2>
        <p class="text-muted mb-0">Selamat Datang, <strong><?php echo $nama_saya; ?></strong></p>
    </div>
    <div class="text-end">
        <span class="badge bg-primary fs-6 px-3 py-2">Program: <?php echo $program_saya; ?></span>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-primary text-white shadow-sm h-100 border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase fw-bold small opacity-75">Siswa <?php echo $program_saya; ?></div>
                        <div class="fs-1 fw-bold"><?php echo $total_siswa; ?></div>
                    </div>
                    <i class="bi bi-people-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-success text-white shadow-sm h-100 border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase fw-bold small opacity-75">Siswa Sudah Dinilai</div>
                        <div class="fs-1 fw-bold"><?php echo $sudah_dinilai; ?></div>
                    </div>
                    <i class="bi bi-check-circle-fill fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 fw-bold border-bottom">
        <i class="bi bi-list-ul me-2"></i> Daftar Siswa Kelas <?php echo $program_saya; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">NIS</th>
                        <th>Nama Lengkap</th>
                        <th>Angkatan</th>
                        <th>No. HP</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($list_siswa && $list_siswa->num_rows > 0): while($row = $list_siswa->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?php echo $row['nis']; ?></td>
                        <td><?php echo $row['nama_lengkap']; ?></td>
                        <td><?php echo $row['angkatan']; ?></td>
                        <td><?php echo $row['no_telp']; ?></td>
                        <td class="text-center"><span class="badge bg-success rounded-pill">Aktif</span></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-emoji-frown fs-1 d-block mb-2 opacity-50"></i>
                            Tidak ada siswa aktif di program <strong><?php echo $program_saya; ?></strong>.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>