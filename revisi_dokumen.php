<?php
session_start();
include "koneksi.php";

/* ============================================================
   GUARD: hanya siswa yang bisa akses
   ============================================================ */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: login.php");
    exit;
}

$id_akun = $_SESSION['id_akun'];

/* ============================================================
   AMBIL DATA SISWA
   ============================================================ */
$siswa = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id_peserta, nama, status, batas_revisi
     FROM siswa
     WHERE id_akun = '$id_akun'
     LIMIT 1"
));

if (!$siswa) {
    header("Location: dashboard_peserta.php");
    exit;
}

$id_siswa    = $siswa['id_peserta'];
$batas_revisi = $siswa['batas_revisi'];
$hari_ini    = date('Y-m-d');

/* Cek apakah masih dalam masa revisi */
$masih_bisa_revisi = true;
$sudah_kadaluarsa  = false;

if ($batas_revisi && strtotime($hari_ini) > strtotime($batas_revisi)) {
    $masih_bisa_revisi = false;
    $sudah_kadaluarsa  = true;
}

/* ============================================================
   AMBIL DOKUMEN YANG BERSTATUS REVISI
   ============================================================ */
$query_revisi = mysqli_query($conn,
    "SELECT * FROM dokumen_pendaftaran
     WHERE id_siswa = '$id_siswa'
     AND status_verifikasi = 'revisi'
     ORDER BY tgl_upload DESC"
);
$jumlah_revisi = mysqli_num_rows($query_revisi);

/* ============================================================
   PROSES UPLOAD ULANG (POST)
   ============================================================ */
$pesan_sukses = '';
$pesan_error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_revisi'])) {

    /* Tolak jika sudah kadaluarsa */
    if ($sudah_kadaluarsa) {
        $pesan_error = "Masa revisi sudah berakhir. Anda tidak dapat mengunggah dokumen lagi.";
        goto tampilForm;
    }

    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
    $folder      = "uploads/" . $id_siswa;
    if (!is_dir($folder)) mkdir($folder, 0777, true);

    $berhasil = 0;
    $gagal    = 0;

    /* Loop setiap jenis dokumen yang di-upload ulang */
    foreach ($_FILES as $field_name => $file_info) {

        /* Field upload dinamai: file_revisi_[jenis] misal: file_revisi_ktp */
        if (strpos($field_name, 'file_revisi_') !== 0) continue;
        if (empty($file_info['name'])) continue;

        $jenis = str_replace('file_revisi_', '', $field_name);
        $jenis = mysqli_real_escape_string($conn, $jenis);

        /* Validasi ekstensi */
        $ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_ext)) {
            $gagal++;
            continue;
        }

        /* Validasi ukuran (maks 5MB) */
        if ($file_info['size'] > 5 * 1024 * 1024) {
            $gagal++;
            continue;
        }

        /* Hapus file lama jika ada */
        $dokLama = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT file_path FROM dokumen_pendaftaran
             WHERE id_siswa = '$id_siswa' AND jenis = '$jenis'
             LIMIT 1"
        ));
        if ($dokLama && file_exists($dokLama['file_path'])) {
            @unlink($dokLama['file_path']);
        }

        /* Simpan file baru */
        $new_name  = $jenis . "." . $ext;
        $file_path = $folder . "/" . $new_name;

        if (move_uploaded_file($file_info['tmp_name'], $file_path)) {
            /* Update record: kembalikan ke pending, catat waktu revisi */
            $tgl_revisi = date("Y-m-d H:i:s");
            $file_path_safe = mysqli_real_escape_string($conn, $file_path);

            mysqli_query($conn,
                "UPDATE dokumen_pendaftaran
                 SET file_path          = '$file_path_safe',
                     status_verifikasi  = 'pending',
                     catatan_admin      = NULL,
                     tgl_revisi         = '$tgl_revisi'
                 WHERE id_siswa = '$id_siswa'
                 AND   jenis    = '$jenis'"
            );
            $berhasil++;
        } else {
            $gagal++;
        }
    }

    if ($berhasil > 0 && $gagal === 0) {
        $pesan_sukses = "Berhasil mengunggah $berhasil dokumen. "
                      . "Dokumen Anda sedang menunggu verifikasi ulang oleh admin.";
    } elseif ($berhasil > 0 && $gagal > 0) {
        $pesan_sukses = "$berhasil dokumen berhasil diunggah, $gagal gagal "
                      . "(periksa format dan ukuran file).";
    } else {
        $pesan_error = "Tidak ada dokumen yang berhasil diunggah. "
                     . "Pastikan format JPG, PNG, atau PDF dan ukuran maks 5MB.";
    }

    /* Refresh query setelah update */
    $query_revisi = mysqli_query($conn,
        "SELECT * FROM dokumen_pendaftaran
         WHERE id_siswa = '$id_siswa'
         AND status_verifikasi = 'revisi'
         ORDER BY tgl_upload DESC"
    );
    $jumlah_revisi = mysqli_num_rows($query_revisi);
}

