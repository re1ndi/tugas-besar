<?php
// pages/absensi_admin.php
session_start();
require_once '_template_sidebar.php'; 

// CEK ROLE
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}
$user_id = $_SESSION['user_id']; 

// 1. LOGIKA FILTER TANGGAL
$tgl_filter = isset($_GET['tgl']) ? $_GET['tgl'] : date('Y-m-d');
$msg = "";

// 2. LOGIKA UPDATE ABSENSI MASSAL
if (isset($_POST['update_absensi'])) {
    $tanggal_absensi = $_POST['tanggal_absensi'];
    $ids = $_POST['trainee_id'];
    $statuses = $_POST['status'];
    $kets = $_POST['keterangan'];

    $sukses_count = 0;
    
    foreach ($ids as $i => $tid) {
        $status = sanitize_input($conn, $statuses[$i]);
        $keterangan = sanitize_input($conn, $kets[$i]);

        $cek_q = $conn->query("SELECT id_absensi FROM absensi WHERE trainee_id = '$tid' AND tanggal = '$tanggal_absensi'");
        
        if (mysqli_num_rows($cek_q) > 0) {
            // Jika sudah ada, lakukan UPDATE
            $q = $conn->prepare("UPDATE absensi SET status_kehadiran=?, keterangan=? WHERE trainee_id=? AND tanggal=?");
            $q->bind_param("ssis", $status, $keterangan, $tid, $tanggal_absensi);
        } else {
            // Jika belum ada, lakukan INSERT
            $q = $conn->prepare("INSERT INTO absensi (trainee_id, status_kehadiran, keterangan, tanggal) VALUES (?, ?, ?, ?)");
            $q->bind_param("siss", $tid, $status, $keterangan, $tanggal_absensi); 
        }
        
        if (isset($q) && $q->execute()) {
            $sukses_count++;
        }
        if (isset($q)) $q->close();
    }

    $msg = "<div class='alert alert-success'>Berhasil memperbarui $sukses_count data absensi untuk tanggal $tanggal_absensi.</div>";
    
    header("location:absensi_admin.php?tgl=$tanggal_absensi&msg=success");
    exit;
}

// 3. QUERY DATA ABSENSI & SISWA (LEFT JOIN)
$sql = "SELECT t.trainee_id, t.nis, t.nama_lengkap, t.program_pendidikan, 
               a.status_kehadiran, a.keterangan
        FROM trainee t
        LEFT JOIN absensi a ON t.trainee_id = a.trainee_id AND a.tanggal = '$tgl_filter'
        WHERE t.status_pendaftaran = 'Aktif'
        ORDER BY t.nama_lengkap ASC";
        
$result_siswa = $conn->query($sql);

// --- QUERY STATISTIK TOTAL KEHADIRAN UNTUK TANGGAL INI ---
$q_hadir = $conn->query("SELECT COUNT(*) AS count FROM absensi WHERE tanggal = '$tgl_filter' AND status_kehadiran = 'Hadir'");
$q_sakit = $conn->query("SELECT COUNT(*) AS count FROM absensi WHERE tanggal = '$tgl_filter' AND status_kehadiran = 'Sakit'");
$q_izin = $conn->query("SELECT COUNT(*) AS count FROM absensi WHERE tanggal = '$tgl_filter' AND status_kehadiran = 'Izin'");
$q_alpha = $conn->query("SELECT COUNT(*) AS count FROM absensi WHERE tanggal = '$tgl_filter' AND status_kehadiran = 'Alpha'");

$total_hadir = $q_hadir->fetch_assoc()['count'] ?? 0;
$total_sakit = $q_sakit->fetch_assoc()['count'] ?? 0;
$total_izin = $q_izin->fetch_assoc()['count'] ?? 0;
$total_alpha = $q_alpha->fetch_assoc()['count'] ?? 0;

$conn->close();
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-calendar-check-fill me-2 text-primary"></i> Monitoring & Update Absensi</h2>
<p class="lead mb-4">Kelola kehadiran siswa untuk tanggal <?php echo date('d F Y', strtotime($tgl_filter)); ?>.</p>

