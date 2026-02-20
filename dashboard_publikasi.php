<?php
include "koneksi.php";

// ambil data periode untuk dropdown
$periode = mysqli_query($conn, 
    "SELECT id_periode, tahun, gelombang 
     FROM periode_diklat 
     ORDER BY tahun DESC, gelombang DESC");

if(isset($_POST['submit'])){

    $id_periode    = $_POST['id_periode'];
    $estimasi      = $_POST['estimasi_bulan'];
    $tempat        = $_POST['tempat'];

    $nama_file = $_FILES['brosur']['name'];
    $tmp       = $_FILES['brosur']['tmp_name'];

    $folder = "uploads/";
    move_uploaded_file($tmp, $folder.$nama_file);

   $cek = mysqli_query($conn, 
    "SELECT * FROM informasi_diklat 
     WHERE id_periode='$id_periode'");

if(mysqli_num_rows($cek) > 0){

    mysqli_query($conn, "
        UPDATE informasi_diklat SET
        brosur_path='$nama_file',
        estimasi_bulan='$estimasi',
        tempat='$tempat'
        WHERE id_periode='$id_periode'
    ");

    $pesan = "update";

}else{

    mysqli_query($conn, "
        INSERT INTO informasi_diklat 
        (id_periode, brosur_path, estimasi_bulan, tempat)
        VALUES 
        ('$id_periode','$nama_file','$estimasi','$tempat')
    ");

    $pesan = "insert";
}
}
?>

<?php if(isset($pesan)) : ?>
<script>
document.addEventListener("DOMContentLoaded", function(){
    <?php if($pesan == "insert") : ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Publikasi berhasil ditambahkan.',
            confirmButtonColor: '#ffd60a'
        });
    <?php else : ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Publikasi berhasil diperbarui.',
            confirmButtonColor: '#ffd60a'
        });
    <?php endif; ?>
});
</script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Publikasi</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#0d1b2a;
    color:white;
}

.navbar{
    background:#001f3f !important;
}

.logo-navbar{
    width:40px;
}

.card-form{
    background:#1b263b;
    padding:30px;
    border-radius:15px;
}

.btn-primary{
    background:#ffd60a;
    border:none;
    color:black;
    font-weight:bold;
}

.btn-primary:hover{
    background:#ffc300;
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container">

    <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
      <img src="img/logo.png" class="logo-navbar">
      <span class="fw-bold ms-2">Gemilang</span>
    </a>

    <button class="navbar-toggler" type="button" 
            data-bs-toggle="collapse" 
            data-bs-target="#navmenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navmenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="publikasi.php">Publikasi</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-danger fw-semibold" href="logout.php">Logout</a>
        </li>
      </ul>
    </div>

  </div>
</nav>

<div class="container" style="margin-top:120px; max-width:700px;">

    <div class="card-form shadow">
        <h4 class="mb-4 text-warning fw-bold">Upload Publikasi Diklat</h4>

        <form method="POST" enctype="multipart/form-data">

            <!-- ANGKATAN -->
            <div class="mb-3">
                <label class="form-label">Pilih Angkatan</label>
                <select name="id_periode" class="form-select" required>
                    <option value="">-- Pilih Angkatan --</option>
                    <?php while($p = mysqli_fetch_assoc($periode)) : ?>
                        <option value="<?= $p['id_periode']; ?>">
                            Gelombang <?= $p['gelombang']; ?> - <?= $p['tahun']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- ESTIMASI BULAN -->
            <div class="mb-3">
                <label class="form-label">Estimasi Bulan</label>
                <select name="estimasi_bulan" class="form-select" required>
                    <option value="">-- Pilih Bulan --</option>
                    <?php for($i=1; $i<=12; $i++) : ?>
                        <option value="<?= $i; ?>"><?= $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- TEMPAT -->
            <div class="mb-3">
                <label class="form-label">Tempat Pelatihan</label>
                <input type="text" name="tempat" class="form-control" required>
            </div>

            <!-- UPLOAD -->
            <div class="mb-4">
                <label class="form-label">Upload Brosur</label>
                <input type="file" name="brosur" class="form-control" required>
            </div>

            <button type="submit" name="submit" class="btn btn-primary w-100">
                Upload Publikasi
            </button>

        </form>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>