<?php
// pages/_template_sidebar.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../config/db_connect.php'; 

if (!isset($_SESSION['user_id'])) { header("location:../index.php"); exit; }

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

function generate_sidebar_menu($role) {
    function menuItem($href, $icon, $text) {
        $active = (basename($_SERVER['PHP_SELF']) == $href) ? 'active' : '';
        return '<li class="nav-item">
                    <a class="nav-link text-white '.$active.'" href="'.$href.'" title="'.$text.'">
                        <i class="bi '.$icon.'"></i>
                        <span class="sidebar-text ms-2">'.$text.'</span>
                    </a>
                </li>';
    }

    $menu = '';
    
    // --- MENU ROLE ADMIN (URUTAN BARU) ---
    if ($role == 'admin') {
        $menu .= menuItem('dashboard_admin.php', 'bi-grid-1x2-fill', 'Dashboard');
        
        // 1. MANAJEMEN UTAMA (COLLAPSIBLE)
        $menu .= '
            <li class="nav-item">
                <a class="nav-link text-white" href="#submenu_manajemen" data-bs-toggle="collapse" role="button" aria-expanded="false" id="manajemen_toggle">
                    <i class="bi bi-gear-fill me-2"></i>
                    <span class="sidebar-text ms-2">Manajemen Utama</span>
                    <i class="bi bi-chevron-right ms-auto sidebar-text toggle-icon-manajemen"></i>
                </a>
                <ul class="collapse list-unstyled ps-0" id="submenu_manajemen">';
        
        // URUTAN ADMIN: PENGAJAR -> TRAINEE -> ABSENSI -> NILAI
        $menu .= menuItem('kelola_pengajar.php', 'bi-person-workspace', 'Kelola Data Pengajar'); 
        $menu .= menuItem('tambah_trainee.php', 'bi-people-fill', 'Kelola Data Trainee'); 
        $menu .= menuItem('absensi_admin.php', 'bi-calendar-check-fill', 'Monitoring Absensi');
        $menu .= menuItem('input_nilai.php', 'bi-clipboard-data-fill', 'Input/Cek Nilai');
        
        $menu .= '</ul></li>';

        // 2. KEUANGAN (COLLAPSIBLE)
        $menu .= '
            <li class="nav-item">
                <a class="nav-link text-white" href="#submenu_keuangan" data-bs-toggle="collapse" role="button" aria-expanded="false" id="keuangan_toggle">
                    <i class="bi bi-cash-stack me-2"></i>
                    <span class="sidebar-text ms-2">Keuangan</span>
                    <i class="bi bi-chevron-right ms-auto sidebar-text toggle-icon-keuangan"></i>
                </a>
                <ul class="collapse list-unstyled ps-0" id="submenu_keuangan">';
        
        $menu .= menuItem('verifikasi_pembayaran.php', 'bi-shield-check', 'Verifikasi Transaksi'); 
        $menu .= menuItem('laporan_tunggakan.php', 'bi-file-text-fill', 'Laporan Tunggakan'); 
        $menu .= menuItem('kelola_biaya.php', 'bi-currency-dollar', 'Kelola Biaya Program'); 
        
        $menu .= '</ul></li>';
        
    // --- MENU ROLE PENGAJAR ---
    } elseif ($role == 'pengajar') {
        $menu .= menuItem('dashboard_pengajar.php', 'bi-grid-1x2-fill', 'Dashboard');
        $menu .= menuItem('absensi_pengajar.php', 'bi-calendar-plus-fill', 'Input Kehadiran');
        $menu .= menuItem('input_nilai.php', 'bi-pencil-square', 'Input Nilai Trainee');
        $menu .= menuItem('kelola_materi.php', 'bi-folder-fill', 'Kelola Materi');
            
    // --- MENU ROLE SISWA (TRAINEE - URUTAN BARU) ---
    } elseif ($role == 'trainee') {
        $menu .= menuItem('dashboard_trainee.php', 'bi-grid-1x2-fill', 'Dashboard');
        
        // 1. PEMBELAJARAN (COLLAPSIBLE)
        $menu .= '
            <li class="nav-item">
                <a class="nav-link text-white" href="#submenu_pembelajaran" data-bs-toggle="collapse" role="button" aria-expanded="false" id="pembelajaran_toggle">
                    <i class="bi bi-journals me-2"></i>
                    <span class="sidebar-text ms-2">Pembelajaran</span>
                    <i class="bi bi-chevron-right ms-auto sidebar-text toggle-icon-pembelajaran"></i>
                </a>
                <ul class="collapse list-unstyled ps-0" id="submenu_pembelajaran">';
        
        // URUTAN SISWA: KEHADIRAN -> MATERI -> NILAI -> SERTIFIKAT
        $menu .= menuItem('absensi_trainee.php', 'bi-calendar-event-fill', 'Riwayat Kehadiran');
        $menu .= menuItem('akses_materi.php', 'bi-book-half', 'Akses Materi');
        $menu .= menuItem('cek_nilai.php', 'bi-bar-chart-line-fill', 'Cek Hasil Nilai'); 
        $menu .= menuItem('sertifikat.php', 'bi-patch-check-fill', 'Download Sertifikat');

        $menu .= '</ul></li>';

        // 2. ADMINISTRASI (COLLAPSIBLE FIX PANAH)
         $menu .= '
            <li class="nav-item">
                <a class="nav-link text-white" href="#submenu_administrasi" data-bs-toggle="collapse" role="button" aria-expanded="false" id="administrasi_toggle">
                    <i class="bi bi-receipt-cutoff me-2"></i>
                    <span class="sidebar-text ms-2">Administrasi</span>
                    <i class="bi bi-chevron-right ms-auto sidebar-text toggle-icon-administrasi"></i>
                </a>
                <ul class="collapse list-unstyled ps-0" id="submenu_administrasi">';
        
        $menu .= menuItem('cek_tunggakan.php', 'bi-currency-dollar', 'Cek Tunggakan'); 
        $menu .= menuItem('unggah_bayar.php', 'bi-cloud-upload-fill', 'Unggah Bukti Bayar'); 

        $menu .= '</ul></li>';
    }
    return $menu;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Trainee - <?php echo strtoupper($role); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body { margin-top: 56px; background-color: #f5f7fa; overflow-x: hidden; } 
        #sidebar-wrapper { min-height: 100vh; width: 250px; position: fixed; top: 0; left: 0; z-index: 1050; background-color: #34495e; transition: all 0.3s ease; }
        #page-content-wrapper { margin-left: 250px; padding: 20px; width: calc(100% - 250px); transition: all 0.3s ease; }
        .navbar-top { z-index: 1030; margin-left: 250px; transition: all 0.3s ease; width: calc(100% - 250px); height: 56px; }
        .sidebar-header { height: 60px; background-color: #2c3e50; display: flex; align-items: center; justify-content: space-between; padding: 0 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-brand { font-weight: bold; color: white; white-space: nowrap; overflow: hidden; }
        #sidebarToggle { color: white; background: rgba(255,255,255,0.1); border: none; font-size: 1.2rem; cursor: pointer; padding: 5px 10px; border-radius: 5px; }
        .nav-link { padding: 12px 20px; font-size: 0.95rem; white-space: nowrap; border-left: 4px solid transparent; }
        .nav-link:hover, .nav-link.active { background-color: rgba(0,0,0,0.2); border-left-color: #3498db; color: #fff; }
        .nav-link i { font-size: 1.2rem; min-width: 35px; display: inline-block; text-align: center; }
        
        /* Nested Menu Styling */
        #submenu_pembelajaran .nav-link, #submenu_administrasi .nav-link, 
        #submenu_manajemen .nav-link, #submenu_keuangan .nav-link { padding: 8px 20px 8px 30px; font-size: 0.9rem; border-left: 0; background-color: rgba(0,0,0,0.1); }
        #submenu_pembelajaran .nav-link:hover, #submenu_administrasi .nav-link:hover, 
        #submenu_manajemen .nav-link:hover, #submenu_keuangan .nav-link:hover { background-color: rgba(0,0,0,0.3); }

        /* MODE TOGGLE */
        body.sb-toggled #sidebar-wrapper { width: 80px; }
        body.sb-toggled #page-content-wrapper { margin-left: 80px; width: calc(100% - 80px); }
        body.sb-toggled .navbar-top { margin-left: 80px; width: calc(100% - 80px); }
        body.sb-toggled .sidebar-text { display: none; }
        body.sb-toggled .sidebar-brand { display: none; }
        body.sb-toggled .role-badge { display: none; }
        body.sb-toggled .sidebar-header { justify-content: center; padding: 0; }
        body.sb-toggled .nav-item a { text-align: center; padding: 15px 0; }
        body.sb-toggled .nav-item i { margin-right: 0 !important; font-size: 1.5rem; }

        @media (max-width: 768px) {
            #sidebar-wrapper { margin-left: -250px; }
            #page-content-wrapper { margin-left: 0; width: 100%; }
            .navbar-top { margin-left: 0; width: 100%; }
            body.sb-toggled #sidebar-wrapper { margin-left: 0; width: 250px; }
            body.sb-toggled .sidebar-text { display: inline; }
            body.sb-toggled .sidebar-brand { display: block; }
            body.sb-toggled .sidebar-header { justify-content: space-between; padding: 0 15px; }
        }
    </style>
</head>
<body>
    
    <div id="sidebar-wrapper">
        <div class="sidebar-header">
            <div class="sidebar-brand"><span class="sidebar-brand-text">TADIKA PURI</span><br><small class="badge bg-primary role-badge" style="font-size: 0.6rem;"><?php echo strtoupper($role); ?></small></div>
            <button type="button" id="sidebarToggle"><i class="bi bi-list"></i></button>
        </div>
        <div class="list-group list-group-flush pt-2"><ul class="nav flex-column ps-0"><?php echo generate_sidebar_menu($role); ?></ul></div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top navbar-top">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1 ps-2">Sistem Manajemen Trainee</span>
            <div class="d-flex align-items-center ms-auto">
                <span class="text-muted small me-3 d-none d-sm-inline">Halo, <strong><?php echo $username; ?></strong></span>
                <a href="../logout.php" class="btn btn-sm btn-outline-danger d-flex align-items-center"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
            </div>
        </div>
    </nav>
    <div id="page-content-wrapper">
        <div class="container-fluid">