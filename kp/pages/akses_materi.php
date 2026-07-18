<?php
// pages/akses_materi.php
session_start();
require_once '_template_sidebar.php'; 

if ($role !== 'trainee') { header("Location: ../index.php"); exit(); }

$upload_dir = '../uploads/materi/';

// 1. AMBIL PROGRAM SISWA (Ini adalah kunci filtering)
$q_siswa = $conn->query("SELECT program_pendidikan FROM trainee WHERE user_id='$user_id'");
$program_saya = $q_siswa->fetch_assoc()['program_pendidikan'] ?? 'Program Tidak Ditemukan';

// 2. AMBIL DAFTAR MATERI SESUAI PROGRAM SISWA
$filter_sql = "program_pendidikan = '$program_saya'";
$list_materi = $conn->query("SELECT m.*, u.username FROM materi m JOIN users u ON m.user_id = u.user_id WHERE $filter_sql ORDER BY tanggal_upload DESC");

$conn->close();
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-book-half me-2 text-success"></i> Akses Materi Pembelajaran</h2>
<div class="alert alert-info py-2">
    Anda terdaftar pada Program <strong><?php echo $program_saya; ?></strong>. Silakan unduh materi di bawah ini.
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-success text-white fw-bold py-3">
        Daftar Materi Program <?php echo $program_saya; ?>
    </div>
    
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Mata Kuliah & Deskripsi</th>
                        <th>Nama File</th>
                        <th>Tanggal Upload</th>
                        <th>Pengunggah</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($list_materi->num_rows > 0): while($row = $list_materi->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-3">
                            <span class="fw-bold text-primary"><?php echo $row['judul']; ?></span><br>
                            <small class="text-muted"><?php echo $row['deskripsi']; ?></small>
                        </td>
                        <td><?php echo $row['file_name']; ?></td>
                        <td><?php echo date('d M y', strtotime($row['tanggal_upload'])); ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td class="text-center">
                            <a href="<?php echo $upload_dir . $row['file_path']; ?>" target="_blank" class="btn btn-sm btn-primary" title="Download Materi">
                                <i class="bi bi-download me-1"></i> Download
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada materi diunggah untuk program Anda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>