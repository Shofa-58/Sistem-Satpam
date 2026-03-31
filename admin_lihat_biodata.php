<?php
session_start();
include "koneksi.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != "admin"){
    header("location:login.php");
    exit;
}

if(!isset($_GET['id'])){
    header("location:dashboard_admin.php");
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

$query = mysqli_query($conn, "
    SELECT s.*, pp.tanggal_terima
    FROM siswa s
    LEFT JOIN peserta_periode pp ON s.id_peserta = pp.id_peserta
    WHERE s.id_peserta = '$id'
");

$data = mysqli_fetch_assoc($query);

if(!$data){
    echo "Data tidak ditemukan.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Biodata Siswa - Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/admin_biodata.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--navy);">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard_admin.php">Admin Gemilang</a>
    </div>
</nav>

<div class="container my-5">
    <div class="card shadow biodata-card">
        <div class="card-body p-4 p-md-5">

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h3 class="mb-2">Biodata Siswa</h3>
                <span class="badge status_badge <?php echo $data['status']; ?>">
                    <?php echo strtoupper(str_replace('_',' ', $data['status'])); ?>
                </span>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap</label>
                    <div class="info-box"><?php echo htmlspecialchars($data['nama']); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <div class="info-box"><?php echo htmlspecialchars($data['email']); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">No HP</label>
                    <div class="info-box"><?php echo htmlspecialchars($data['no_telp']); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Jenis Kelamin</label>
                    <div class="info-box">
                        <?php echo $data['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Lahir</label>
                    <div class="info-box"><?php echo $data['tgl_lahir']; ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Agama</label>
                    <div class="info-box"><?php echo htmlspecialchars($data['agama']); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tinggi Badan</label>
                    <div class="info-box"><?php echo $data['tinggi_badan']; ?> cm</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Berat Badan</label>
                    <div class="info-box"><?php echo $data['berat_badan']; ?> kg</div>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Alamat</label>
                    <div class="info-box"><?php echo htmlspecialchars($data['alamat']); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Daftar</label>
                    <div class="info-box"><?php echo $data['created_at']; ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Batas Revisi</label>
                    <div class="info-box">
                        <?php echo $data['batas_revisi'] ?? '-'; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Diterima</label>
                    <div class="info-box">
                        <?php echo $data['tanggal_terima'] ?? '-'; ?>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-end">
                <a href="dashboard_admin.php" class="btn btn-secondary">Kembali</a>
            </div>

        </div>
    </div>
</div>

</body>
</html>