<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" action="absensi_admin.php" class="d-flex align-items-center mb-4">
            <label for="tgl_absensi" class="me-3 fw-bold small">Pilih Tanggal:</label>
            <input type="date" name="tgl" id="tgl_absensi" class="form-control me-3" value="<?php echo $tgl_filter; ?>" style="max-width: 200px;" required>
            <button type="submit" name="filter_tgl" class="btn btn-primary px-4">Tampilkan Data</button>
            <?php if($tgl_filter != date('Y-m-d')): ?>
                <a href="absensi_admin.php" class="btn btn-outline-secondary ms-2">Hari Ini</a>
            <?php endif; ?>
        </form>

        <?php echo isset($_GET['msg']) ? '<div class="alert alert-success">Data berhasil diperbarui!</div>' : ''; ?>

        <h5 class="fw-bold mb-3 mt-4 border-bottom pb-2">Rekapitulasi Kehadiran Tanggal Ini</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card bg-success text-white p-3"><h4 class="mb-0"><?php echo $total_hadir; ?></h4><small>Hadir</small></div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark p-3"><h4 class="mb-0"><?php echo $total_sakit; ?></h4><small>Sakit</small></div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white p-3"><h4 class="mb-0"><?php echo $total_izin; ?></h4><small>Izin</small></div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white p-3"><h4 class="mb-0"><?php echo $total_alpha; ?></h4><small>Alpha</small></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
        <span class="fw-bold">Tabel Absensi (Mode Baca)</span>
        
        <div class="btn-group">
            <button type="button" id="btnEditMode" class="btn btn-warning fw-bold btn-sm shadow">
                <i class="bi bi-pencil-square me-1"></i> Aktifkan Mode Edit
            </button>
            <button type="submit" form="absensiForm" id="btnSaveMode" class="btn btn-success fw-bold btn-sm shadow" style="display:none;">
                <i class="bi bi-save me-1"></i> Simpan Perubahan
            </button>
        </div>
    </div>
    
    <form method="POST" action="absensi_admin.php" id="absensiForm">
        <input type="hidden" name="tanggal_absensi" value="<?php echo $tgl_filter; ?>">
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">NIS</th>
                            <th>Nama Siswa</th>
                            <th>Program</th>
                            <th style="width: 20%;">Status Kehadiran</th>
                            <th style="width: 30%;">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_siswa && $result_siswa->num_rows > 0): while($row = $result_siswa->fetch_assoc()): 
                            $current_status = $row['status_kehadiran'] ?? 'Alpha';
                            $current_keterangan = $row['keterangan'] ?? '';
                            $row_class = $row['status_kehadiran'] ? 'table-success bg-opacity-10' : '';
                            $status_options = ['Hadir', 'Izin', 'Sakit', 'Alpha'];
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td class="ps-4 fw-bold"><?php echo $row['nis']; ?></td>
                            <td>
                                <?php echo $row['nama_lengkap']; ?>
                                <input type="hidden" name="trainee_id[]" value="<?php echo $row['trainee_id']; ?>">
                            </td>
                            <td><?php echo $row['program_pendidikan']; ?></td>
                            <td>
                                <select name="status[]" class="form-select form-select-sm status-input" required disabled>
                                    <?php foreach ($status_options as $option) { 
                                        $selected = ($option == $current_status) ? 'selected' : '';
                                        echo "<option value='{$option}' {$selected}>{$option}</option>";
                                    } ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="keterangan[]" class="form-control form-control-sm keterangan-input" value="<?php echo $current_keterangan; ?>" placeholder="Keterangan..." disabled>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">Tidak ada data siswa aktif.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnEdit = document.getElementById('btnEditMode');
    const btnSave = document.getElementById('btnSaveMode');
    const inputs = document.querySelectorAll('.status-input, .keterangan-input');
    const statusRows = document.querySelectorAll('.table-responsive tbody tr');

    // Fungsi untuk mengaktifkan mode edit
    function enableEditMode() {
        inputs.forEach(input => {
            input.disabled = false;
        });
        btnEdit.style.display = 'none';
        btnSave.style.display = 'block';
        alert('Mode Edit Aktif! Anda sekarang bisa merubah data absensi. Jangan lupa klik "Simpan Perubahan".');
    }

    // Listener tombol Edit
    if (btnEdit) {
        btnEdit.addEventListener('click', enableEditMode);
    }
    
    // Logika: Jika tidak ada data, jangan tampilkan tombol Save/Edit
    if (statusRows.length === 0 || statusRows[0].cells.length === 1) { 
        btnEdit.style.display = 'none';
    }
});
</script>

<?php 
require_once '_template_footer.php'; 
?>