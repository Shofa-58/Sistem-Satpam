<?php
session_start();
include "koneksi.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != "siswa"){
    header("location:login.php");
    exit;
}

$id_akun = $_SESSION['id_akun'];

$siswa = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT s.*
    FROM siswa s
    WHERE s.id_akun = '$id_akun'
"));

if(!$siswa){
    echo "Data tidak ditemukan.";
    exit;
}

$id_siswa = $siswa['id_peserta'];

$periode = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT pd.*
    FROM peserta_periode pp
    JOIN periode_diklat pd ON pp.id_periode = pd.id_periode
    WHERE pp.id_peserta = '$id_siswa'
"));

$jadwal = null;
if($periode){
    $jadwal = mysqli_query($conn,"
        SELECT * FROM jadwal_diklat
        WHERE id_periode = '{$periode['id_periode']}'
        ORDER BY tanggal ASC
    ");
}

/* Cek hasil evaluasi */
$evaluasi = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT * FROM evaluasi
    WHERE id_siswa = '$id_siswa'
    AND dikonfirmasi_admin = 1
    LIMIT 1
"));

/* Cek notifikasi revisi terbaru */
$notif = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT * FROM notifikasi
    WHERE id_siswa = '$id_siswa'
    ORDER BY tgl_kirim DESC
    LIMIT 1
"));

/* Cek dokumen yang perlu direvisi */
$dokRevisi = mysqli_query($conn,"
    SELECT jenis, catatan_admin
    FROM dokumen_pendaftaran
    WHERE id_siswa = '$id_siswa'
    AND status_verifikasi = 'revisi'
");
$adaRevisi = mysqli_num_rows($dokRevisi) > 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Siswa</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/dashboard_peserta.css">
</head>
<body>

<nav class="navbar navbar-dark" style="background-color: var(--navy);">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">Dashboard Siswa</span>
        <div class="d-flex gap-2">
            <a href="ganti_password.php" class="btn btn-sm btn-warning">Ganti Password</a>
            <a href="revisi_dokumen.php" class="btn btn-sm btn-primary">Revisi Dokumen</a>
            <a href="status_siswa.php" class="btn btn-sm btn-info">Status Siswa</a>
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <!-- NOTIFIKASI REVISI -->
    <?php if($adaRevisi): ?>
    <div class="alert alert-danger">
        <strong>⚠️ Dokumen Anda memerlukan revisi!</strong>
        <ul class="mt-2 mb-2">
        <?php
        mysqli_data_seek($dokRevisi, 0);
        while($r = mysqli_fetch_assoc($dokRevisi)){
            echo "<li><strong>" . strtoupper($r['jenis']) . "</strong>: " .
                 htmlspecialchars($r['catatan_admin'] ?? '-') . "</li>";
        }
        ?>
        </ul>
        <?php
        $batas = $siswa['batas_revisi'];
        if($batas && strtotime($batas) >= strtotime(date('Y-m-d'))):
        ?>
        <p class="mb-2">Batas revisi: <strong><?php echo $batas; ?></strong></p>
        <?php endif; ?>
        <a href="revisi_dokumen.php" class="btn btn-danger btn-sm">Upload Revisi Sekarang</a>
    </div>
    <?php endif; ?>

    <!-- INFO AKUN -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Informasi Akun</h5>
            <p class="mb-1"><strong>Nama &nbsp;&nbsp;&nbsp;:</strong> <?php echo htmlspecialchars($siswa['nama']); ?></p>
            <p class="mb-1"><strong>Username :</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            <p class="mb-0">
                <strong>Status &nbsp;&nbsp;:</strong>
                <span class="badge bg-secondary">
                    <?php echo ucfirst(str_replace('_', ' ', $siswa['status'])); ?>
                </span>
            </p>
        </div>
    </div>

    <!-- INFO DIKLAT -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Informasi Diklat</h5>

            <?php if($periode): ?>
                <p><strong>Periode :</strong>
                    <?php echo $periode['tahun']; ?> Gelombang <?php echo $periode['gelombang']; ?>
                </p>
                <p><strong>Tanggal :</strong>
                    <?php echo $periode['tanggal_mulai']; ?> - <?php echo $periode['tanggal_selesai']; ?>
                </p>
                <p><strong>Lokasi :</strong> <?php echo htmlspecialchars($periode['lokasi_spesifik']); ?></p>
                <p><strong>Lokasi Ambil Fasilitas :</strong>
                    <?php echo htmlspecialchars($periode['lokasi_fasilitas'] ?? '-'); ?>
                </p>
                <p><strong>Fasilitas :</strong><br>
                    <?php echo nl2br(htmlspecialchars($periode['fasilitas'])); ?>
                </p>
                <?php if($periode['info_kebutuhan']): ?>
                <p><strong>Yang Perlu Dibawa :</strong><br>
                    <?php echo nl2br(htmlspecialchars($periode['info_kebutuhan'])); ?>
                </p>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info mb-0">
                    Anda belum ditetapkan ke periode diklat.
                    Silakan tunggu konfirmasi dari admin setelah dokumen diverifikasi.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RUNDOWN -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Rundown Diklat</h5>
            <?php if($jadwal && mysqli_num_rows($jadwal) > 0): ?>
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
                    <?php while($j = mysqli_fetch_assoc($jadwal)): ?>
                        <tr>
                            <td><?php echo $j['tanggal']; ?></td>
                            <td><?php echo htmlspecialchars($j['kegiatan']); ?></td>
                            <td><?php echo htmlspecialchars($j['keterangan']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">Jadwal diklat belum tersedia.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- HASIL EVALUASI -->
    <?php if($evaluasi): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-3">Hasil Evaluasi Diklat</h5>

            <div class="row g-3 text-center mb-3">
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded">
                        <div class="fw-bold fs-4"><?php echo $evaluasi['nilai_teori']; ?></div>
                        <div class="text-muted small">Teori</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded">
                        <div class="fw-bold fs-4"><?php echo $evaluasi['nilai_fisik']; ?></div>
                        <div class="text-muted small">Fisik</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded">
                        <div class="fw-bold fs-4"><?php echo $evaluasi['nilai_disiplin']; ?></div>
                        <div class="text-muted small">Disiplin</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light rounded">
                        <div class="fw-bold fs-4"><?php echo $evaluasi['nilai_praktik']; ?></div>
                        <div class="text-muted small">Praktik</div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Rata-rata:</strong> <?php echo $evaluasi['rata_rata']; ?>
                </div>
                <span class="badge fs-6 <?php echo $evaluasi['hasil'] == 'lulus' ? 'bg-success' : 'bg-danger'; ?>">
                    <?php echo strtoupper(str_replace('_', ' ', $evaluasi['hasil'])); ?>
                </span>
            </div>

            <?php if($evaluasi['catatan']): ?>
            <div class="mt-3 p-3 bg-light rounded">
                <strong>Catatan:</strong><br>
                <?php echo htmlspecialchars($evaluasi['catatan']); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>