<?php
session_start();
include "koneksi.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != "admin"){
    header("location:login.php");
    exit;
}

if(isset($_POST['tambah_periode'])){

$tahun = $_POST['tahun'];
$gelombang = $_POST['gelombang'];
$tanggal_mulai = $_POST['tanggal_mulai'];
$tanggal_selesai = $_POST['tanggal_selesai'];
$biaya = $_POST['biaya'];
$lokasi = $_POST['lokasi'];
$fasilitas = $_POST['fasilitas'];
$status = $_POST['status'];

mysqli_query($conn,"
INSERT INTO periode_diklat
(tahun,gelombang,tanggal_mulai,tanggal_selesai,biaya,lokasi,fasilitas,status)
VALUES
('$tahun','$gelombang','$tanggal_mulai','$tanggal_selesai','$biaya','$lokasi','$fasilitas','$status')
");

header("location:admin_persiapan_diklat.php");
}

if(isset($_POST['tambah_jadwal'])){

$tanggal = $_POST['tanggal'];
$kegiatan = $_POST['kegiatan'];
$keterangan = $_POST['keterangan'];
$id_periode = $_POST['id_periode'];

mysqli_query($conn,"
INSERT INTO jadwal_diklat
(tanggal,kegiatan,keterangan,id_periode)
VALUES
('$tanggal','$kegiatan','$keterangan','$id_periode')
");

header("location:admin_persiapan_diklat.php");
}

$periode = mysqli_query($conn,"SELECT * FROM periode_diklat ORDER BY tahun DESC");

$jadwal = mysqli_query($conn,"
SELECT j.*,p.tahun,p.gelombang
FROM jadwal_diklat j
LEFT JOIN periode_diklat p
ON j.id_periode=p.id_periode
ORDER BY j.tanggal ASC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Persiapan Diklat</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/base.css">
<link rel="stylesheet" href="css/admin_persiapan_diklat.css">

</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--navy);">
<div class="container-fluid">
<a class="navbar-brand fw-bold" href="#">Admin Diklat</a>

<div class="ms-auto">
<a href="dashboard_admin.php" class="btn btn-sm btn-light me-2">Dashboard</a>
<a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
</div>
</div>
</nav>

<div class="container mt-4">

<div class="row g-4">

<div class="col-lg-6">

<div class="card shadow-sm">
<div class="card-body">

<h5 class="mb-3">Tambah Periode Diklat</h5>

<form method="POST">

<div class="row">
<div class="col-md-6 mb-3">
<label>Tahun</label>
<input type="number" name="tahun" class="form-control" required>
</div>

<div class="col-md-6 mb-3">
<label>Gelombang</label>
<input type="number" name="gelombang" class="form-control" required>
</div>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label>Tanggal Mulai</label>
<input type="date" name="tanggal_mulai" class="form-control" required>
</div>

<div class="col-md-6 mb-3">
<label>Tanggal Selesai</label>
<input type="date" name="tanggal_selesai" class="form-control" required>
</div>
</div>

<div class="mb-3">
<label>Biaya Diklat</label>
<input type="number" name="biaya" class="form-control">
</div>

<div class="mb-3">
<label>Lokasi</label>
<input type="text" name="lokasi" class="form-control">
</div>

<div class="mb-3">
<label>Fasilitas</label>
<textarea name="fasilitas" class="form-control"></textarea>
</div>

<div class="mb-3">
<label>Status</label>
<select name="status" class="form-select">
<option value="pendaftaran">Pendaftaran</option>
<option value="berjalan">Berjalan</option>
<option value="selesai">Selesai</option>
</select>
</div>

<button class="btn btn-warning" name="tambah_periode">
Simpan Periode
</button>

</form>

</div>
</div>

</div>

<div class="col-lg-6">

<div class="card shadow-sm">
<div class="card-body">

<h5 class="mb-3">Tambah Rundown Diklat</h5>

<form method="POST">

<div class="mb-3">
<label>Pilih Periode</label>

<select name="id_periode" class="form-select">
<?php while($p=mysqli_fetch_assoc($periode)){ ?>
<option value="<?php echo $p['id_periode']; ?>">
<?php echo $p['tahun']." - Gelombang ".$p['gelombang']; ?>
</option>
<?php } ?>
</select>

</div>

<div class="mb-3">
<label>Tanggal</label>
<input type="date" name="tanggal" class="form-control">
</div>

<div class="mb-3">
<label>Kegiatan</label>
<input type="text" name="kegiatan" class="form-control">
</div>

<div class="mb-3">
<label>Keterangan</label>
<textarea name="keterangan" class="form-control"></textarea>
</div>

<button class="btn btn-warning" name="tambah_jadwal">
Tambah Jadwal
</button>

</form>

</div>
</div>

</div>

</div>

<div class="card shadow-sm mt-4">
<div class="card-body">

<h5 class="mb-3">Rundown Diklat</h5>

<div class="table-responsive">

<table class="table table-bordered table-hover text-center align-middle">

<thead class="table-dark">
<tr>
<th>Tanggal</th>
<th>Kegiatan</th>
<th>Keterangan</th>
<th>Periode</th>
</tr>
</thead>

<tbody>

<?php while($j=mysqli_fetch_assoc($jadwal)){ ?>

<tr>
<td><?php echo $j['tanggal']; ?></td>
<td><?php echo $j['kegiatan']; ?></td>
<td><?php echo $j['keterangan']; ?></td>
<td><?php echo $j['tahun']." - G".$j['gelombang']; ?></td>
</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>