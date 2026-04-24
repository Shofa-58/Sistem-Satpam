<?php
session_start();
include "koneksi.php";

/* Semua role yang login bisa ganti password */
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$id_akun = $_SESSION['id_akun'];
$role    = $_SESSION['role'];
$pesan   = '';
$tipe    = '';

/* Link kembali ke dashboard masing-masing role */
$back_link = match($role) {
    'siswa'           => 'siswa/dashboard_peserta.php',
    'admin'           => 'admin/dashboard_admin.php',
    'ceo'             => 'ceo/dashboard_ceo.php',
    'kepala_keamanan' => 'kepala/dashboard_kepala.php',
    'polda'           => 'polda/dashboard_polda.php',
    'publikasi'       => 'publikasi/dashboard_publikasi.php',
    default           => 'login.php'
};

/* Label role yang lebih ramah */
$role_label = match($role) {
    'siswa'           => 'Siswa',
    'admin'           => 'Admin',
    'ceo'             => 'CEO',
    'kepala_keamanan' => 'Kepala Keamanan',
    'polda'           => 'Polda DIY',
    'publikasi'       => 'Publikasi',
    default           => ucfirst($role)
};

/* Badge evaluasi pending khusus untuk admin */
$pending_eval_jml = 0;
if ($role === 'admin') {
    $pe = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS jml FROM evaluasi WHERE dikonfirmasi_admin=0"));
    $pending_eval_jml = (int) ($pe['jml'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password_lama  = trim($_POST['password_lama']);
    $password_baru  = trim($_POST['password_baru']);
    $konfirmasi     = trim($_POST['konfirmasi']);

    /* Ambil password sekarang */
    $akun = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT password FROM akun WHERE id_akun='$id_akun' LIMIT 1"
    ));

    /* Validasi bertahap */
    if ($password_lama !== $akun['password']) {
        $pesan = "Password lama tidak sesuai!";
        $tipe  = "danger";
    } elseif (strlen($password_baru) < 6) {
        $pesan = "Password baru minimal 6 karakter!";
        $tipe  = "danger";
    } elseif (!preg_match('/[A-Z]/', $password_baru)) {
        $pesan = "Password baru harus mengandung minimal 1 huruf kapital!";
        $tipe  = "danger";
    } elseif (!preg_match('/[0-9]/', $password_baru)) {
        $pesan = "Password baru harus mengandung minimal 1 angka!";
        $tipe  = "danger";
    } elseif ($password_baru !== $konfirmasi) {
        $pesan = "Konfirmasi password tidak cocok!";
        $tipe  = "danger";
    } elseif ($password_baru === $password_lama) {
        $pesan = "Password baru tidak boleh sama dengan password lama!";
        $tipe  = "danger";
    } else {
        /* Simpan password baru (plaintext sesuai sistem yang ada) */
        $pw_esc = mysqli_real_escape_string($conn, $password_baru);
        mysqli_query($conn, "UPDATE akun SET password='$pw_esc' WHERE id_akun='$id_akun'");
        $pesan = "Password berhasil diubah!";
        $tipe  = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ganti Password — Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <?php if ($role !== 'siswa'): ?>
    <!-- Untuk role selain siswa, pakai topnav PC -->
    <link rel="stylesheet" href="css/navbar_top.css">
    <?php else: ?>
    <!-- Siswa pakai topbar standar -->
    <link rel="stylesheet" href="css/dashboard_layout.css">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .ganti-card {
            background: #fff;
            border-radius: 16px;
            padding: 36px 40px;
            box-shadow: 0 6px 24px rgba(0,0,0,0.08);
            max-width: 480px;
            margin: 0 auto;
        }
        .ganti-card h4 {
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 6px;
        }
        .ganti-card .subtitle { font-size: 13px; color: #888; margin-bottom: 24px; }
        .form-label            { font-weight: 600; font-size: 13px; }
        .syarat-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #555;
        }
        .syarat-box ul { margin: 6px 0 0; padding-left: 18px; }
        .syarat-box li { margin-bottom: 4px; }
        .btn-ganti {
            background: var(--navy);
            color: #fff;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            padding: 11px;
            width: 100%;
            font-size: 14px;
            transition: 0.2s;
        }
        .btn-ganti:hover { background: #1b263b; }

        /* Wrapper konten untuk topnav role */
        .gp-content {
            padding: 36px 32px;
            min-height: calc(100vh - 60px);
        }

        /* Siswa: standar tanpa topnav */
        .gp-simple { padding: 40px 20px; }
    </style>
</head>
<body>

<?php if ($role !== 'siswa'): ?>
<!-- ===== TOP NAVBAR (role selain siswa) ===== -->
<nav class="topnav">
    <a class="tn-brand" href="<?= $back_link ?>">
        <img src="img/logo.png" alt="Logo" style="height:28px;width:auto;margin-right:8px;vertical-align:middle;">
        <?php
        echo match($role) {
            'admin'           => 'Admin Gemilang',
            'ceo'             => 'CEO Gemilang',
            'kepala_keamanan' => 'Kepala Keamanan',
            'polda'           => 'Polda DIY',
            'publikasi'       => 'Publikasi',
            default           => 'Gemilang'
        };
        ?>
    </a>
    <div class="tn-links">
        <?php if ($role === 'admin'): ?>
        <a href="admin/dashboard_admin.php">👥 Data Peserta</a>
        <a href="admin/admin_evaluasi.php">
            📊 Evaluasi
            <?php if ($pending_eval_jml > 0): ?>
                <span class="tn-badge"><?= $pending_eval_jml ?></span>
            <?php endif; ?>
        </a>
        <a href="admin/admin_persiapan_diklat.php">📅 Persiapan Diklat</a>
        <a href="admin/admin_status_siswa.php">🔄 Status Siswa</a>
        <a href="admin/tambah_akun.php">👤 Buat Akun</a>
        <?php elseif ($role === 'ceo'): ?>
        <a href="ceo/dashboard_ceo.php">📊 Dashboard Eksekutif</a>
        <?php elseif ($role === 'kepala_keamanan'): ?>
        <a href="kepala/dashboard_kepala.php">🛡️ Dashboard Kepala</a>
        <?php elseif ($role === 'polda'): ?>
        <a href="polda/dashboard_polda.php">📋 Dashboard Polda</a>
        <?php elseif ($role === 'publikasi'): ?>
        <a href="publikasi/dashboard_publikasi.php">📰 Data Publikasi</a>
        <?php endif; ?>
        <a href="ganti_password.php" class="active">🔒 Ganti Password</a>
    </div>
    <div class="tn-user">
        <span class="tn-username"><?= $role_label ?>: <span><?= htmlspecialchars($_SESSION['username']) ?></span></span>
        <button class="tn-logout" id="btnLogout">🚪 Logout</button>
    </div>
</nav>
<div class="gp-content">
<?php else: ?>
<!-- ===== TOPBAR SEDERHANA UNTUK SISWA ===== -->
<nav class="navbar navbar-dark" style="background-color:var(--navy);padding:0 20px;height:56px;display:flex;align-items:center;justify-content:space-between;">
    <div style="display:flex;align-items:center;gap:10px;">
        <img src="img/logo.png" alt="Logo" style="height:28px;">
        <span style="color:var(--yellow);font-weight:700;font-size:15px;">Gemilang</span>
    </div>
    <a href="<?= $back_link ?>" style="color:#fff;font-size:13px;text-decoration:none;">← Kembali ke Dashboard</a>
</nav>
<div class="gp-simple">
<?php endif; ?>

    <div class="ganti-card">
        <h4>🔒 Ganti Password</h4>
        <p class="subtitle">
            Halo, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
            — Role: <?= $role_label ?>
        </p>

        <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipe ?> py-2 mb-3" style="font-size:13px;border-radius:10px;">
            <?= htmlspecialchars($pesan) ?>
        </div>
        <?php endif; ?>

        <div class="syarat-box">
            <strong>Syarat password baru:</strong>
            <ul>
                <li>Minimal 6 karakter</li>
                <li>Mengandung minimal 1 huruf kapital</li>
                <li>Mengandung minimal 1 angka</li>
                <li>Tidak sama dengan password lama</li>
            </ul>
        </div>

        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Password Lama</label>
                <input type="password" name="password_lama" id="pw_lama" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input type="password" name="password_baru" id="pw_baru" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="konfirmasi" id="pw_konfirm" class="form-control" required>
            </div>
            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="lihatPassword">
                <label class="form-check-label" for="lihatPassword" style="font-size:13px;">
                    Tampilkan password
                </label>
            </div>
            <button type="submit" class="btn-ganti">Simpan Password Baru</button>
        </form>
    </div>

</div><!-- End wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
/* Toggle show/hide semua field password */
document.getElementById('lihatPassword').addEventListener('change', function() {
    const type = this.checked ? 'text' : 'password';
    ['pw_lama', 'pw_baru', 'pw_konfirm'].forEach(id => {
        document.getElementById(id).type = type;
    });
});

/* Popup hasil submit */
<?php if ($pesan && $tipe === 'success'): ?>
Swal.fire({ title: 'Berhasil!', text: '<?= addslashes($pesan) ?>', icon: 'success',
            confirmButtonColor: '#0d1b2a', timer: 2000, showConfirmButton: false });
<?php elseif ($pesan && $tipe === 'danger'): ?>
Swal.fire({ title: 'Perhatian', text: '<?= addslashes($pesan) ?>', icon: 'error',
            confirmButtonColor: '#dc3545' });
<?php endif; ?>

/* Logout (hanya untuk role non-siswa yang punya topnav) */
<?php if ($role !== 'siswa'): ?>
document.getElementById('btnLogout')?.addEventListener('click', function() {
    Swal.fire({
        title: 'Keluar?', icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Logout', cancelButtonText: 'Batal', reverseButtons: true
    }).then(r => { if (r.isConfirmed) window.location.href = 'logout.php'; });
});
<?php endif; ?>
</script>
</body>
</html>
