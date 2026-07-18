<?php
// pages/cek_tunggakan.php
session_start();
require_once '_template_sidebar.php'; 

if ($role !== 'trainee') { header("Location: ../index.php"); exit(); }

// FUNGSI UNTUK MENGAMBIL CONFIG (Biaya Program)
function getConfig($key) {
    global $conn;
    $q = $conn->query("SELECT config_value FROM sistem_config WHERE config_key='$key'");
    // Pastikan hasil ada, jika tidak, kembalikan 0.00
    return $q->fetch_assoc()['config_value'] ?? 0.00;
}

// Konstanta Biaya diambil dari Database
define("TOTAL_BIAYA_PROGRAM", getConfig("TOTAL_BIAYA_PROGRAM"));

// Ambil ID Siswa
$q_siswa = $conn->query("SELECT trainee_id FROM trainee WHERE user_id='$user_id'");
$trainee_id = $q_siswa->fetch_assoc()['trainee_id'] ?? 0;
$program_saya = $conn->query("SELECT program_pendidikan FROM trainee WHERE user_id='$user_id'")->fetch_assoc()['program_pendidikan'] ?? 'N/A';

// LOGIKA CEK STATUS KEUANGAN
$q_bayar = $conn->query("SELECT SUM(jumlah) AS total FROM pembayaran WHERE trainee_id='$trainee_id' AND status='VERIFIED'");
$total_sudah_bayar = $q_bayar->fetch_assoc()['total'] ?? 0;
$sisa_tagihan = TOTAL_BIAYA_PROGRAM - $total_sudah_bayar;

// Hindari error divide by zero jika total biaya belum diatur (seharusnya sudah diatur oleh SQL di atas)
$persentase = (TOTAL_BIAYA_PROGRAM > 0) ? round(($total_sudah_bayar / TOTAL_BIAYA_PROGRAM) * 100, 1) : 0;

$conn->close();
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-currency-dollar me-2 text-primary"></i> Status Tunggakan Biaya</h2>
<p class="lead">Informasi tagihan dan sisa pembayaran program Anda. (Biaya Program Saat Ini: Rp <?php echo number_format(TOTAL_BIAYA_PROGRAM, 0, ',', '.'); ?>)</p>

<div class="row g-4">
    <div class="col-lg-12">
        <div class="card shadow-sm h-100 border-start border-4 border-info">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-3">Informasi Keuangan Program</h4>
                
                <div class="row">
                    <div class="col-md-4">
                        <p class="text-secondary small mb-1">Total Biaya Program</p>
                        <h3 class="fw-bold text-primary">Rp <?php echo number_format(TOTAL_BIAYA_PROGRAM, 0, ',', '.'); ?></h3>
                    </div>
                    <div class="col-md-4">
                        <p class="text-secondary small mb-1">Total Sudah Dibayar</p>
                        <h3 class="fw-bold text-success">Rp <?php echo number_format($total_sudah_bayar, 0, ',', '.'); ?></h3>
                    </div>
                    <div class="col-md-4">
                        <p class="text-secondary small mb-1">SISA TAGIHAN ANDA</p>
                        <?php if ($sisa_tagihan <= 0): ?>
                            <h3 class="fw-bold text-success">LUNAS!</h3>
                        <?php else: ?>
                            <h3 class="fw-bold text-danger">Rp <?php echo number_format($sisa_tagihan, 0, ',', '.'); ?></h3>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="my-4">

                <div class="mt-3">
                    <p class="mb-2 fw-bold">Progress Pembayaran: <?php echo $persentase; ?>%</p>
                    <div class="progress" role="progressbar" style="height: 25px;">
                        <div class="progress-bar bg-success fw-bold" style="width: <?php echo min(100, $persentase); ?>%">
                           <?php echo $persentase; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>