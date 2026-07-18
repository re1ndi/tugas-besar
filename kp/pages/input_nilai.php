<?php
// pages/input_nilai.php
session_start();
require_once '_template_sidebar.php';

// HANYA PENGAJAR DAN ADMIN
if ($role !== 'pengajar' && $role !== 'admin') { header("Location: ../index.php"); exit(); }

// 1. DATA MATA KULIAH
$matkul_db = [
    'Staff Airlines' => ['Aviation Knowledge', 'UU Penerbangan & AVSEC', 'Customer Service', 'English for Airlines', 'Passenger & Baggage Handling', 'Ramp Handling', 'Weight & Balance', 'Cargo Handling', 'Reservation', 'Tarif & Ticketing', 'Communication Skill', 'Leadership', 'Grooming & Beauty'],
    'Flight Attendant' => ['Aviation Knowledge', 'Aviation Security', 'Cabin Practice', 'Flight Announcement', 'English for Airlines', 'Flight Safety', 'Passenger Handling', 'Social Grace', 'Dangerous Good', 'Food and Beverage', 'Communication Skill', 'Poise Grooming', 'Swimming'],
    'Cruise Line Hotel School' => ['Basic Safety & Ship Familiarization', 'Food Product, Pastry & Bakery', 'House Keeping & Laundry', 'Front Office', 'Bartending & Mixology', 'Food & Beverages Service', 'Sanitasi', 'Personality & Appearance', 'Job Interview & Marine Test', 'Hospitality English']
];

// 2. DETEKSI PROGRAM & FILTER URL
if ($role == 'admin') {
    $program_aktif = isset($_GET['prog']) ? urldecode($_GET['prog']) : 'Staff Airlines';
} else {
    $q_guru = $conn->query("SELECT program_ajar FROM pengajar WHERE user_id='$user_id'");
    $program_aktif = $q_guru->fetch_assoc()['program_ajar'] ?? 'Staff Airlines';
}

$list_matkul = $matkul_db[$program_aktif] ?? [];
$angkatan_pilih = isset($_GET['angkatan']) ? $_GET['angkatan'] : '';
$matkul_pilih = isset($_GET['matkul']) ? urldecode($_GET['matkul']) : ''; 

// 3. LOGIKA SIMPAN NILAI 
if (isset($_POST['simpan_nilai'])) {
    $kode_pelatihan = $_POST['kode_pelatihan'];
    $angkatan_pilih = $_POST['angkatan_hidden'];
    $ids = $_POST['id_trainee'];
    $nilai_input = $_POST['nilai_akhir'];
    
    $sukses = 0;
    foreach ($ids as $i => $tid) {
        $na = intval($nilai_input[$i]);
        
        // HANYA PROSES JIKA NILAI ADA (1 sampai 100)
        if ($na > 0 && $na <= 100) { 
            // Tentukan Grade
            if ($na >= 90) { $grade = 'A'; $status = 'LULUS'; }
            elseif ($na >= 80) { $grade = 'B'; $status = 'LULUS'; }
            elseif ($na >= 70) { $grade = 'C'; $status = 'LULUS'; }
            else { $grade = 'D'; $status = 'TIDAK LULUS'; }

            $sql = "INSERT INTO nilai (trainee_id, kode_pelatihan, nilai_akhir, grade, status_lulus) 
                    VALUES ('$tid', '$kode_pelatihan', '$na', '$grade', '$status')
                    ON DUPLICATE KEY UPDATE nilai_akhir='$na', grade='$grade', status_lulus='$status'";
            
            if ($conn->query($sql)) $sukses++;
        }
    }
    
    // Redirect ke halaman yang sama dengan filter yang lengkap
    $redirect_url = "input_nilai.php?angkatan=".urlencode($angkatan_pilih)."&matkul=".urlencode($kode_pelatihan);
    if ($role == 'admin') { $redirect_url .= "&prog=".urlencode($program_aktif); }
    
    echo "<script>alert('Berhasil! Nilai $sukses siswa tersimpan/diperbarui.'); window.location='$redirect_url';</script>";
}

