<?php
// pages/kelola_pengajar.php
session_start();
require_once '_template_sidebar.php'; 
if ($role !== 'admin') { header("Location: ../index.php"); exit(); }

$msg = ""; 
$is_edit = false;

// INISIALISASI DATA UNTUK MODE TAMBAH BARU (Password dijamin kosong)
$edit_data = [
    'username'=>'', 
    'nama_lengkap'=>'', 
    'program_ajar'=>'', 
    'password'=>'' // Ini harus kosong saat bukan edit
];

// --- LOGIKA HAPUS (D) ---
if (isset($_GET['hapus_id'])) {
    $id = sanitize_input($conn, $_GET['hapus_id']);
    $q_cari = $conn->query("SELECT user_id FROM pengajar WHERE pengajar_id='$id'")->fetch_assoc()['user_id'];
    $conn->query("DELETE FROM users WHERE user_id='$uid'");
    echo "<script>alert('Terhapus!'); window.location='kelola_pengajar.php';</script>";
}

// --- LOGIKA PERSIAPAN EDIT (R) ---
if (isset($_GET['edit_id'])) {
    $is_edit = true; $id = sanitize_input($conn, $_GET['edit_id']);
    // Ambil data untuk diisi di form
    $q = $conn->query("SELECT p.*, u.password, u.username FROM pengajar p JOIN users u ON p.user_id=u.user_id WHERE p.pengajar_id='$id'")->fetch_assoc();
    if ($q) {
        $edit_data = $q;
    }
}

// --- LOGIKA SIMPAN (C / U) ---
if (isset($_POST['simpan'])) {
    $nik = sanitize_input($conn, $_POST['nik']); 
    $nama = sanitize_input($conn, $_POST['nama']); 
    $prog = sanitize_input($conn, $_POST['program']); 
    $pass = $_POST['password'];

    if ($is_edit) {
        // UPDATE
        $pid = $_POST['pid']; $uid = $_POST['uid'];
        $conn->query("UPDATE users SET username='$nik', password='$pass' WHERE user_id='$uid'");
        $conn->query("UPDATE pengajar SET nik='$nik', nama_lengkap='$nama', program_ajar='$prog' WHERE pengajar_id='$pid'");
        $msg = "<div class='alert alert-success'>Data diperbarui!</div>";
        // Update data agar tidak hilang saat refresh form
        $edit_data['username']=$nik; $edit_data['nama_lengkap']=$nama; $edit_data['program_ajar']=$prog; $edit_data['password']=$pass;
    } else {
        // CREATE
        if ($conn->query("INSERT INTO users (username, password, role) VALUES ('$nik', '$pass', 'pengajar')")) {
            $uid = $conn->insert_id;
            $conn->query("INSERT INTO pengajar (user_id, nik, nama_lengkap, program_ajar) VALUES ('$uid', '$nik', '$nama', '$prog')");
            $msg = "<div class='alert alert-success'>Pengajar ditambahkan!</div>";
            // Redirect setelah insert agar form bersih
            header("location:kelola_pengajar.php"); exit; 
        } else { 
            $msg = "<div class='alert alert-danger'>NIK sudah ada!</div>"; 
            // Jika gagal, simpan input lain kecuali password
            $edit_data['username']=$nik; $edit_data['nama_lengkap']=$nama; $edit_data['program_ajar']=$prog; $edit_data['password']=''; // DIJAMIN KOSONG
        }
    }
}
$list = $conn->query("SELECT p.*, u.username, u.password FROM pengajar p JOIN users u ON p.user_id=u.user_id ORDER BY p.pengajar_id DESC");
$program_opts = ['Staff Airlines','Flight Attendant','Cruise Line Hotel School'];
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-person-workspace me-2 text-primary"></i> Kelola Data Pengajar</h2>
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold py-3 border-bottom border-3 border-primary"><?php echo $is_edit?'Edit Pengajar':'Tambah Pengajar'; ?></div>
            <div class="card-body p-4">
                <?php echo $msg; ?>
                <form method="POST">
                    <?php if($is_edit): ?>
                        <input type="hidden" name="pid" value="<?=$edit_data['pengajar_id']?>">
                        <input type="hidden" name="uid" value="<?=$edit_data['user_id']?>">
                    <?php endif; ?>
                    <div class="mb-2"><label class="small fw-bold">NIK (Username)</label><input type="text" name="nik" class="form-control" required value="<?=$edit_data['username']?>" placeholder="Masukkan NIK"></div>
                    <div class="mb-2"><label class="small fw-bold">Nama Lengkap</label><input type="text" name="nama" class="form-control" required value="<?=$edit_data['nama_lengkap']?>" placeholder="Nama Lengkap"></div>
                    <div class="mb-2"><label class="small fw-bold">Program Ajar</label>
                        <select name="program" class="form-select" required>
                            <option value="" disabled <?php echo !$is_edit?'selected':'';?>>-- Pilih --</option>
                            <?php foreach($program_opts as $o){ echo "<option ".($edit_data['program_ajar']==$o?'selected':'')." value='$o'>$o</option>"; } ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="small fw-bold">Password</label><input type="text" name="password" class="form-control" required value="<?=$edit_data['password']?>" placeholder="Masukkan Password"></div>
                    <button name="simpan" class="btn btn-primary w-100 fw-bold"><?php echo $is_edit?'Simpan Perubahan':'Tambah Data'; ?></button>
                    <?php if($is_edit): ?><a href="kelola_pengajar.php" class="btn btn-light w-100 mt-2 border">Batal Edit</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between">
                <span>Daftar Pengajar</span>
                <input type="text" id="cari" class="form-control form-control-sm w-50" placeholder="Cari..." onkeyup="liveSearch()">
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle small">
                        <thead class="table-light"><tr><th class="ps-3">NIK</th><th>Nama</th><th>Program</th><th>Password</th><th class="text-center">Aksi</th></tr></thead>
                        <tbody id="tblBody">
                            <?php while($r=$list->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3 fw-bold"><?=$r['nik']?></td><td><?=$r['nama_lengkap']?></td><td><span class="badge bg-info text-dark"><?=$r['program_ajar']?></span></td><td class="text-muted"><?=$r['password']?></td>
                                <td class="text-center">
                                    <a href="?edit_id=<?=$r['pengajar_id']?>" class="btn btn-sm btn-info text-white me-1"><i class="bi bi-pencil-square"></i></a>
                                    <a href="?hapus_id=<?=$r['pengajar_id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus?')"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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