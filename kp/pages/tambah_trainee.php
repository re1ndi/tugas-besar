<?php
// pages/tambah_trainee.php
session_start();
require_once '_template_sidebar.php'; 

if ($role !== 'admin') { header("Location: ../index.php"); exit(); }

$msg = "";
$is_edit = false;
// Nilai default diatur ke string kosong ("")
$edit_data = [
    'nis' => '', 'nama_lengkap' => '', 'program_pendidikan' => '', 
    'angkatan' => '', 'no_telp' => '', 'password' => '', 'status_pendaftaran' => 'Aktif'
];

// --- LOGIKA HAPUS ---
if (isset($_GET['hapus_id'])) {
    $id_hapus = sanitize_input($conn, $_GET['hapus_id']);
    $q_cari = $conn->query("SELECT user_id FROM trainee WHERE trainee_id='$id_hapus'");
    if ($q_cari->num_rows > 0) {
        $uid_hapus = $q_cari->fetch_assoc()['user_id'];
        if ($conn->query("DELETE FROM users WHERE user_id='$uid_hapus'")) {
            echo "<script>alert('Data Trainee Berhasil Dihapus!'); window.location='tambah_trainee.php';</script>";
        }
    }
}

// --- LOGIKA PERSIAPAN EDIT (U) ---
if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $id = sanitize_input($conn, $_GET['edit_id']);
    $q = $conn->query("SELECT t.*, u.password FROM trainee t JOIN users u ON t.user_id = u.user_id WHERE t.trainee_id='$id'");
    if ($q->num_rows > 0) {
        $edit_data = $q->fetch_assoc();
    }
}

// --- LOGIKA SIMPAN (C / U) ---
if (isset($_POST['simpan'])) {
    $nis = sanitize_input($conn, $_POST['nis']);
    $nama = sanitize_input($conn, $_POST['nama']);
    $program = sanitize_input($conn, $_POST['program']);
    $angkatan = sanitize_input($conn, $_POST['angkatan']);
    $hp = sanitize_input($conn, $_POST['hp']);
    $pass = $_POST['password'];
    $status = $_POST['status'];

    if ($is_edit) {
        // UPDATE
        $id_trainee = $_POST['id_trainee'];
        $id_user = $_POST['id_user'];
        $conn->query("UPDATE users SET username='$nis', password='$pass' WHERE user_id='$id_user'");
        $sql_up = "UPDATE trainee SET nis='$nis', nama_lengkap='$nama', program_pendidikan='$program', angkatan='$angkatan', no_telp='$hp', status_pendaftaran='$status' WHERE trainee_id='$id_trainee'";
        
        if ($conn->query($sql_up) === TRUE) {
            $msg = "<div class='alert alert-success'>Data Trainee berhasil diperbarui!</div>";
        }
    } else {
        // CREATE BARU
        $cek = $conn->query("SELECT user_id FROM users WHERE username='$nis'");
        if ($cek->num_rows > 0) {
            $msg = "<div class='alert alert-danger'>Gagal: NIS $nis sudah terdaftar!</div>";
        } else {
            if ($conn->query("INSERT INTO users (username, password, role) VALUES ('$nis', '$pass', 'trainee')")) {
                $last_id = $conn->insert_id;
                $sql_ins = "INSERT INTO trainee (user_id, nis, nama_lengkap, program_pendidikan, angkatan, no_telp, status_pendaftaran) 
                            VALUES ('$last_id', '$nis', '$nama', '$program', '$angkatan', '$hp', '$status')";
                if ($conn->query($sql_ins)) {
                    $msg = "<div class='alert alert-success'>Trainee <strong>$nama</strong> berhasil ditambahkan!</div>";
                }
            } else {
                $msg = "<div class='alert alert-danger'>Error System.</div>";
            }
        }
    }
}

