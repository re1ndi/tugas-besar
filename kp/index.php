<?php
// index.php
session_start();
include 'config/db_connect.php';

$error = "";

$remembered_username = $_COOKIE['remember_username'] ?? '';

// 1. Cek jika user sudah login, langsung redirect
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: pages/dashboard_admin.php");
    } elseif ($_SESSION['role'] == 'pengajar') {
        header("Location: pages/dashboard_pengajar.php");
    } else { 
        header("Location: pages/dashboard_trainee.php");
    }
    exit();
}

// 2. Proses Login saat tombol ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize_input($conn, $_POST['username']);
    $password = $_POST['password']; 

    $sql = "SELECT user_id, password, role FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        // Verifikasi Password (Plain Text)
        if ($password === $row['password']) { 
            
            // LOGIKA REMEMBER ME
            if (isset($_POST['remember'])) {
                setcookie('remember_username', $username, time() + (30 * 24 * 60 * 60), "/"); 
            } else {
                if (isset($_COOKIE['remember_username'])) {
                    setcookie('remember_username', '', time() - 3600, "/");
                }
            }

            // Set Session Variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $row['role'];

            // Redirect ke halaman dashboard
            if ($_SESSION['role'] == 'admin') {
                header("Location: pages/dashboard_admin.php");
            } elseif ($_SESSION['role'] == 'pengajar') {
                header("Location: pages/dashboard_pengajar.php");
            } else { 
                header("Location: pages/dashboard_trainee.php");
            }
            exit();
        } else {
            $error = "Username atau Password salah.";
        }
    } else {
        $error = "Username atau Password salah.";
    }
    $stmt->close();
}
if(isset($conn) && $conn) $conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Manajemen Trainee</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f0f3f5; } 
        .login-wrapper { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .logo-login { max-width: 140px; height: auto; margin-bottom: 25px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); }
        .login-container { 
            max-width: 420px; 
            width: 100%;
            border-radius: 12px; 
            background-color: #fff;
            border: 1px solid #ced4da; 
        }
        
        /* INPUT GRUP & ICON GABUNGAN */
        .input-group-seamless {
            position: relative;
            display: flex;
            align-items: center;
            border: 1px solid #ced4da; 
            border-radius: 8px;
            overflow: hidden;
            height: 50px;
            margin-bottom: 1.5rem;
        }

        /* ICON KIRI (PREFIX) */
        .input-group-seamless .icon-prefix {
            position: absolute;
            left: 15px;
            color: #6c757d;
            font-size: 1.2rem;
            z-index: 10;
        }

        /* Input field padding */
        .input-group-seamless input {
            border: none;
            padding-left: 45px; /* Ruang untuk ikon prefix */
            padding-right: 45px; /* Ruang untuk ikon toggle */
            height: 100%;
        }

        /* ICON TOGGLE MATA (KANAN) */
        .toggle-password-merged {
            position: absolute;
            right: 0;
            background: none;
            border: none;
            color: #6c757d;
            height: 100%;
            width: 50px;
            cursor: pointer;
            z-index: 10;
        }

        /* Focus State */
        .input-group-seamless:focus-within {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .btn-masuk { background-color: #0d6efd; border-color: #0d6efd; color: white; font-weight: bold; padding: 12px; font-size: 1rem; border-radius: 8px; width: 100%; transition: all 0.3s; }
        .btn-masuk:hover { background-color: #0b5ed7; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25); }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100">

    <div class="login-wrapper">
        
        <img src="assets/img/logo.png" alt="Logo Yayasan Tadika Puri" class="logo-login">

        <div class="login-container shadow-lg p-4 p-md-5">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-primary mb-2">Sistem Manajemen Trainee</h4>
                <p class="text-muted small">Masuk dengan akun Anda</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center small" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><div><?php echo $error; ?></div></div>
            <?php endif; ?>
            
            <form method="POST" action="index.php">
                
                <div class="input-group-seamless">
                    <i class="bi bi-person icon-prefix"></i>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="<?php echo htmlspecialchars($remembered_username); ?>" required>
                </div>

                <div class="input-group-seamless">
                    <i class="bi bi-lock icon-prefix"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <button class="toggle-password-merged" type="button" id="togglePassword" title="Lihat Password">
                        <i class="bi bi-eye-slash" id="toggleIcon"></i>
                    </button>
                </div>
                
                <div class="form-check text-start mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" <?php echo $remembered_username ? 'checked' : ''; ?>>
                    <label class="form-check-label small text-muted" for="rememberMe">
                        Remember Me
                    </label>
                </div>

                <button type="submit" name="login" class="btn btn-masuk mt-2">Masuk</button>
                
                </form>
        </div>
        
        <div class="mt-4 text-center text-muted small opacity-50">
            &copy; <?php echo date("Y"); ?> Yayasan Tadika Puri. All rights reserved.
        </div>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            toggleIcon.classList.toggle('bi-eye');
            toggleIcon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>