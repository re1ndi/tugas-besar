<?php
// config/db_connect.php

if (!defined('DB_KONEKSI_AKTIF')) {
    define('DB_KONEKSI_AKTIF', true);
    
    ini_set('display_errors', 0); 
    error_reporting(E_ALL);
    
    $servername = "localhost";
    $username = "root"; 
    $password = "";     
    $dbname = "db_kp"; 

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Koneksi Database Gagal: " . $conn->connect_error); 
    }

    function sanitize_input($conn, $data) {
        return $conn->real_escape_string(trim($data));
    }
}
?>