// --- LOGIKA PENCARIAN & DAFTAR (R) ---
$sql_search = "";
if (isset($_GET['cari'])) { $sql_search = " WHERE nama_lengkap LIKE '%" . sanitize_input($conn, $_GET['cari']) . "%' OR nis LIKE '%" . sanitize_input($conn, $_GET['cari']) . "%'"; }
$list_trainee = $conn->query("SELECT * FROM trainee $sql_search ORDER BY trainee_id DESC");
$program_opts = ['Staff Airlines', 'Flight Attendant', 'Cruise Line Hotel School'];
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-people-fill me-2 text-primary"></i> Kelola Data Trainee</h2>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 80px; z-index: 1;">
            <div class="card-header bg-white py-3 fw-bold border-bottom border-primary border-3">
                <?php echo $is_edit ? '<i class="bi bi-pencil-square"></i> Edit Data Trainee' : '<i class="bi bi-person-plus-fill"></i> Tambah Trainee Baru'; ?>
            </div>
            <div class="card-body p-4">
                <?php echo $msg; ?>
                <form method="POST">
                    <?php if($is_edit): ?>
                        <input type="hidden" name="id_trainee" value="<?php echo $edit_data['trainee_id']; ?>">
                        <input type="hidden" name="id_user" value="<?php echo $edit_data['user_id']; ?>">
                    <?php endif; ?>
                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small fw-bold">NIS (Username)</label>
                            <input type="text" name="nis" class="form-control" required 
                                   value="<?php echo $is_edit ? $edit_data['nis'] : ''; ?>" placeholder="Masukkan NIS">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="text" name="password" class="form-control" required 
                                   value="<?php echo $is_edit ? $edit_data['password'] : ''; ?>" placeholder="Masukkan Password">
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label small fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required 
                                   value="<?php echo $is_edit ? $edit_data['nama_lengkap'] : ''; ?>" placeholder="Nama Lengkap">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small fw-bold">Program Pendidikan</label>
                            <select name="program" class="form-select" required>
                                <option value="" disabled <?php echo !$is_edit ? 'selected' : ''; ?>>-- Pilih Program --</option>
                                <?php foreach($program_opts as $o) { echo "<option ".($edit_data['program_pendidikan']==$o?'selected':'')." value='$o'>$o</option>"; } ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small fw-bold">Angkatan</label>
                            <input type="number" name="angkatan" class="form-control" required 
                                   value="<?php echo $is_edit ? $edit_data['angkatan'] : ''; ?>" placeholder="Tahun Angkatan">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small fw-bold">No. HP</label>
                            <input type="number" name="hp" class="form-control" required 
                                   value="<?php echo $is_edit ? $edit_data['no_telp'] : ''; ?>" placeholder="08...">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach(['Aktif', 'Pending', 'Lulus', 'Remedial', 'Nonaktif'] as $s) { echo "<option ".($edit_data['status_pendaftaran']==$s?'selected':'').">$s</option>"; } ?>
                            </select>
                        </div>
                        <div class="col-12 pt-2 border-top">
                            <button type="submit" name="simpan" class="btn btn-primary w-100 fw-bold">
                                <?php echo $is_edit ? 'Simpan Perubahan' : 'Tambah Trainee'; ?>
                            </button>
                            <?php if($is_edit): ?>
                                <a href="tambah_trainee.php" class="btn btn-light w-100 mt-2 border">Batal Edit</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold">Daftar Trainee</span>
                <input type="text" id="cari" class="form-control form-control-sm w-50" placeholder="Ketik NIS / Nama..." onkeyup="liveSearch()">
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle small">
                        <thead class="table-light">
                            <tr><th class="ps-3">NIS</th><th>Nama</th><th>Program</th><th>Angkatan</th><th>Status</th><th class="text-center">Aksi</th></tr>
                        </thead>
                        <tbody id="tblBody">
                            <?php if ($list_trainee && $list_trainee->num_rows > 0): while($row = $list_trainee->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-3 fw-bold"><?php echo $row['nis']; ?></td><td><?php echo $row['nama_lengkap']; ?></td><td><?php echo $row['program_pendidikan']; ?></td>
                                    <td><?php echo $row['angkatan']; ?></td>
                                    <td><span class="badge bg-success rounded-pill"><?php echo $row['status_pendaftaran']; ?></span></td>
                                    <td class="text-center">
                                        <a href="tambah_trainee.php?edit_id=<?php echo $row['trainee_id']; ?>" class="btn btn-sm btn-info text-white p-1 px-2"><i class="bi bi-pencil-square"></i></a>
                                        <a href="tambah_trainee.php?hapus_id=<?php echo $row['trainee_id']; ?>" class="btn btn-sm btn-danger p-1 px-2" onclick="return confirm('Hapus data?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada data Trainee.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function liveSearch() {
    let input = document.getElementById("cari").value.toUpperCase();
    let tr = document.getElementById("tblBody").getElementsByTagName("tr");
    for (let i=0; i<tr.length; i++) {
        let text = tr[i].innerText || tr[i].textContent;
        tr[i].style.display = text.toUpperCase().indexOf(input) > -1 ? "" : "none";
    }
}
</script>
<?php require_once '_template_footer.php'; ?>