<?php
session_start();
include "../koneksi.php";
include "../helpers.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("location: dashboard_admin.php");
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

/* ============================================================
   PROSES UPDATE SEMUA DOKUMEN
   ============================================================ */
if (isset($_POST['simpan_semua'])) {

    foreach ($_POST['status_verifikasi'] as $id_dokumen => $status) {
        $catatan    = mysqli_real_escape_string($conn, $_POST['catatan_admin'][$id_dokumen]);
        $id_dokumen = (int) $id_dokumen;

        mysqli_query($conn,
            "UPDATE dokumen_pendaftaran
             SET status_verifikasi = '$status',
                 catatan_admin     = '$catatan'
             WHERE id_dokumen = '$id_dokumen'"
        );
    }

    /* Cek status keseluruhan dokumen setelah disimpan */
    $cek   = mysqli_query($conn, "SELECT status_verifikasi FROM dokumen_pendaftaran WHERE id_siswa='$id'");
    $total  = 0; $valid = 0; $revisi = 0;
    while ($row = mysqli_fetch_assoc($cek)) {
        $total++;
        if ($row['status_verifikasi'] === 'valid')  $valid++;
        if ($row['status_verifikasi'] === 'revisi') $revisi++;
    }

    /* Update status siswa otomatis berdasarkan hasil verifikasi */
    if ($valid === $total && $total > 0) {
        /* Semua valid → terverifikasi */
        mysqli_query($conn,
            "UPDATE siswa SET status='terverifikasi', batas_revisi=NULL WHERE id_peserta='$id'"
        );
        $msg  = urlencode("Semua dokumen valid. Siswa otomatis terverifikasi.");
        $type = "success";

    } elseif ($revisi > 0) {
        /* Ada revisi → notif email + set batas revisi 7 hari */
        $dataSiswa  = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT nama, email FROM siswa WHERE id_peserta='$id'"
        ));
        $batas_revisi = date('Y-m-d', strtotime('+7 days'));
        mysqli_query($conn, "UPDATE siswa SET batas_revisi='$batas_revisi' WHERE id_peserta='$id'");

        $catatanRevisi = mysqli_query($conn,
            "SELECT jenis, catatan_admin FROM dokumen_pendaftaran
             WHERE id_siswa='$id' AND status_verifikasi='revisi'"
        );
        $listRevisi = [];
        while ($r = mysqli_fetch_assoc($catatanRevisi)) {
            $listRevisi[] = strtoupper($r['jenis']) . ": " . ($r['catatan_admin'] ?: 'Perlu diperbaiki');
        }
        $pesanRevisi = implode("\n", $listRevisi);
        $emailBody   = "Dokumen Anda memerlukan revisi:\n\n" . $pesanRevisi
                     . "\n\nBatas waktu revisi: $batas_revisi"
                     . "\n\nSilakan login dan upload ulang dokumen sebelum batas waktu.";

        $terkirim = kirimEmailAkun($dataSiswa['email'], $dataSiswa['nama'], 'REVISI DOKUMEN', $emailBody);

        /* Simpan ke tabel notifikasi */
        $pesanDB     = mysqli_real_escape_string($conn, $emailBody);
        $statusKirim = $terkirim ? 'terkirim' : 'gagal';
        mysqli_query($conn,
            "INSERT INTO notifikasi (id_siswa, jenis, pesan, status_kirim)
             VALUES ('$id', 'revisi', '$pesanDB', '$statusKirim')"
        );

        /* Reset status siswa ke calon */
        mysqli_query($conn, "UPDATE siswa SET status='calon' WHERE id_peserta='$id'");

        $notifStatus = $terkirim ? "berhasil dikirim" : "gagal dikirim (cek SMTP)";
        $msg  = urlencode("Ada dokumen revisi. Batas revisi: $batas_revisi. Email $notifStatus.");
        $type = $terkirim ? "warning" : "error";

    } else {
        $msg  = urlencode("Perubahan disimpan. Masih ada dokumen pending.");
        $type = "info";
    }

    /* Redirect dengan pesan — hindari alert() native yang jelek */
    header("Location: admin_lihat_dokumen.php?id=$id&msg=$msg&type=$type");
    exit;
}