// 4. AMBIL LIST ANGKATAN
$list_angkatan = $conn->query("SELECT DISTINCT angkatan FROM trainee WHERE program_pendidikan='$program_aktif' ORDER BY angkatan DESC");

// 5. AMBIL SISWA + DATA NILAI (LEFT JOIN)
$siswa_nilai = null;
if ($angkatan_pilih != '' && $matkul_pilih != '') {
    $sql_siswa = "SELECT t.*, n.nilai_akhir, n.grade 
                  FROM trainee t
                  LEFT JOIN nilai n ON t.trainee_id = n.trainee_id AND n.kode_pelatihan = '$matkul_pilih'
                  WHERE t.program_pendidikan='$program_aktif' 
                  AND t.status_pendaftaran='Aktif' 
                  AND t.angkatan = '$angkatan_pilih'
                  ORDER BY t.nama_lengkap ASC";
    $siswa_nilai = $conn->query($sql_siswa);
}
?>

<div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <h2 class="fw-bold text-dark mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i> Input Nilai (<?php echo $program_aktif; ?>)</h2>
    <?php if($role == 'admin'): ?>
    <div class="dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown">Program</button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="?prog=Staff Airlines">Staff Airlines</a></li>
            <li><a class="dropdown-item" href="?prog=Flight Attendant">Flight Attendant</a></li>
            <li><a class="dropdown-item" href="?prog=Cruise Line Hotel School">Cruise Line Hotel School</a></li>
        </ul>
    </div>
    <?php endif; ?>
</div>