tampilForm:
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Revisi Dokumen — Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/revisi_dokumen.css">
</head>
<body>

<!-- Navbar -->
<nav class="rev-navbar">
    <a href="dashboard_peserta.php" class="brand">← Kembali</a>
    <span style="color:#ccc;font-size:13px;">Revisi Dokumen</span>
</nav>

<div class="rev-container">

    <!-- =====================================================
         INFO SISWA
    ===================================================== -->
    <div class="rev-header-card">
        <h5>Dokumen perlu direvisi untuk</h5>
        <p class="siswa-nama"><?php echo htmlspecialchars($siswa['nama']); ?></p>
    </div>

    <!-- =====================================================
         ALERT PESAN
    ===================================================== -->
    <?php if ($pesan_sukses): ?>
    <div class="rev-alert-sukses">
        ✅ <?php echo htmlspecialchars($pesan_sukses); ?>
    </div>
    <?php endif; ?>

    <?php if ($pesan_error): ?>
    <div class="alert alert-danger" style="border-radius:10px;font-size:14px;">
        ⚠️ <?php echo htmlspecialchars($pesan_error); ?>
    </div>
    <?php endif; ?>

    <!-- =====================================================
         BATAS WAKTU REVISI
    ===================================================== -->
    <?php if ($batas_revisi): ?>
        <?php if ($sudah_kadaluarsa): ?>
        <div class="rev-alert-locked">
            <div class="lock-icon">🔒</div>
            <div class="lock-title">Masa Revisi Telah Berakhir</div>
            <div class="lock-desc">
                Batas waktu revisi adalah <strong><?php echo $batas_revisi; ?></strong>.<br>
                Anda tidak dapat mengunggah dokumen lagi. Hubungi admin untuk informasi lebih lanjut.
            </div>
        </div>
        <?php else: ?>
        <div class="rev-alert-deadline">
            <div class="deadline-title">⏰ Batas Waktu Revisi</div>
            <div class="deadline-info">
                Unggah dokumen sebelum <strong><?php echo $batas_revisi; ?></strong>.
                <?php
                $sisa_hari = (int) round((strtotime($batas_revisi) - strtotime($hari_ini)) / 86400);
                if ($sisa_hari === 0) {
                    echo "<strong style='color:#dc3545'> Hari ini batas terakhir!</strong>";
                } elseif ($sisa_hari === 1) {
                    echo "<strong style='color:#e67e22'> Sisa 1 hari lagi.</strong>";
                } else {
                    echo " Sisa <strong>$sisa_hari hari</strong> lagi.";
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- =====================================================
         KONDISI: TIDAK ADA DOKUMEN REVISI
    ===================================================== -->
    <?php if ($jumlah_revisi === 0 && !$pesan_sukses): ?>

    <div class="rev-no-revisi">
        <div class="no-rev-icon">✅</div>
        <h5>Tidak ada dokumen yang perlu direvisi</h5>
        <p>Semua dokumen Anda sudah dalam status pending atau valid.</p>
        <a href="dashboard_peserta.php" class="rev-btn-back">Kembali ke Dashboard</a>
    </div>

    <?php elseif ($jumlah_revisi === 0 && $pesan_sukses): ?>

    <div class="rev-no-revisi">
        <div class="no-rev-icon">🎉</div>
        <h5>Semua revisi telah dikirim!</h5>
        <p>Dokumen Anda sedang dalam proses verifikasi ulang oleh admin. Pantau status di dashboard.</p>
        <a href="dashboard_peserta.php" class="rev-btn-back">Kembali ke Dashboard</a>
    </div>

    <?php else: ?>

    <!-- =====================================================
         PANDUAN
    ===================================================== -->
    <div class="rev-panduan">
        <strong>📋 Cara upload revisi:</strong>
        <ol>
            <li>Baca catatan admin di setiap dokumen</li>
            <li>Pilih file baru (JPG, PNG, atau PDF, maks 5MB)</li>
            <li>Tap tombol <strong>Kirim Revisi</strong> di bawah</li>
        </ol>
    </div>

    <!-- Counter -->
    <div class="rev-counter">
        <span><?php echo $jumlah_revisi; ?></span> dokumen perlu direvisi
    </div>

    <!-- =====================================================
         FORM UPLOAD REVISI
    ===================================================== -->
    <?php if (!$sudah_kadaluarsa): ?>
    <form method="POST" enctype="multipart/form-data" id="formRevisi">

        <?php
        /* Reset pointer result set */
        mysqli_data_seek($query_revisi, 0);
        while ($dok = mysqli_fetch_assoc($query_revisi)):
        ?>

        <div class="rev-dok-card">

            <!-- Judul & badge -->
            <div class="rev-dok-jenis">
                <span class="jenis-nama">
                    <?php
                    $label_map = [
                        'ktp'          => 'KTP',
                        'ijazah'       => 'Ijazah Terakhir',
                        'kk'           => 'Kartu Keluarga',
                        'skck'         => 'SKCK',
                        'pembayaran'   => 'Bukti Pembayaran',
                        'surat_kesehatan' => 'Surat Kesehatan',
                    ];
                    echo $label_map[$dok['jenis']] ?? ucfirst($dok['jenis']);
                    ?>
                </span>
                <span class="rev-dok-badge revisi">⚠️ Revisi</span>
            </div>

            <!-- Catatan admin -->
            <?php if ($dok['catatan_admin']): ?>
            <div class="rev-catatan-admin">
                <div class="catatan-label">Catatan Admin</div>
                <p class="catatan-isi"><?php echo htmlspecialchars($dok['catatan_admin']); ?></p>
            </div>
            <?php else: ?>
            <div class="rev-catatan-admin">
                <div class="catatan-label">Catatan Admin</div>
                <p class="catatan-isi">Dokumen tidak memenuhi persyaratan. Mohon upload ulang.</p>
            </div>
            <?php endif; ?>

            <!-- Lihat file lama -->
            <div class="rev-file-lama">
                <span class="file-icon">📄</span>
                <a href="<?php echo htmlspecialchars($dok['file_path']); ?>"
                   target="_blank">
                   Lihat file sebelumnya
                </a>
                <small style="color:#aaa;font-size:12px;">
                    (upload: <?php echo date('d/m/Y', strtotime($dok['tgl_upload'])); ?>)
                </small>
            </div>

            <!-- Upload baru -->
            <div class="rev-upload-wrap">
                <label>Unggah file baru untuk
                    <em><?php echo $label_map[$dok['jenis']] ?? ucfirst($dok['jenis']); ?></em>
                    <span style="color:#dc3545">*</span>
                </label>
                <input type="file"
                       name="file_revisi_<?php echo $dok['jenis']; ?>"
                       id="file_<?php echo $dok['jenis']; ?>"
                       accept=".jpg,.jpeg,.png,.pdf"
                       required
                       onchange="previewNamaFile(this, '<?php echo $dok['jenis']; ?>')">
                <div class="upload-hint">
                    Format: JPG, PNG, PDF · Maks 5MB
                </div>
                <div id="preview_<?php echo $dok['jenis']; ?>"
                     style="display:none;font-size:13px;color:#198754;margin-top:6px;padding:8px;
                            background:#d1e7dd;border-radius:8px;">
                </div>
            </div>

        </div>

        <?php endwhile; ?>

        <!-- Tombol submit -->
        <button type="submit"
                name="submit_revisi"
                class="rev-btn-submit"
                id="btnSubmit">
            Kirim Revisi Dokumen
        </button>

        <p style="text-align:center;font-size:12px;color:#6c757d;margin-top:10px;">
            Pastikan semua file sudah dipilih sebelum mengirim.
        </p>

    </form>

    <?php else: ?>
    <!-- Kadaluarsa: tampilkan daftar tapi nonaktif -->
    <?php
    mysqli_data_seek($query_revisi, 0);
    while ($dok = mysqli_fetch_assoc($query_revisi)):
    ?>
    <div class="rev-dok-card" style="opacity:0.6;">
        <div class="rev-dok-jenis">
            <span class="jenis-nama">
                <?php
                $label_map = [
                    'ktp'          => 'KTP',
                    'ijazah'       => 'Ijazah Terakhir',
                    'kk'           => 'Kartu Keluarga',
                    'skck'         => 'SKCK',
                    'pembayaran'   => 'Bukti Pembayaran',
                    'surat_kesehatan' => 'Surat Kesehatan',
                ];
                echo $label_map[$dok['jenis']] ?? ucfirst($dok['jenis']);
                ?>
            </span>
            <span class="rev-dok-badge revisi">🔒 Terkunci</span>
        </div>
        <?php if ($dok['catatan_admin']): ?>
        <div class="rev-catatan-admin">
            <div class="catatan-label">Catatan Admin</div>
            <p class="catatan-isi"><?php echo htmlspecialchars($dok['catatan_admin']); ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>

    <?php endif; /* end sudah_kadaluarsa */ ?>

    <?php endif; /* end jumlah_revisi */ ?>

    <!-- Tombol kembali selalu tampil -->
    <?php if ($jumlah_revisi > 0): ?>
    <a href="dashboard_peserta.php" class="rev-btn-back" style="margin-top:16px;">
        Kembali ke Dashboard
    </a>
    <?php endif; ?>

    <!-- Jarak bawah agar tidak terpotong di HP -->
    <div style="height:40px;"></div>

</div><!-- /rev-container -->

<script>
/* Preview nama file setelah dipilih */
function previewNamaFile(input, jenis) {
    var el = document.getElementById('preview_' + jenis);
    if (input.files && input.files[0]) {
        var f    = input.files[0];
        var size = (f.size / 1024).toFixed(0);

        /* Validasi ukuran di sisi client */
        if (f.size > 5 * 1024 * 1024) {
            el.style.background = '#f8d7da';
            el.style.color      = '#842029';
            el.innerHTML        = '❌ File terlalu besar (' + (f.size/1024/1024).toFixed(1) + ' MB). Maks 5MB.';
            el.style.display    = 'block';
            input.value         = '';
            return;
        }

        el.style.background = '#d1e7dd';
        el.style.color      = '#0a3622';
        el.innerHTML        = '✅ ' + f.name + ' (' + size + ' KB) — siap diunggah';
        el.style.display    = 'block';
    }
}

/* Konfirmasi sebelum submit */
document.getElementById('formRevisi')?.addEventListener('submit', function(e) {
    var btn = document.getElementById('btnSubmit');
    btn.disabled    = true;
    btn.textContent = 'Mengunggah... mohon tunggu';
});
</script>

</body>
</html>
