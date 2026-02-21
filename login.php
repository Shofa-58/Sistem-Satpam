<?php
session_start();
require "koneksi.php";

/* ===============================
   AUTO REDIRECT JIKA SUDAH LOGIN
================================= */
if(isset($_SESSION['role'])){
    switch($_SESSION['role']){
        case 'admin':
            header("Location: dashboard_admin.php");
            break;
        case 'siswa':
            header("Location: dashboard_siswa.php");
            break;
        case 'publikasi':
            header("Location: dashboard_publikasi.php");
            break;
        case 'kepala_keamanan':
            header("Location: dashboard_kepala.php");
            break;
    }
    exit();
}

/* ===============================
   PROSES LOGIN
================================= */
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($conn,
        "SELECT * FROM akun WHERE username='$username' LIMIT 1");

    if(mysqli_num_rows($query) === 1){

        $akun = mysqli_fetch_assoc($query);

        // WAJIB pakai password_verify
        if(password_verify($password, $akun['password'])){

            $_SESSION['id_akun']  = $akun['id_akun'];
            $_SESSION['username'] = $akun['username'];
            $_SESSION['role']     = $akun['role'];

            switch($akun['role']){
                case 'admin':
                    header("Location: dashboard_admin.php");
                    break;
                case 'siswa':
                    header("Location: dashboard_siswa.php");
                    break;
                case 'publikasi':
                    header("Location: dashboard_publikasi.php");
                    break;
                case 'kepala_keamanan':
                    header("Location: dashboard_kepala.php");
                    break;
                default:
                    session_destroy();
                    header("Location: login.php");
            }
            exit();

        } else {
            $error = "Username atau password salah!";
        }

    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Sistem Satpam</title>
<link rel="stylesheet" href="css/base.css">
<link rel="stylesheet" href="css/login.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="login-page">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="dashboard_umum.php">
      <img src="img/logo.png" class="me-2">
      <span class="fw-bold">Gemilang</span>
    </a>

    <div class="ms-auto">
      <a href="dashboard_umum.php" class="btn btn-sm btn-outline-warning">
        Kembali
      </a>
    </div>
  </div>
</nav>

<!-- LOGIN AREA -->
<div class="login-wrapper">

    <div class="login-box">

        <h3>Login Sistem</h3>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-login w-100">
                Login
            </button>
        </form>

    </div>

</div>

</body>
</html>