/* Ambil data siswa */
$siswa = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT nama, status, batas_revisi FROM siswa WHERE id_peserta='$id'"
));

/* Ambil dokumen siswa */
$dokumen = mysqli_query($conn,
    "SELECT * FROM dokumen_pendaftaran WHERE id_siswa='$id' ORDER BY created_at DESC"
);

/* Mapping nama label dokumen */
$label_map = [
    'ktp'             => 'KTP',
    'ijazah'          => 'Ijazah Terakhir',
    'kk'              => 'Kartu Keluarga',
    'skck'            => 'SKCK',
    'pembayaran'      => 'Bukti Pembayaran',
    'surat_kesehatan' => 'Surat Kesehatan',
];

/* Pesan flash dari URL (setelah redirect) */
$flash_msg  = $_GET['msg']  ?? '';
$flash_type = $_GET['type'] ?? 'info';

/* Cek evaluasi pending untuk badge navbar */
$pending_eval = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS jml FROM evaluasi WHERE dikonfirmasi_admin=0"
));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dokumen Siswa — Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar_top.css">
    <link rel="stylesheet" href="../css/admin_dokumen.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .dokumen-card { border-radius: 14px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.07); }
        .info-panel {
            background: #fff;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            font-size: 13px;
        }
    </style>
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="topnav">
    <a class="tn-brand" href="#"><img src="../img/logo.png" alt="Logo" style="height:28px;width:auto;margin-right:8px;vertical-align:middle;">Admin Gemilang</a>
    <div class="tn-links">
        <a href="dashboard_admin.php" class="active">👥 Data Peserta</a>
        <a href="admin_evaluasi.php">
            📊 Evaluasi
            <?php if ($pending_eval['jml'] > 0): ?>
                <span class="tn-badge"><?= $pending_eval['jml'] ?></span>
            <?php endif; ?>
        </a>
        <a href="admin_persiapan_diklat.php">📅 Persiapan Diklat</a>
        <a href="admin_status_siswa.php">🔄 Status Siswa</a>
        <a href="tambah_akun.php">👤 Buat Akun</a>
    </div>
    <div class="tn-user">
        <span class="tn-username">Admin: <span><?= htmlspecialchars($_SESSION['username']) ?></span></span>
        <button class="tn-logout" id="btnLogout">🚪 Logout</button>
    </div>
</nav>

