<?php
// pages/kelola_biaya.php
session_start();
require_once '_template_sidebar.php'; 

if ($role !== 'admin') { header("Location: dashboard_admin.php"); exit(); }

$msg = "";
$program_options = ['Staff Airlines', 'Flight Attendant', 'Cruise Line Hotel School'];

// FUNGSI UNTUK MENGAMBIL CONFIG
function getConfig($key) {
    global $conn;
    $q = $conn->query("SELECT total_biaya FROM program_biaya WHERE program_pendidikan='$key'");
    return $q->fetch_assoc()['total_biaya'] ?? 0.00;
}

// LOGIKA UPDATE
if (isset($_POST['update_biaya'])) {
    $prog_key = sanitize_input($conn, $_POST['prog_key']);
    $biaya_baru = floatval(sanitize_input($conn, $_POST['total_biaya']));
    
    // Insert/Update ke tabel program_biaya
    $q = $conn->prepare("INSERT INTO program_biaya (program_pendidikan, total_biaya) VALUES (?, ?) ON DUPLICATE KEY UPDATE total_biaya = ?");
    $q->bind_param("sds", $prog_key, $biaya_baru, $biaya_baru);
    
    if ($q->execute()) {
        echo "<script>alert('Biaya untuk $prog_key berhasil diperbarui!'); window.location='kelola_biaya.php?prog=".urlencode($prog_key)."';</script>";
    } else {
        $msg = "<div class='alert alert-danger'>Gagal menyimpan perubahan.</div>";
    }
}

// AMBIL BIAYA SAAT INI UNTUK DIPERLIHATKAN DI FORM
$program_pilih = isset($_GET['prog']) ? urldecode($_GET['prog']) : '';
$current_biaya = 0;
if ($program_pilih) {
    $current_biaya = getConfig($program_pilih);
}
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-cash-coin me-2 text-primary"></i> Kelola Biaya Program</h2>
<p class="lead mb-4">Atur total biaya yang menjadi dasar perhitungan tunggakan per program.</p>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold py-3">Pilih Program</div>
            <div class="card-body">
                <form method="GET" action="kelola_biaya.php">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Program Pendidikan</label>
                        <select name="prog" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Program --</option>
                            <?php foreach ($program_options as $opt): ?>
                                <option value="<?php echo urlencode($opt); ?>" <?php echo ($program_pilih == $opt) ? 'selected' : ''; ?>>
                                    <?php echo $opt; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info w-100 fw-bold">Pilih Program</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7">
        <?php if ($program_pilih): ?>
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold py-3">
                    Ubah Biaya: <?php echo $program_pilih; ?>
                </div>
                <div class="card-body">
                    <?php echo $msg; ?>
                    <form method="POST">
                        <input type="hidden" name="prog_key" value="<?php echo $program_pilih; ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Biaya Saat Ini (Tersimpan di Sistem):</label>
                            <input type="text" class="form-control bg-light fw-bold" value="Rp <?php echo number_format($current_biaya, 0, ',', '.'); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Masukkan Biaya Baru (Angka Murni)</label>
                            <input type="number" name="total_biaya" class="form-control" required value="<?php echo $current_biaya; ?>" placeholder="Contoh: 15000000">
                        </div>
                        
                        <button type="submit" name="update_biaya" class="btn btn-success fw-bold px-4">Simpan Perubahan Biaya</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info py-4 text-center">Silakan pilih program dari menu di samping untuk mengubah biayanya.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>