<?php
// pages/kelola_materi.php
session_start();
require_once '_template_sidebar.php'; 

if ($role !== 'pengajar' && $role !== 'admin') { header("Location: ../index.php"); exit(); }

$msg = "";
$upload_dir = '../uploads/materi/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

// 1. DEFINISI MATA KULIAH
$matkul_db = [
    'Staff Airlines' => [
        'Aviation Knowledge', 'UU Penerbangan & AVSEC', 'Customer Service', 'English for Airlines', 
        'Passenger & Baggage Handling', 'Ramp Handling', 'Weight & Balance', 'Cargo Handling',
        'Reservation', 'Tarif & Ticketing', 'Communication Skill', 'Leadership', 'Grooming & Beauty'
    ],
    'Flight Attendant' => [
        'Aviation Knowledge', 'Aviation Security', 'Cabin Practice', 'Flight Announcement', 'English for Airlines',
        'Flight Safety', 'Passenger Handling', 'Social Grace',
        'Dangerous Good', 'Food and Beverage', 'Communication Skill', 'Poise Grooming', 'Swimming'
    ],
    'Cruise Line Hotel School' => [
        'Basic Safety & Ship Familiarization', 'Food Product, Pastry & Bakery', 'House Keeping & Laundry', 'Front Office',
        'Bartending & Mixology', 'Food & Beverages Service', 'Sanitasi', 'Personality & Appearance',
        'Job Interview & Marine Test', 'Hospitality English'
    ]
];

// 2. AMBIL PROGRAM PENGAJAR
$q_guru = $conn->query("SELECT program_ajar FROM pengajar WHERE user_id='$user_id'");
$program_saya = $q_guru->fetch_assoc()['program_ajar'] ?? 'Staff Airlines';

$list_matkul_saya = $matkul_db[$program_saya] ?? [];

// Logika Filter Program
$filter_program = ($role == 'admin' && isset($_GET['prog'])) ? urldecode($_GET['prog']) : $program_saya;
if ($role == 'admin' && $filter_program == 'all') {
    $filter_sql = "1=1";
} else {
    $filter_sql = "program_pendidikan = '$filter_program'";
}

// Data Edit Default
$is_edit = false;
$edit_data = ['materi_id' => null, 'judul' => '', 'deskripsi' => '', 'file_name' => '', 'file_path_old' => ''];

// --- LOGIKA PERSIAPAN EDIT ---
if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $id = sanitize_input($conn, $_GET['edit_id']);
    $q_edit = $conn->query("SELECT * FROM materi WHERE materi_id='$id' AND user_id='$user_id'");
    if ($q_edit->num_rows > 0) {
        $data = $q_edit->fetch_assoc();
        $edit_data['materi_id'] = $data['materi_id'];
        $edit_data['judul'] = $data['judul'];
        $edit_data['deskripsi'] = $data['deskripsi'];
        $edit_data['file_name'] = $data['file_name'];
        $edit_data['program_upload'] = $data['program_pendidikan'];
        $edit_data['file_path_old'] = $data['file_path'];
    } else {
        echo "<script>alert('Materi tidak ditemukan atau Anda tidak memiliki izin edit.'); window.location='kelola_materi.php';</script>";
        exit();
    }
}

// --- LOGIKA UPLOAD/UPDATE ---
if (isset($_POST['upload_materi'])) {
    $judul = sanitize_input($conn, $_POST['judul']);
    $deskripsi = sanitize_input($conn, $_POST['deskripsi']);
    $prog_upload = sanitize_input($conn, $_POST['program_upload']);
    $materi_id = sanitize_input($conn, $_POST['materi_id']);
    
    $file_update_needed = !empty($_FILES['file_materi']['name']);
    $success = false;

    // A. HANDLE FILE UPLOAD/REPLACEMENT
    if ($file_update_needed) {
        $file_tmp = $_FILES['file_materi']['tmp_name'];
        $original_name = basename($_FILES['file_materi']['name']);
        $new_filename = time() . '_' . uniqid() . '_' . $original_name;
        $target_file = $upload_dir . $new_filename;

        if ($materi_id && !empty($_POST['file_path_old'])) {
            @unlink($upload_dir . $_POST['file_path_old']); 
        }
        
        if (!move_uploaded_file($file_tmp, $target_file)) {
            $msg = "<div class='alert alert-danger'>Gagal mengunggah file baru.</div>";
            goto end_upload_logic;
        }
    } else {
        $new_filename = sanitize_input($conn, $_POST['file_path_old']);
        $original_name = sanitize_input($conn, $_POST['file_name_old']);
        
        if (!$materi_id && empty($new_filename)) {
             $msg = "<div class='alert alert-danger'>Wajib mengunggah file materi.</div>";
             goto end_upload_logic;
        }
    }

    // B. HANDLE DATABASE INSERT/UPDATE
    if ($materi_id) {
        $sql = "UPDATE materi SET judul=?, deskripsi=?, file_name=?, file_path=? WHERE materi_id=?";
        $q = $conn->prepare($sql);
        $q->bind_param("ssssi", $judul, $deskripsi, $original_name, $new_filename, $materi_id);
        $success = $q->execute();
        $action_msg = "diperbarui";
    } else {
        $sql = "INSERT INTO materi (user_id, program_pendidikan, judul, deskripsi, file_name, file_path) VALUES (?, ?, ?, ?, ?, ?)";
        $q = $conn->prepare($sql);
        $q->bind_param("isssss", $user_id, $prog_upload, $judul, $deskripsi, $original_name, $new_filename);
        $success = $q->execute();
        $action_msg = "diunggah";
    }

    if ($success) {
        $msg = "<div class='alert alert-success'>Materi <strong>$judul</strong> berhasil $action_msg!</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Gagal $action_msg ke database.</div>";
    }

    end_upload_logic:
}