<div class="page-wrapper">

    <!-- Judul & tombol kembali -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 style="color:var(--navy);font-weight:700;margin:0;">
                Dokumen: <?= htmlspecialchars($siswa['nama']) ?>
            </h4>
            <span style="font-size:13px;color:#6c757d;">
                Status saat ini: <strong><?= ucfirst(str_replace('_',' ',$siswa['status'])) ?></strong>
            </span>
        </div>
        <a href="dashboard_admin.php" class="btn btn-secondary btn-sm">← Kembali</a>
    </div>

    <!-- Info batas revisi jika ada -->
    <?php if ($siswa['batas_revisi']): ?>
    <div class="info-panel" style="border-left:4px solid #ffc107;background:#fffbf0;">
        ⏰ Batas revisi dokumen untuk siswa ini:
        <strong><?= $siswa['batas_revisi'] ?></strong>
        <?php
        $sisa = (int) round((strtotime($siswa['batas_revisi']) - strtotime(date('Y-m-d'))) / 86400);
        if ($sisa < 0)       echo " <span style='color:#dc3545;'>(sudah lewat)</span>";
        elseif ($sisa === 0) echo " <span style='color:#dc3545;'>(hari ini!)</span>";
        else                 echo " <span style='color:#198754;'>($sisa hari lagi)</span>";
        ?>
    </div>
    <?php endif; ?>

    <form method="POST">
        <?php $jumlah_dok = 0; while ($d = mysqli_fetch_assoc($dokumen)): $jumlah_dok++; ?>
        <div class="card shadow-sm mb-4 dokumen-card">
            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <h5 class="mb-2"><?= $label_map[$d['jenis']] ?? ucfirst($d['jenis']) ?></h5>
                    <span class="badge dokumen_badge <?= $d['status_verifikasi'] ?>">
                        <?= strtoupper($d['status_verifikasi']) ?>
                    </span>
                </div>

                <p class="text-muted small mb-1">Upload: <?= date('d M Y H:i', strtotime($d['tgl_upload'])) ?></p>
                <?php if ($d['tgl_revisi']): ?>
                <p class="text-muted small mb-1">
                    Re-upload (revisi): <?= date('d M Y H:i', strtotime($d['tgl_revisi'])) ?>
                </p>
                <?php endif; ?>

                <div class="mb-3">
                    <a href="../<?= htmlspecialchars($d['file_path']) ?>" target="_blank"
                       class="btn btn-outline-primary btn-sm">📄 Lihat File</a>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold" style="font-size:13px;">Status Verifikasi</label>
                        <select name="status_verifikasi[<?= $d['id_dokumen'] ?>]" class="form-select" required>
                            <option value="pending" <?= $d['status_verifikasi']==='pending'?'selected':'' ?>>⏳ Pending</option>
                            <option value="valid"   <?= $d['status_verifikasi']==='valid'?'selected':'' ?>>✅ Valid</option>
                            <option value="revisi"  <?= $d['status_verifikasi']==='revisi'?'selected':'' ?>>⚠️ Revisi</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-bold" style="font-size:13px;">
                            Catatan Admin
                            <span style="font-weight:400;color:#6c757d;">(wajib diisi jika revisi)</span>
                        </label>
                        <textarea name="catatan_admin[<?= $d['id_dokumen'] ?>]" class="form-control" rows="2"
                                  placeholder="Contoh: Foto tidak jelas, KTP expired, dll."
                        ><?= htmlspecialchars($d['catatan_admin'] ?? '') ?></textarea>
                    </div>
                </div>

            </div>
        </div>
        <?php endwhile; ?>

        <?php if ($jumlah_dok === 0): ?>
        <div class="text-center py-5 text-muted">
            <div style="font-size:48px;margin-bottom:12px;">📁</div>
            <p>Belum ada dokumen yang diupload oleh siswa ini.</p>
        </div>
        <?php else: ?>
        <div class="d-flex gap-2 justify-content-end mt-2">
            <a href="dashboard_admin.php" class="btn btn-secondary">Batal</a>
            <button type="submit" name="simpan_semua" class="btn btn-warning px-5 fw-bold">
                💾 Simpan & Kirim Notifikasi
            </button>
        </div>
        <p style="font-size:12px;color:#6c757d;text-align:right;margin-top:8px;">
            Jika ada dokumen revisi, batas perbaikan otomatis 7 hari dari sekarang & email terkirim ke siswa.
        </p>
        <?php endif; ?>
    </form>

</div><!-- End page-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
/* Tampilkan flash message dari URL (menggantikan alert() native yang jelek) */
<?php if ($flash_msg): ?>
Swal.fire({
    title: '<?= $flash_type === "success" ? "Berhasil!" : ($flash_type === "warning" ? "Perhatian" : "Info") ?>',
    text:  '<?= addslashes(urldecode($flash_msg)) ?>',
    icon:  '<?= $flash_type ?>',
    confirmButtonColor: '#0d1b2a'
});
<?php endif; ?>

/* Validasi: jika ada status 'revisi' yang dipilih, pastikan catatan diisi */
document.querySelector('button[name="simpan_semua"]')?.addEventListener('click', function(e) {
    let ada_revisi_kosong = false;
    document.querySelectorAll('select[name^="status_verifikasi"]').forEach(function(sel) {
        if (sel.value === 'revisi') {
            const id_dok = sel.name.match(/\[(\d+)\]/)[1];
            const textarea = document.querySelector(`textarea[name="catatan_admin[${id_dok}]"]`);
            if (!textarea || textarea.value.trim() === '') {
                ada_revisi_kosong = true;
                textarea.style.border = '2px solid #dc3545';
            }
        }
    });
    if (ada_revisi_kosong) {
        e.preventDefault();
        Swal.fire({
            title: 'Catatan Kosong!',
            text: 'Isi catatan untuk setiap dokumen yang berstatus Revisi.',
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    }
});

/* Konfirmasi logout */
document.getElementById('btnLogout').addEventListener('click', function() {
    Swal.fire({
        title: 'Keluar dari Sistem?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then(r => { if (r.isConfirmed) window.location.href = '../logout.php'; });
});
</script>
</body>
</html>