<?php
session_start();
include "koneksi.php";
include "helpers.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] != "admin"){
    header("location:login.php");
    exit;
}

if(!isset($_GET['id'])){
    header("location:dashboard_admin.php");
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

/* PROSES UPDATE SEMUA DOKUMEN */
if(isset($_POST['simpan_semua'])){

    foreach($_POST['status_verifikasi'] as $id_dokumen => $status){
        $catatan   = mysqli_real_escape_string($conn, $_POST['catatan_admin'][$id_dokumen]);
        $id_dokumen = (int) $id_dokumen;

        mysqli_query($conn, "
            UPDATE dokumen_pendaftaran
            SET status_verifikasi = '$status',
                catatan_admin     = '$catatan'
            WHERE id_dokumen = '$id_dokumen'
        ");
    }

    /* CEK STATUS DOKUMEN */
    $cek   = mysqli_query($conn,
        "SELECT status_verifikasi FROM dokumen_pendaftaran WHERE id_siswa='$id'"
    );
    $total  = 0; $valid = 0; $revisi = 0;

    while($row = mysqli_fetch_assoc($cek)){
        $total++;
        if($row['status_verifikasi'] == 'valid')  $valid++;
        if($row['status_verifikasi'] == 'revisi') $revisi++;
    }

    /* UPDATE STATUS SISWA */
    if($valid == $total && $total > 0){
        mysqli_query($conn,
            "UPDATE siswa SET status='terverifikasi' WHERE id_peserta='$id'"
        );
        $pesan = "Semua dokumen valid. Siswa otomatis terverifikasi.";

    } elseif($revisi > 0){
        /* Ambil data siswa untuk kirim email */
        $dataSiswa = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT nama, email FROM siswa WHERE id_peserta='$id'"
        ));

        /* Kumpulkan catatan dokumen revisi */
        $catatanRevisi = mysqli_query($conn,
            "SELECT jenis, catatan_admin
             FROM dokumen_pendaftaran
             WHERE id_siswa='$id' AND status_verifikasi='revisi'"
        );
        $listRevisi = [];
        while($r = mysqli_fetch_assoc($catatanRevisi)){
            $listRevisi[] = strtoupper($r['jenis']) . ": " . $r['catatan_admin'];
        }
        $pesanRevisi = implode("\n", $listRevisi);

        /* Kirim notifikasi email ke siswa */
        $emailBody = "Dokumen Anda memerlukan revisi:\n\n" . $pesanRevisi .
                     "\n\nSilakan login dan upload ulang dokumen sebelum batas waktu revisi.";

        $terkirim = kirimEmailAkun(
            $dataSiswa['email'],
            $dataSiswa['nama'],
            'REVISI DOKUMEN',
            $emailBody
        );

        /* Simpan ke tabel notifikasi */
        $pesanDB = mysqli_real_escape_string($conn, $emailBody);
        $statusKirim = $terkirim ? 'terkirim' : 'gagal';
        mysqli_query($conn,
            "INSERT INTO notifikasi (id_siswa, jenis, pesan, status_kirim)
             VALUES ('$id', 'revisi', '$pesanDB', '$statusKirim')"
        );

        /* Reset status siswa ke calon */
        mysqli_query($conn,
            "UPDATE siswa SET status='calon' WHERE id_peserta='$id'"
        );

        $pesan = "Ada dokumen revisi. Notifikasi " .
                 ($terkirim ? "berhasil" : "gagal") . " dikirim ke email siswa.";

    } else {
        $pesan = "Perubahan disimpan. Masih ada dokumen pending.";
    }

    echo "<script>
            alert('$pesan');
            window.location='admin_lihat_dokumen.php?id=$id';
          </script>";
    exit;
}

/* Ambil nama siswa */
$siswa = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT nama FROM siswa WHERE id_peserta='$id'"
));

/* Ambil dokumen */
$dokumen = mysqli_query($conn,
    "SELECT * FROM dokumen_pendaftaran
     WHERE id_siswa='$id'
     ORDER BY created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dokumen Siswa - Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/admin_dokumen.css">
</head>
<body>

<nav class="navbar navbar-dark" style="background-color: var(--navy);">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dashboard_admin.php">Admin Gemilang</a>
    </div>
</nav>

<div class="container my-5">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h3>Dokumen: <?php echo htmlspecialchars($siswa['nama']); ?></h3>
        <a href="dashboard_admin.php" class="btn btn-secondary">Kembali</a>
    </div>

    <form method="POST">
        <?php while($d = mysqli_fetch_assoc($dokumen)){ ?>
        <div class="card shadow-sm mb-4 dokumen-card">
            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <h5 class="mb-2 text-capitalize"><?php echo $d['jenis']; ?></h5>
                    <span class="badge dokumen_badge <?php echo $d['status_verifikasi']; ?>">
                        <?php echo strtoupper($d['status_verifikasi']); ?>
                    </span>
                </div>

                <p class="text-muted small mb-1">
                    Upload: <?php echo $d['tgl_upload']; ?>
                </p>
                <?php if($d['tgl_revisi']): ?>
                <p class="text-muted small">
                    Revisi: <?php echo $d['tgl_revisi']; ?>
                </p>
                <?php endif; ?>

                <div class="mb-3">
                    <a href="<?php echo $d['file_path']; ?>"
                       target="_blank"
                       class="btn btn-outline-primary btn-sm">
                       Lihat File
                    </a>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status_verifikasi[<?php echo $d['id_dokumen']; ?>]"
                                class="form-select" required>
                            <option value="pending" <?php if($d['status_verifikasi']=='pending') echo 'selected'; ?>>Pending</option>
                            <option value="valid"   <?php if($d['status_verifikasi']=='valid')   echo 'selected'; ?>>Valid</option>
                            <option value="revisi"  <?php if($d['status_verifikasi']=='revisi')  echo 'selected'; ?>>Revisi</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Catatan Admin</label>
                        <textarea name="catatan_admin[<?php echo $d['id_dokumen']; ?>]"
                                  class="form-control" rows="2"><?php echo htmlspecialchars($d['catatan_admin'] ?? ''); ?></textarea>
                    </div>
                </div>

            </div>
        </div>
        <?php } ?>

        <div class="text-end">
            <button type="submit" name="simpan_semua" class="btn btn-warning px-4">
                Simpan Semua &amp; Kirim Notifikasi
            </button>
        </div>
    </form>

</div>

</body>
</html>