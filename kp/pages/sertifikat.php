<?php
// pages/sertifikat.php
session_start();
require_once '_template_sidebar.php';

if ($role !== 'trainee') { header("Location: ../index.php"); exit(); }

// 1. AMBIL TRAINEE ID
$q_siswa = $conn->query("SELECT trainee_id, nis, nama_lengkap, program_pendidikan FROM trainee WHERE user_id='$user_id'");
$data_siswa = $q_siswa->fetch_assoc();
$trainee_id = $data_siswa['trainee_id'] ?? 0;

// 2. CEK APAKAH SUDAH LULUS (Cek semua nilai)
// Dianggap lulus jika ada setidaknya satu record nilai yang statusnya LULUS
$q_lulus = $conn->query("SELECT * FROM nilai WHERE trainee_id='$trainee_id' AND status_lulus='LULUS' LIMIT 1");
$data_lulus = $q_lulus->fetch_assoc();

$conn->close();
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-patch-check-fill me-2 text-primary"></i> Download E-Certificate</h2>
<p class="lead mb-4">Halaman ini menampilkan sertifikat kelulusan setelah Anda berhasil menyelesaikan program.</p>

<?php if ($data_lulus): ?>
    <div class="card shadow-lg border-3 border-success mb-5">
        <div class="card-body">
            <div class="certificate-print p-5 text-center" style="border: 4px double #007bff;">
                <h1 style="color: #007bff; font-weight: 800; border-bottom: 2px solid #007bff;">CERTIFICATE OF COMPLETION</h1>
                <p class="mt-4 mb-2">Dengan ini menyatakan bahwa:</p>
                <h2 class="fw-bold text-success"><?php echo strtoupper($data_siswa['nama_lengkap']); ?></h2>
                <p class="mb-4">Telah berhasil menyelesaikan program pelatihan:</p>
                
                <h3 class="fw-bold text-dark mb-4"><?php echo strtoupper($data_siswa['program_pendidikan']); ?></h3>
                
                <table class="mx-auto" style="width: 60%; font-size: 1.1rem; margin-top: 30px;">
                    <tr>
                        <td class="text-start">Nomor Induk (NIS)</td>
                        <td class="text-end fw-bold"><?php echo $data_siswa['nis']; ?></td>
                    </tr>
                    <tr>
                        <td class="text-start">Status Kelulusan</td>
                        <td class="text-end fw-bold text-success">LULUS</td>
                    </tr>
                    <tr>
                        <td class="text-start">Predikat</td>
                        <td class="text-end fw-bold text-info"><?php echo $data_lulus['grade']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card-footer bg-white text-center no-print">
            <button onclick="window.print()" class="btn btn-primary btn-lg fw-bold px-5">
                <i class="bi bi-download me-2"></i> Cetak / Download Sertifikat
            </button>
        </div>
    </div>
    
<?php else: ?>
    <div class="alert alert-warning text-center">
        <i class="bi bi-exclamation-triangle-fill fs-4 d-block mb-2"></i>
        Maaf, Anda belum memenuhi syarat kelulusan program.
    </div>
<?php endif; ?>

<style>
    @media print {
        .no-print, .navbar-top, #sidebar-wrapper { display: none !important; }
        .certificate-print { border: none !important; }
        body { margin: 0 !important; }
    }
</style>

<?php require_once '_template_footer.php'; ?>