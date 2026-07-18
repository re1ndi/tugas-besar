<?php
// pages/cek_nilai.php
session_start();
require_once '_template_sidebar.php';

if ($role !== 'trainee') { header("Location: ../index.php"); exit(); }

// 1. AMBIL TRAINEE ID
$q_siswa = $conn->query("SELECT trainee_id, nis, nama_lengkap, program_pendidikan FROM trainee WHERE user_id='$user_id'");
$data_siswa = $q_siswa->fetch_assoc();
$trainee_id = $data_siswa['trainee_id'] ?? 0;
$program_saya = $data_siswa['program_pendidikan'] ?? 'Program Tidak Ditemukan';

// 2. AMBIL SEMUA DATA NILAI UNTUK SISWA INI
$sql_nilai = "SELECT * FROM nilai WHERE trainee_id='$trainee_id' ORDER BY nilai_id DESC";
$result_nilai = $conn->query($sql_nilai);

$conn->close();
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i> Cek Hasil Nilai</h2>
<p class="lead">Berikut adalah riwayat nilai akademik Anda untuk Program <?php echo $program_saya; ?>.</p>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-header bg-white py-3 fw-bold border-bottom">
        Riwayat Nilai Lengkap (NIS: <?php echo $data_siswa['nis'] ?? '-'; ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-striped">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Mata Kuliah</th>
                        <th class="text-center">Nilai Akhir</th>
                        <th class="text-center">Grade</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_nilai->num_rows > 0): while($row = $result_nilai->fetch_assoc()): ?>
                        <?php 
                            $grade_class = ($row['grade'] == 'A') ? 'success' : (($row['grade'] == 'B') ? 'primary' : (($row['grade'] == 'C') ? 'info' : 'danger'));
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo $row['kode_pelatihan']; ?></td>
                            <td class="text-center fw-bold text-dark"><?php echo $row['nilai_akhir']; ?></td>
                            <td class="text-center"><span class="badge bg-<?php echo $grade_class; ?> fs-6"><?php echo $row['grade']; ?></span></td>
                            <td class="text-center"><span class="badge bg-<?php echo ($row['status_lulus'] == 'LULUS' ? 'success' : 'danger'); ?>"><?php echo $row['status_lulus']; ?></span></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada nilai tercatat untuk Anda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>