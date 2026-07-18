<?php
// pages/absensi_pengajar.php
session_start();
require_once '_template_sidebar.php';

// HANYA PENGAJAR DAN ADMIN
if ($role !== 'pengajar' && $role !== 'admin') { header("Location: ../index.php"); exit(); }

// 1. TENTUKAN PROGRAM
if ($role == 'admin') {
    $program_aktif = isset($_GET['prog']) ? urldecode($_GET['prog']) : 'Staff Airlines';
} else {
    $q_guru = $conn->query("SELECT program_ajar FROM pengajar WHERE user_id='$user_id'");
    $program_aktif = $q_guru->fetch_assoc()['program_ajar'] ?? 'Staff Airlines';
}

// 2. TANGKAP FILTER DARI URL
$tgl_pilih = isset($_GET['tgl']) ? $_GET['tgl'] : date('Y-m-d');
$angkatan_pilih = isset($_GET['angkatan']) ? $_GET['angkatan'] : '';

// 3. LOGIKA SIMPAN (INSERT / UPDATE)
if (isset($_POST['simpan_absen'])) {
    $tgl_input = $_POST['tanggal_absen'];
    $ids = $_POST['id_trainee'];
    $statuses = $_POST['status'];
    $kets = $_POST['keterangan'];
    $pilih = isset($_POST['pilih']) ? $_POST['pilih'] : []; // Checkbox

    $sukses = 0;
    foreach ($ids as $i => $tid) {
        // Hanya proses yang dicentang
        if (in_array($tid, $pilih)) {
            $st = $statuses[$i];
            $k = sanitize_input($conn, $kets[$i]);

            // Hapus data lama agar bersih (mekanisme replace/update)
            $conn->query("DELETE FROM absensi WHERE trainee_id='$tid' AND tanggal='$tgl_input'");

            // Insert data baru/update
            $sql = "INSERT INTO absensi (trainee_id, status_kehadiran, keterangan, tanggal) 
                    VALUES ('$tid', '$st', '$k', '$tgl_input')";
            
            if ($conn->query($sql)) {
                $sukses++;
            }
        }
    }

    $redirect_url = "absensi_pengajar.php?tgl=$tgl_input&angkatan=".urlencode($_POST['angkatan_hidden']);
    if ($role == 'admin') { $redirect_url .= "&prog=".urlencode($program_aktif); }
    
    echo "<script>alert('Berhasil! Data kehadiran $sukses siswa telah diperbarui.'); window.location='$redirect_url';</script>";
}

// 4. AMBIL LIST ANGKATAN
$list_angkatan = $conn->query("SELECT DISTINCT angkatan FROM trainee WHERE program_pendidikan='$program_aktif' ORDER BY angkatan DESC");

// 5. AMBIL SISWA + DATA ABSENSI (LEFT JOIN)
// Query ini mengambil SEMUA siswa di angkatan tersebut.
// Jika sudah absen, kolom 'status_kehadiran' akan terisi. Jika belum, akan NULL.
$siswa = null;
if ($angkatan_pilih != '') {
    $sql_siswa = "SELECT t.*, a.status_kehadiran, a.keterangan 
                  FROM trainee t
                  LEFT JOIN absensi a ON t.trainee_id = a.trainee_id AND a.tanggal = '$tgl_pilih'
                  WHERE t.program_pendidikan='$program_aktif' 
                  AND t.status_pendaftaran='Aktif' 
                  AND t.angkatan = '$angkatan_pilih'
                  ORDER BY t.nama_lengkap ASC";
    $siswa = $conn->query($sql_siswa);
}
?>

<div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <h2 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-range me-2 text-primary"></i> Kelola Kehadiran</h2>
    
    <?php if($role == 'admin'): ?>
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown">
            Program: <strong><?php echo $program_aktif; ?></strong>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="?prog=Staff Airlines">Staff Airlines</a></li>
            <li><a class="dropdown-item" href="?prog=Flight Attendant">Flight Attendant</a></li>
            <li><a class="dropdown-item" href="?prog=Cruise Line Hotel School">Cruise Line Hotel School</a></li>
        </ul>
    </div>
    <?php else: ?>
        <span class="badge bg-info text-dark fs-6"><?php echo $program_aktif; ?></span>
    <?php endif; ?>
</div>

