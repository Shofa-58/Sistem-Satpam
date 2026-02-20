<?php
include "koneksi.php";

$query = mysqli_query($conn, "SELECT * FROM informasi_diklat ORDER BY id_info DESC LIMIT 1");
$data  = mysqli_fetch_assoc($query);

/* ===== Function Angka ke Romawi ===== */
function toRoman($number) {
    $map = [
        'M'=>1000,'CM'=>900,'D'=>500,'CD'=>400,
        'C'=>100,'XC'=>90,'L'=>50,'XL'=>40,
        'X'=>10,'IX'=>9,'V'=>5,'IV'=>4,'I'=>1
    ];

    $return = '';
    while ($number > 0) {
        foreach ($map as $roman => $int) {
            if($number >= $int){
                $number -= $int;
                $return .= $roman;
                break;
            }
        }
    }
    return $return;
}

/* ===== Bulan Mapping ===== */
$bulan_list = [
    1=>"JANUARI",2=>"FEBRUARI",3=>"MARET",4=>"APRIL",
    5=>"MEI",6=>"JUNI",7=>"JULI",8=>"AGUSTUS",
    9=>"SEPTEMBER",10=>"OKTOBER",11=>"NOVEMBER",12=>"DESEMBER"
];

/* ===== Safe Data ===== */
$angkatan_romawi = isset($data['id_periode']) 
    ? toRoman($data['id_periode']) 
    : "-";

$bulan_estimasi = isset($data['estimasi_bulan']) 
    ? $bulan_list[$data['estimasi_bulan']] 
    : "-";

$tanggal_fix = !empty($data['tanggal_fix']) 
    ? date('d F Y', strtotime($data['tanggal_fix'])) 
    : "-";

$tempat = !empty($data['tempat']) 
    ? $data['tempat'] 
    : "Belum ditentukan";

$brosur = !empty($data['brosur_path']) 
    ? "uploads/".$data['brosur_path'] 
    : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Publikasi Diklat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#0d1b2a;
    color:white;
}
.section-card{
    background:#1b263b;
    border-radius:15px;
    padding:30px;
    margin-bottom:30px;
}
.section-title{
    color:#ffd60a;
    font-weight:bold;
    margin-bottom:20px;
}
.hero-title{
    font-weight:bold;
    font-size:1.5rem;
}
</style>
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container">

    <!-- Logo -->
    <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
      <img src="img/logo.png" alt="Logo Gemilang" 
           width="40" height="40"
           class="me-2">
      <span class="fw-bold">Gemilang</span>
    </a>

    <!-- Button Hamburger -->
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navmenu"
            aria-controls="navmenu"
            aria-expanded="false"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menu -->
    <div class="collapse navbar-collapse" id="navmenu">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php">Beranda</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="publikasi.php">Publikasi</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-warning fw-semibold" href="login.php">Login</a>
        </li>
      </ul>
    </div>

  </div>
</nav>

<div class="container" style="margin-top:120px; max-width:900px;">

    <!-- HERO -->
    <div class="text-center mb-5">
        <h2 class="hero-title">
            INFORMASI PELATIHAN SATPAM <br>
            ANGKATAN <?php echo $angkatan_romawi; ?> <br>
            PT TATA KARYA GEMILANG
        </h2>

        <?php if($brosur): ?>
            <img src="<?php echo $brosur; ?>" 
                 class="img-fluid rounded shadow mt-4">
        <?php endif; ?>
    </div>

    <!-- PELAKSANAAN -->
    <div class="section-card">
        <div class="section-title">I. PELAKSANAAN</div>
        <p>
            Tanggal Fix: <strong><?php echo $tanggal_fix; ?></strong><br>
            Estimasi Bulan: <strong><?php echo $bulan_estimasi; ?></strong>
        </p>
    </div>

    <!-- SYARAT -->
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

    <!-- KUALIFIKASI -->
    <div class="section-card">
        <div class="section-title">III. KUALIFIKASI</div>
        <ul>
            <li>Tidak bertindik</li>
            <li>Tidak bertato</li>
            <li>Siap mengikuti diklat</li>
            <li>Tidak tersangkut urusan hukum/narkoba</li>
        </ul>
    </div>

    <!-- FASILITAS -->
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

    <!-- TEMPAT -->
    <div class="section-card">
        <div class="section-title">V. TEMPAT PELATIHAN</div>
        <p><?php echo $tempat; ?></p>
    </div>

    <!-- BIAYA -->
    <div class="section-card">
        <div class="section-title">VI. BIAYA</div>
        <p>
            Nama Rekening: <strong>PT. GEMILANG LAYANAN PRIMA</strong><br>
            Bank: PERMATA<br>
            Nomor Rekening: 9977608888<br><br>
            <strong>Rp 3.800.000,-</strong>
        </p>
    </div>

    <!-- TAMBAHAN -->
    <div class="section-card">
        <div class="section-title">VII. TAMBAHAN</div>
        <ul>
            <li>Peserta wajib hadir saat verifikasi data</li>
            <li>Penutupan pendaftaran sewaktu-waktu</li>
            <li>Pelatihan wajib diikuti selama 10 hari</li>
        </ul>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>