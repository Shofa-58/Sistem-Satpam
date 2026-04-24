<?php
include "../koneksi.php";

/*
 * Ambil informasi diklat berdasarkan periode paling relevan:
 * Prioritas 1 — periode berjalan saat ini
 * Prioritas 2 — periode yang sedang buka pendaftaran
 * Prioritas 3 — periode paling baru (fallback)
 */

/* Prioritas 1: pendaftaran */
$query = mysqli_query($conn, "
        SELECT i.*, p.tanggal_mulai, p.tanggal_selesai, p.status, p.biaya
        FROM informasi_diklat i
        JOIN periode_diklat p ON i.id_periode = p.id_periode
        WHERE p.status = 'pendaftaran'
        ORDER BY p.tanggal_mulai ASC
        LIMIT 1
    ");
$data = mysqli_fetch_assoc($query);

/* Prioritas 2: berjalan */
if (!$data) {
$query = mysqli_query($conn, "
    SELECT i.*, p.tanggal_mulai, p.tanggal_selesai, p.status, p.biaya
    FROM informasi_diklat i
    JOIN periode_diklat p ON i.id_periode = p.id_periode
    WHERE p.status = 'berjalan'
    ORDER BY p.tanggal_mulai DESC
    LIMIT 1
");
    $data = mysqli_fetch_assoc($query);
}

/* Prioritas 3: periode paling baru apapun statusnya */
if (!$data) {
    $query = mysqli_query($conn, "
        SELECT i.*, p.tanggal_mulai, p.tanggal_selesai, p.status, p.biaya
        FROM informasi_diklat i
        JOIN periode_diklat p ON i.id_periode = p.id_periode
        ORDER BY p.tanggal_mulai DESC
        LIMIT 1
    ");
    $data = mysqli_fetch_assoc($query);
}

/* ===== Angka ke Romawi ===== */
function toRoman($number) {
    $map = [
        'M'=>1000,'CM'=>900,'D'=>500,'CD'=>400,
        'C'=>100,'XC'=>90,'L'=>50,'XL'=>40,
        'X'=>10,'IX'=>9,'V'=>5,'IV'=>4,'I'=>1
    ];
    $return = '';
    while ($number > 0) {
        foreach ($map as $roman => $int) {
            if ($number >= $int) {
                $number -= $int;
                $return .= $roman;
                break;
            }
        }
    }
    return $return;
}

/* ===== Nama bulan Indonesia ===== */
$bulan_list = [
    1=>"JANUARI", 2=>"FEBRUARI", 3=>"MARET",    4=>"APRIL",
    5=>"MEI",     6=>"JUNI",     7=>"JULI",      8=>"AGUSTUS",
    9=>"SEPTEMBER",10=>"OKTOBER",11=>"NOVEMBER", 12=>"DESEMBER"
];

/* ===== Siapkan variabel tampilan ===== */
$angkatan_romawi = ($data && isset($data['id_periode']))
    ? toRoman($data['id_periode'])
    : "-";

/* Estimasi bulan diambil dari tanggal_mulai, bukan kolom estimasi_bulan yang tidak ada */
$bulan_estimasi = ($data && !empty($data['tanggal_mulai']))
    ? $bulan_list[(int) date('n', strtotime($data['tanggal_mulai']))]
    : "-";

$tanggal_mulai = ($data && !empty($data['tanggal_mulai']))
    ? date('d F Y', strtotime($data['tanggal_mulai']))
    : "-";

$tanggal_selesai = ($data && !empty($data['tanggal_selesai']))
    ? date('d F Y', strtotime($data['tanggal_selesai']))
    : "-";

$tempat = ($data && !empty($data['tempat']))
    ? htmlspecialchars($data['tempat'])
    : "Belum ditentukan";

$brosur = ($data && !empty($data['brosur_path']))
    ? "../uploads/" . $data['brosur_path']
    : null;

/* Badge status periode */
$status_label = [
    'pendaftaran' => ['teks' => 'Pendaftaran Dibuka', 'warna' => '#198754'],
    'berjalan'    => ['teks' => 'Sedang Berjalan',    'warna' => '#0d6efd'],
    'selesai'     => ['teks' => 'Sudah Selesai',      'warna' => '#6c757d'],
];
$status_p = $data['status'] ?? 'selesai';
$badge    = $status_label[$status_p] ?? $status_label['selesai'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publikasi Diklat — Gemilang</title>
    <link rel="stylesheet" href="../css/base.css">
    <link rel="stylesheet" href="../css/publikasi.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="publikasi-page">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="../dashboard_umum.php">
            <img src="../img/logo.png" alt="Logo Gemilang" width="40" height="40" class="me-2">
            <span class="fw-bold">Gemilang</span>
        </a>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navmenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navmenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../dashboard_umum.php">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="publikasi.php">Publikasi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-warning fw-semibold" href="../login.php">Login</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container publikasi-container">

    <?php if (!$data): ?>
    <!-- Belum ada data publikasi sama sekali -->
    <div class="text-center py-5">
        <div style="font-size:56px;margin-bottom:16px;">📋</div>
        <h4 style="color:#495057;">Belum Ada Informasi Diklat</h4>
        <p style="color:#6c757d;">Informasi program diklat akan ditampilkan di sini setelah dipublikasikan oleh admin.</p>
        <a href="../login.php" class="btn btn-warning fw-bold mt-2">Login ke Sistem</a>
    </div>

    <?php else: ?>

    <!-- HERO -->
    <div class="text-center mb-4">
        <!-- Badge status periode -->
        <span style="display:inline-block;background:<?= $badge['warna'] ?>;color:#fff;
                     padding:5px 18px;border-radius:20px;font-size:13px;font-weight:600;
                     margin-bottom:16px;">
            <?= $badge['teks'] ?>
        </span>

        <h2 class="hero-title">
            INFORMASI PELATIHAN SATPAM <br>
            ANGKATAN <?= $angkatan_romawi ?> <br>
            PT TATA KARYA GEMILANG
        </h2>

        <?php if ($brosur): ?>
        <img src="<?= $brosur ?>" class="img-fluid rounded shadow mt-4"
             alt="Brosur Diklat Angkatan <?= $angkatan_romawi ?>">
        <?php endif; ?>
    </div>

    <!-- I. PELAKSANAAN -->
    <div class="section-card">
        <div class="section-title">I. PELAKSANAAN</div>
        <p>
            Tanggal: <strong><?= $tanggal_mulai ?> — <?= $tanggal_selesai ?></strong><br>
            Estimasi Bulan: <strong><?= $bulan_estimasi ?></strong>
        </p>
    </div>

    <!-- II. SYARAT -->
    <div class="section-card">
        <div class="section-title">II. SYARAT-SYARAT</div>
        <ul>
            <li>WNI</li>
            <li>Tinggi min. 165 cm (pria), 160 cm (wanita)</li>
            <li>Berat badan proporsional</li>
            <li>Fotocopy Ijazah terakhir min. SMK/SLTA/Kejar Paket C (2 lembar)</li>
            <li>Fotocopy SKCK Aktif (2 lembar)</li>
            <li>Fotocopy KTP (2 lembar)</li>
            <li>Bukti transfer pembayaran DP minimal 500K</li>
            <li>Membayar biaya tes kesehatan dan narkoba Rp 170.000 saat verifikasi</li>
        </ul>
    </div>

    <!-- III. KUALIFIKASI -->
    <div class="section-card">
        <div class="section-title">III. KUALIFIKASI</div>
        <ul>
            <li>Tidak bertindik</li>
            <li>Tidak bertato</li>
            <li>Siap mengikuti diklat</li>
            <li>Tidak tersangkut urusan hukum/narkoba</li>
        </ul>
    </div>

    <!-- IV. FASILITAS -->
    <div class="section-card">
        <div class="section-title">IV. FASILITAS</div>
        <ul>
            <li>Kaos diklat</li>
            <li>Seragam PDH</li>
            <li>KTA SATPAM</li>
            <li>Ijazah GADA PRATAMA</li>
            <li>Pin GADA PRATAMA</li>
            <li>Dibantu proses penempatan kerja</li>
            <li>Selama diklat makan 1x sehari, snack 1x, 1 teh jumbo</li>
            <li>Mess/Penginapan gratis</li>
        </ul>
    </div>

    <!-- V. TEMPAT -->
    <div class="section-card">
        <div class="section-title">V. TEMPAT PELATIHAN</div>
        <p><?= $tempat ?></p>
    </div>

    <!-- VI. BIAYA -->
    <div class="section-card">
        <div class="section-title">VI. BIAYA</div>
        <p>
            Nama Rekening: <strong>PT. GEMILANG LAYANAN PRIMA</strong><br>
            Bank: PERMATA<br>
            Nomor Rekening: 9977608888<br><br>
            <strong>Rp 3.800.000,-</strong>
        </p>
    </div>

    <!-- VII. TAMBAHAN -->
    <div class="section-card">
        <div class="section-title">VII. TAMBAHAN</div>
        <ul>
            <li>Peserta wajib hadir saat verifikasi data</li>
            <li>Penutupan pendaftaran sewaktu-waktu</li>
            <li>Pelatihan wajib diikuti selama 10 hari</li>
        </ul>
    </div>

    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>