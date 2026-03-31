<?php
session_start();
include "koneksi.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != "admin"){
    header("location:login.php");
    exit;
}

$data = mysqli_query($conn,
    "SELECT * FROM siswa ORDER BY id_peserta DESC"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/dashboard_admin.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--navy);">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">Admin Gemilang</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard_admin.php">Dashboard</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Menu</a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="admin_status_siswa.php">Kelola Status</a></li>
                        <li><a class="dropdown-item" href="admin_persiapan_diklat.php">Persiapan Diklat</a></li>
                        <li><a class="dropdown-item" href="tambah_akun.php">Buat Akun</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Data Pendaftar</h4>
        <span class="badge bg-warning text-dark">
            <?php echo $_SESSION['username']; ?>
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Batas Revisi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php while($d = mysqli_fetch_array($data)){ ?>
                <tr>
                    <td><?php echo htmlspecialchars($d['nama']); ?></td>
                    <td><?php echo htmlspecialchars($d['email']); ?></td>
                    <td>
                        <span class="badge status_badge <?php echo $d['status']; ?>">
                            <?php echo ucfirst(str_replace('_',' ', $d['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php echo $d['batas_revisi'] ?? '-'; ?>
                    </td>
                    <td>
                        <a href="admin_lihat_biodata.php?id=<?php echo $d['id_peserta']; ?>"
                           class="btn btn-sm btn-primary mb-1">Biodata</a>
                        <a href="admin_lihat_dokumen.php?id=<?php echo $d['id_peserta']; ?>"
                           class="btn btn-sm btn-info mb-1">Dokumen</a>
                        <a href="admin_status_siswa.php?id=<?php echo $d['id_peserta']; ?>"
                           class="btn btn-sm btn-warning mb-1">Ubah Status</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>