<form method="GET" action="absensi_pengajar.php">
    <?php if($role == 'admin'): ?><input type="hidden" name="prog" value="<?php echo $program_aktif; ?>"><?php endif; ?>
    
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="row align-items-end g-2">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary mb-1">Tanggal Absensi</label>
                    <input type="date" name="tgl" class="form-control fw-bold" value="<?php echo $tgl_pilih; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary mb-1">Pilih Angkatan</label>
                    <select name="angkatan" class="form-select fw-bold" required>
                        <option value="" disabled <?php echo ($angkatan_pilih == '') ? 'selected' : ''; ?>>-- Pilih Angkatan --</option>
                        <?php while($a = $list_angkatan->fetch_assoc()): ?>
                            <option value="<?php echo $a['angkatan']; ?>" <?php echo ($angkatan_pilih == $a['angkatan']) ? 'selected' : ''; ?>>
                                Angkatan <?php echo $a['angkatan']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="bi bi-search me-1"></i> Tampilkan / Edit Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if ($angkatan_pilih != ''): ?>
    <form method="POST">
        <input type="hidden" name="tanggal_absen" value="<?php echo $tgl_pilih; ?>">
        <input type="hidden" name="angkatan_hidden" value="<?php echo $angkatan_pilih; ?>">

        <div class="card shadow-sm border-0 mb-5">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-primary">
                    Daftar Siswa - <span class="text-dark">Tgl: <?php echo date('d-m-Y', strtotime($tgl_pilih)); ?></span>
                </span>
                <div>
                    <button type="button" class="btn btn-outline-success btn-sm me-1" onclick="setAllStatus('Hadir')">Semua Hadir</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleCheckbox(this)">Pilih Semua</button>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 text-center" width="50">#</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th width="200">Status Kehadiran</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($siswa && $siswa->num_rows > 0): while($row = $siswa->fetch_assoc()): ?>
                                <?php 
                                    // Cek apakah data sudah ada di database
                                    $is_recorded = !empty($row['status_kehadiran']);
                                    // Tentukan value default dropdown (jika belum ada, default 'Hadir')
                                    $current_status = $is_recorded ? $row['status_kehadiran'] : 'Hadir';
                                    // Warna baris: Hijau tipis jika sudah diabsen
                                    $row_class = $is_recorded ? 'table-success bg-opacity-10' : '';
                                ?>
                                <tr class="<?php echo $row_class; ?>">
                                    <td class="text-center">
                                        <input type="checkbox" name="pilih[]" value="<?php echo $row['trainee_id']; ?>" class="form-check-input check-item" checked>
                                    </td>
                                    <td class="fw-bold text-secondary"><?php echo $row['nis']; ?></td>
                                    <td class="fw-bold text-dark">
                                        <?php echo $row['nama_lengkap']; ?>
                                        <?php if($is_recorded): ?>
                                            <i class="bi bi-check-circle-fill text-success ms-1" title="Sudah Diabsen"></i>
                                        <?php endif; ?>
                                        <input type="hidden" name="id_trainee[]" value="<?php echo $row['trainee_id']; ?>">
                                    </td>
                                    <td>
                                        <select name="status[]" class="form-select form-select-sm fw-bold border-secondary status-select">
                                            <option value="Hadir" <?php echo ($current_status == 'Hadir') ? 'selected' : ''; ?>>Hadir</option>
                                            <option value="Sakit" <?php echo ($current_status == 'Sakit') ? 'selected' : ''; ?>>Sakit</option>
                                            <option value="Izin" <?php echo ($current_status == 'Izin') ? 'selected' : ''; ?>>Izin</option>
                                            <option value="Alpha" <?php echo ($current_status == 'Alpha') ? 'selected' : ''; ?>>Alpha</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="keterangan[]" class="form-control form-control-sm" value="<?php echo $row['keterangan']; ?>" placeholder="-">
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-x fs-1 d-block mb-2 opacity-50"></i>
                                    Tidak ada siswa aktif di angkatan ini.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if ($siswa && $siswa->num_rows > 0): ?>
            <div class="card-footer bg-white p-3 text-end">
                <button type="submit" name="simpan_absen" class="btn btn-primary px-5 fw-bold">
                    <i class="bi bi-save-fill me-2"></i> Simpan / Update Absensi
                </button>
            </div>
            <?php endif; ?>
        </div>
    </form>
<?php else: ?>
    <div class="alert alert-info text-center py-4 border-0 shadow-sm">
        <i class="bi bi-info-circle fs-4 d-block mb-2"></i>
        Silakan pilih <strong>Tanggal</strong> dan <strong>Angkatan</strong> untuk menampilkan atau mengedit data absensi.
    </div>
<?php endif; ?>

<script>
    function toggleCheckbox(btn) {
        let checkboxes = document.querySelectorAll('.check-item');
        if(checkboxes.length > 0) {
            let newState = !checkboxes[0].checked;
            checkboxes.forEach(cb => { cb.checked = newState; });
        }
    }
    
    function setAllStatus(val) {
        let selects = document.querySelectorAll('.status-select');
        selects.forEach(sel => { sel.value = val; });
    }
</script>

<?php require_once '_template_footer.php'; ?>