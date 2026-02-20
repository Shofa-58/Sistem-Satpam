<?php
session_start();
include "koneksi.php";

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

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, 
        "SELECT * FROM akun WHERE username='$username'");

    if(mysqli_num_rows($query) == 1){

        $akun = mysqli_fetch_assoc($query);

        // Jika masih plain text
        if($password === $akun['password']){

        // Kalau nanti pakai hash, ganti jadi:
        // if(password_verify($password, $akun['password'])){

            $_SESSION['id_akun'] = $akun['id_akun'];
            $_SESSION['username'] = $akun['username'];
            $_SESSION['role'] = $akun['role'];

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
            }
            exit();

        }else{
            $error = "Password salah!";
        }

    }else{
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Sistem Satpam</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#0d1b2a;
    font-family:'Segoe UI',sans-serif;
    margin:0;
}

/* NAVBAR */
.navbar{
    background:#001f3f !important;
}

.navbar-brand img{
    width:35px;
}

/* WRAPPER */
.login-wrapper{
    min-height:calc(100vh - 70px);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:40px 15px;
}

/* BOX */
.login-box{
    background:#1b263b;
    padding:35px;
    border-radius:12px;
    width:100%;
    max-width:420px;
    color:white;
    box-shadow:0 8px 25px rgba(0,0,0,0.4);
}

.login-box h3{
    text-align:center;
    margin-bottom:25px;
    color:#ffd60a;
    font-weight:600;
}

/* INPUT */
.form-control{
    background:#0d1b2a;
    border:1px solid #415a77;
    color:white;
}

.form-control:focus{
    background:#0d1b2a;
    color:white;
    border-color:#ffd60a;
    box-shadow:none;
}

/* BUTTON */
.btn-login{
    background:#ffd60a;
    border:none;
    color:black;
    font-weight:600;
}

.btn-login:hover{
    background:#ffc300;
}

.alert{
    font-size:14px;
}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
      <img src="img/logo.png" class="me-2">
      <span class="fw-bold">Gemilang</span>
    </a>

    <div class="ms-auto">
      <a href="dashboard.php" class="btn btn-sm btn-outline-warning">
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