<form method="GET" action="input_nilai.php">
    <?php if($role == 'admin'): ?><input type="hidden" name="prog" value="<?php echo $program_aktif; ?>"><?php endif; ?>
    <div class="card shadow-sm border-0 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="row align-items-end g-2">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-secondary mb-1">Pilih Angkatan</label>
                    <select name="angkatan" class="form-select fw-bold" required>
                        <option value="" disabled <?php echo ($angkatan_pilih == '') ? 'selected' : ''; ?>>-- Pilih Angkatan --</option>
                        <?php 
                        $list_angkatan->data_seek(0); 
                        while($a = $list_angkatan->fetch_assoc()): ?>
                            <option value="<?php echo $a['angkatan']; ?>" <?php echo ($angkatan_pilih == $a['angkatan']) ? 'selected' : ''; ?>>
                                Angkatan <?php echo $a['angkatan']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="bi bi-search me-1"></i> Tampilkan Angkatan</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if ($angkatan_pilih != ''): ?>
    <form method="GET" action="input_nilai.php">
        <?php if($role == 'admin'): ?><input type="hidden" name="prog" value="<?php echo $program_aktif; ?>"><?php endif; ?>
        <input type="hidden" name="angkatan" value="<?php echo $angkatan_pilih; ?>">
        
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-3">
                <div class="row align-items-end g-2">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold text-secondary mb-1">Pilih Mata Kuliah</label>
                        <select name="matkul" class="form-select fw-bold" required>
                            <option value="" disabled <?php echo ($matkul_pilih == '') ? 'selected' : ''; ?>>-- Pilih Mata Kuliah --</option>
                            <?php foreach($list_matkul as $mk) { ?>
                                <option value="<?php echo htmlspecialchars($mk); ?>" <?php echo ($matkul_pilih == $mk) ? 'selected' : ''; ?>>
                                    <?php echo $mk; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-info w-100 fw-bold text-white"><i class="bi bi-book me-1"></i> Tampilkan Siswa</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    <?php if ($matkul_pilih != ''): ?>
        <form method="POST">
            <input type="hidden" name="kode_pelatihan" value="<?php echo htmlspecialchars($matkul_pilih); ?>">
            <input type="hidden" name="angkatan_hidden" value="<?php echo $angkatan_pilih; ?>">
            
            <div class="card shadow-sm border-0 mb-5">
                <div class="card-header bg-white py-3 fw-bold">
                    Input Nilai: <span class="text-primary"><?php echo $matkul_pilih; ?></span> (Angkatan <?php echo $angkatan_pilih; ?>)
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">NIS</th>
                                    <th>Nama Siswa</th>
                                    <th width="200" class="text-center">Nilai Akhir (0-100)</th>
                                    <th width="100" class="text-center">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($siswa_nilai && $siswa_nilai->num_rows > 0): while($row = $siswa_nilai->fetch_assoc()): 
                                    $existing_nilai = $row['nilai_akhir'] ?? '';
                                    $existing_grade = $row['grade'] ?? '-';
                                    $grade_class = 'text-muted'; 
                                    
                                    if ($existing_grade == 'A') $grade_class = 'text-success';
                                    else if ($existing_grade == 'B') $grade_class = 'text-primary';
                                    else if ($existing_grade == 'C') $grade_class = 'text-info';
                                    else if ($existing_grade == 'D') $grade_class = 'text-danger';
                                ?>
                                <tr class="<?php echo ($existing_nilai != '') ? 'table-success bg-opacity-10' : ''; ?>">
                                    <td class="ps-4 fw-bold text-secondary"><?php echo $row['nis']; ?></td>
                                    <td>
                                        <?php echo $row['nama_lengkap']; ?>
                                        <?php if($existing_nilai != ''): ?>
                                            <i class="bi bi-check-circle-fill text-success ms-1" title="Sudah Ada Nilai"></i>
                                        <?php endif; ?>
                                        <input type="hidden" name="id_trainee[]" value="<?php echo $row['trainee_id']; ?>">
                                    </td>
                                    <td>
                                        <input type="number" name="nilai_akhir[]" class="form-control text-center fw-bold nilai-input" 
                                               placeholder="0" min="0" max="100" value="<?php echo $existing_nilai; ?>" oninput="hitungGrade(this)">
                                    </td>
                                    <td class="text-center fw-bold grade-display <?php echo $grade_class; ?>">
                                        <?php echo $existing_grade; ?>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">Tidak ada siswa aktif di angkatan ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white p-3 text-end">
                    <button type="submit" name="simpan_nilai" class="btn btn-primary px-5 fw-bold">
                        <i class="bi bi-save me-2"></i> Simpan / Update Nilai
                    </button>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-warning text-center shadow-sm border-0">Silakan pilih Mata Kuliah untuk mulai menginput nilai.</div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-info text-center shadow-sm border-0">Silakan pilih Angkatan terlebih dahulu.</div>
<?php endif; ?>

<script>
function getGradeAndColor(nilai) {
    let grade = '-';
    let color = 'text-muted';
    
    if (isNaN(nilai) || nilai < 1 || nilai > 100) { 
        return { grade: grade, color: color }; 
    }

    if (nilai >= 90) { grade = 'A'; color = 'text-success'; }
    else if (nilai >= 80) { grade = 'B'; color = 'text-primary'; }
    else if (nilai >= 70) { grade = 'C'; color = 'text-info'; }
    else { // Nilai < 70
        grade = 'D'; color = 'text-danger';
    }
    
    return { grade: grade, color: color };
}

function hitungGrade(input) {
    let nilai = parseInt(input.value);
    let row = input.closest('tr');
    let gradeDisplay = row.querySelector('.grade-display');
    
    let result = getGradeAndColor(nilai);

    gradeDisplay.innerText = result.grade;
    gradeDisplay.className = "text-center fw-bold grade-display " + result.color;
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.nilai-input').forEach(input => {
        if (input.value) {
            hitungGrade(input);
        }
    });
});
</script>

<?php require_once '_template_footer.php'; ?>