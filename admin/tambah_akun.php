<?php
session_start();
require '../koneksi.php'; /* path relatif dari folder admin/ */

/* Hanya admin yang boleh buat akun */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* Cek evaluasi pending untuk badge navbar */
$pending_eval = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS jml FROM evaluasi WHERE dikonfirmasi_admin=0"
));

/* ============================================================
   PROSES BUAT AKUN BARU
   ============================================================ */
if (isset($_POST['submit'])) {

    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    $role     = mysqli_real_escape_string($conn, $_POST['role']);

    /* Validasi input tidak kosong */
    if (!$username || !$password || !$role) {
        $flash_error = "Semua field wajib diisi.";
        goto tampilForm;
    }

    /* Validasi username tidak duplikat */
    $cek = mysqli_query($conn, "SELECT id_akun FROM akun WHERE username='$username' LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        $flash_error = "Username '$username' sudah digunakan. Pilih username lain.";
        goto tampilForm;
    }

    /* Simpan akun — password disimpan plaintext sesuai sistem yang sudah ada */
    mysqli_query($conn,
        "INSERT INTO akun (username, password, role) VALUES ('$username', '$password', '$role')"
    );

    header("Location: tambah_akun.php?msg=Akun+berhasil+dibuat&type=success");
    exit;
}

tampilForm:
$flash_msg  = $_GET['msg']  ?? '';
$flash_type = $_GET['type'] ?? 'info';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Akun — Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar_top.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        /* Card form buat akun */
        .akun-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 36px 40px;
            max-width: 500px;
            margin: 0 auto;
        }
        .akun-card h5 {
            font-size: 17px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f2f5;
        }
        .form-label { font-size: 13px; font-weight: 600; color: #495057; }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #dee2e6;
            font-size: 13px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(13,27,42,0.08);
        }
        .btn-buat {
            background: var(--navy);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
            font-weight: 700;
            width: 100%;
            transition: 0.2s;
        }
        .btn-buat:hover { background: #1b263b; }

        /* Role badge preview */
        .role-preview {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }

        /* Info box */
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<!-- TOP NAVBAR ADMIN -->
<nav class="topnav">
    <a class="tn-brand" href="dashboard_admin.php">
        <img src="../img/logo.png" alt="Logo" style="height:28px;width:auto;margin-right:8px;vertical-align:middle;">
        ⚙️ Admin Gemilang
    </a>
    <div class="tn-links">
        <a href="dashboard_admin.php">👥 Data Peserta</a>
        <a href="admin_evaluasi.php">
            📊 Evaluasi
            <?php if ($pending_eval['jml'] > 0): ?>
                <span class="tn-badge"><?= $pending_eval['jml'] ?></span>
            <?php endif; ?>
        </a>
        <a href="admin_persiapan_diklat.php">📅 Persiapan Diklat</a>
        <a href="admin_status_siswa.php">🔄 Status Siswa</a>
        <a href="tambah_akun.php" class="active">👤 Buat Akun</a>
        <a href="../ganti_password.php">🔒 Ganti Password</a>
    </div>
    <div class="tn-user">
        <span class="tn-username">Admin: <span><?= htmlspecialchars($_SESSION['username']) ?></span></span>
        <button class="tn-logout" id="btnLogout">🚪 Logout</button>
    </div>
</nav>

<div class="page-wrapper">
    <div class="akun-card">
        <h5>👤 Buat Akun Baru</h5>

        <!-- Info: untuk siswa gunakan form daftar -->
        <div class="info-box">
            ℹ️ Halaman ini untuk membuat akun <strong>non-siswa</strong> (admin, polda, kepala, dll).<br>
            Untuk pendaftaran siswa baru, gunakan form <a href="../daftar.php">Pendaftaran Siswa</a>.
        </div>

        <?php if (!empty($flash_error)): ?>
        <div class="flash-error mb-3"><?= htmlspecialchars($flash_error) ?></div>
        <?php endif; ?>

        <form method="POST" id="formAkun">

            <div class="mb-3">
                <label class="form-label">Username <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control" required
                       placeholder="Contoh: polda_yk_2024"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                <div class="form-text">Tidak boleh mengandung spasi.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input type="text" name="password" class="form-control" required
                       placeholder="Minimal 6 karakter">
                <div class="form-text">Password akan terlihat di sini agar admin bisa catat.</div>
            </div>

            <div class="mb-4">
                <label class="form-label">Role / Jabatan <span class="text-danger">*</span></label>
                <select name="role" class="form-select" required id="selectRole">
                    <option value="">— Pilih Role —</option>
                    <option value="admin"           <?= (($_POST['role']??'') === 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="polda"            <?= (($_POST['role']??'') === 'polda') ? 'selected' : '' ?>>Polda DIY</option>
                    <option value="kepala_keamanan"  <?= (($_POST['role']??'') === 'kepala_keamanan') ? 'selected' : '' ?>>Kepala Keamanan</option>
                    <option value="ceo"              <?= (($_POST['role']??'') === 'ceo') ? 'selected' : '' ?>>CEO</option>
                    <option value="publikasi"        <?= (($_POST['role']??'') === 'publikasi') ? 'selected' : '' ?>>Publikasi</option>
                </select>
            </div>

            <button type="submit" name="submit" class="btn-buat">✅ Buat Akun</button>
        </form>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
/* Flash message setelah redirect */
<?php if ($flash_msg): ?>
Swal.fire({
    title: '<?= $flash_type === "success" ? "Berhasil!" : "Gagal" ?>',
    text:  '<?= addslashes(urldecode($flash_msg)) ?>',
    icon:  '<?= $flash_type ?>',
    confirmButtonColor: '#0d1b2a',
    timer: 2500,
    showConfirmButton: false
});
<?php endif; ?>

/* Validasi username: tidak boleh ada spasi */
document.getElementById('formAkun').addEventListener('submit', function(e) {
    const username = this.querySelector('[name="username"]').value;
    if (/\s/.test(username)) {
        e.preventDefault();
        Swal.fire({
            title: 'Username Tidak Valid',
            text: 'Username tidak boleh mengandung spasi.',
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
    }
});

/* Logout konfirmasi */
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
