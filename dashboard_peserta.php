<?php
session_start();
include "koneksi.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != "siswa"){
    header("location:login.php");
    exit;
}

$id_akun = $_SESSION['id_akun'];

$peserta = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT p.*
FROM peserta p
JOIN akun a ON p.id_akun = a.id_akun
WHERE a.id_akun='$id_akun'
"));

$id_peserta = $peserta['id_peserta'];

$periode = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT pd.*
FROM peserta_periode pp
JOIN periode_diklat pd ON pp.id_periode=pd.id_periode
WHERE pp.id_peserta='$id_peserta'
"));

$jadwal = mysqli_query($conn,"
SELECT *
FROM jadwal_diklat
WHERE id_periode='{$periode['id_periode']}'
ORDER BY tanggal ASC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<title>Dashboard Peserta</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="css/base.css">
<link rel="stylesheet" href="css/dashboard_peserta.css">

</head>

<body>

<nav class="navbar navbar-dark" style="background-color: var(--navy);">
<div class="container-fluid">

<span class="navbar-brand fw-bold">
Dashboard Peserta
</span>

<a href="logout.php" class="btn btn-sm btn-danger">
Logout
</a>

</div>
</nav>

<div class="container mt-4">

<div class="card shadow-sm mb-4">
<div class="card-body">

<h5>Informasi Diklat</h5>

<p><strong>Periode :</strong> 
<?php echo $periode['tahun']; ?> 
Gelombang <?php echo $periode['gelombang']; ?>
</p>

<p><strong>Tanggal :</strong> 
<?php echo $periode['tanggal_mulai']; ?> 
- 
<?php echo $periode['tanggal_selesai']; ?>
</p>

<p><strong>Lokasi :</strong> 
<?php echo $periode['lokasi']; ?>
</p>

<p><strong>Fasilitas :</strong><br>
<?php echo nl2br($periode['fasilitas']); ?>
</p>

</div>
</div>


<div class="card shadow-sm">

<div class="card-body">

<h5 class="mb-3">Rundown Diklat</h5>

<div class="table-responsive">

<table class="table table-bordered table-hover text-center">

<thead class="table-dark">
<tr>
<th>Tanggal</th>
<th>Kegiatan</th>
<th>Keterangan</th>
</tr>
</thead>

<tbody>

<?php while($j=mysqli_fetch_assoc($jadwal)){ ?>

<tr>

<td><?php echo $j['tanggal']; ?></td>

<td><?php echo $j['kegiatan']; ?></td>

<td><?php echo $j['keterangan']; ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</body>
</html>