// --- LOGIKA HAPUS (DELETE) ---
if (isset($_GET['delete_id'])) {
    $id = sanitize_input($conn, $_GET['delete_id']);
    
    $q_file = $conn->query("SELECT file_path, user_id FROM materi WHERE materi_id='$id'");
    
    if ($q_file->num_rows > 0) {
        $data = $q_file->fetch_assoc();
        
        if ($data['user_id'] == $user_id || $role == 'admin') {
            if (file_exists($upload_dir . $data['file_path'])) {
                unlink($upload_dir . $data['file_path']);
            }
            $conn->query("DELETE FROM materi WHERE materi_id='$id'");
            echo "<script>alert('Materi berhasil dihapus!'); window.location='kelola_materi.php';</script>";
            exit();
        }
    }
}

// --- AMBIL DAFTAR MATERI (READ) ---
$list_materi = $conn->query("SELECT m.*, u.username FROM materi m JOIN users u ON m.user_id = u.user_id WHERE $filter_sql ORDER BY tanggal_upload DESC");

// Daftar Pilihan Program untuk Admin Filter
$program_options = array_keys($matkul_db);

$matkul_dropdown = $matkul_db[$program_saya] ?? [];

$conn->close();
?>

<h2 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-folder-fill me-2 text-primary"></i> Kelola Materi Pembelajaran</h2>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0 sticky-top" style="top: 80px; z-index: 1;">
            <div class="card-header bg-primary text-white fw-bold py-3">
                <i class="bi bi-<?php echo $is_edit ? 'pencil-square' : 'cloud-upload'; ?> me-1"></i> <?php echo $is_edit ? 'Edit Materi' : 'Unggah Materi Baru'; ?>
            </div>
            <div class="card-body">
                <?php echo $msg; ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="materi_id" value="<?php echo $edit_data['materi_id']; ?>">
                    <input type="hidden" name="file_path_old" value="<?php echo $edit_data['file_path_old']; ?>">
                    <input type="hidden" name="file_name_old" value="<?php echo $edit_data['file_name']; ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Program Terkait</label>
                        <input type="text" class="form-control bg-light" value="<?php echo $program_saya; ?>" readonly>
                        <input type="hidden" name="program_upload" value="<?php echo $program_saya; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Mata Kuliah (Judul)</label>
                        <select name="judul" class="form-select" required>
                            <option value="">-- Pilih Mata Kuliah --</option>
                            <?php 
                            $current_judul = $edit_data['judul'] ?? '';
                            foreach ($matkul_dropdown as $mk): ?>
                                <option value="<?php echo htmlspecialchars($mk); ?>" <?php echo ($current_judul == $mk ? 'selected' : ''); ?>>
                                    <?php echo $mk; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="2"><?php echo $edit_data['deskripsi'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3 border rounded p-2 bg-light">
                        <label class="form-label small fw-bold text-dark w-100">Upload File Materi</label> 
                        <input type="file" name="file_materi" class="form-control form-control-sm" <?php echo $is_edit ? '' : 'required'; ?>>
                        <?php if($is_edit): ?>
                            <small class="text-danger">File lama: <strong><?php echo $edit_data['file_name']; ?></strong></small>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" name="upload_materi" class="btn btn-primary w-100 fw-bold">
                        <?php echo $is_edit ? 'Simpan Perubahan' : 'Unggah Materi'; ?>
                    </button>
                    <?php if($is_edit): ?>
                        <a href="kelola_materi.php" class="btn btn-light w-100 mt-2 border">Batal Edit</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold">Daftar Materi (<?php echo $filter_program; ?>)</span>
                
                <?php if ($role == 'admin'): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown">
                        Filter: <?php echo ($filter_program == 'all' ? 'Semua Program' : $filter_program); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="?prog=all">Semua Program</a></li>
                        <?php foreach ($program_options as $opt): ?>
                            <li><a class="dropdown-item" href="?prog=<?php echo urlencode($opt); ?>"><?php echo $opt; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Mata Kuliah & Deskripsi</th>
                                <th>Program</th>
                                <th>Tanggal</th>
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
                                <td><span class="badge bg-info text-dark"><?php echo $row['program_pendidikan']; ?></span></td>
                                <td><?php echo date('d M y', strtotime($row['tanggal_upload'])); ?></td>
                                <td><?php echo ($row['user_id'] == $user_id ? 'Anda' : $row['username']); ?></td>
                                <td class="text-center">
                                    <?php if ($row['user_id'] == $user_id || $role == 'admin'): ?>
                                    <a href="kelola_materi.php?edit_id=<?php echo $row['materi_id']; ?>" class="btn btn-sm btn-info text-white me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <a href="kelola_materi.php?delete_id=<?php echo $row['materi_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus materi ini?')"><i class="bi bi-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada materi diunggah untuk program ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '_template_footer.php'; ?>