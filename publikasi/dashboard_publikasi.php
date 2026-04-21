<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'publikasi') {
    header("location:../login.php");
    exit;
}

$periode = mysqli_query($conn,
    "SELECT id_periode, tahun, gelombang
     FROM periode_diklat
     ORDER BY tahun DESC, gelombang DESC"
);

/* ============================================================
   PROSES UPLOAD / UPDATE PUBLIKASI
   ============================================================ */
if (isset($_POST['submit'])) {

    $id_periode = (int) $_POST['id_periode'];
    $tempat     = mysqli_real_escape_string($conn, trim($_POST['tempat']));
    $nama_file  = '';

    /* Upload brosur jika ada */
    if (!empty($_FILES['brosur']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['brosur']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','pdf'];

        if (!in_array($ext, $allowed)) {
            $flash_error = "Format file tidak didukung. Gunakan JPG, PNG, atau PDF.";
            goto tampilForm;
        }
        if ($_FILES['brosur']['size'] > 5 * 1024 * 1024) {
            $flash_error = "File brosur melebihi 5MB.";
            goto tampilForm;
        }

        $nama_file = 'brosur_' . $id_periode . '_' . time() . '.' . $ext;
        $folder    = "../uploads/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);
        move_uploaded_file($_FILES['brosur']['tmp_name'], $folder . $nama_file);
    }

    /* Cek apakah sudah ada data untuk periode ini */
    $cek = mysqli_query($conn,
        "SELECT id_info, brosur_path FROM informasi_diklat WHERE id_periode='$id_periode'"
    );

    if (mysqli_num_rows($cek) > 0) {
        $lama      = mysqli_fetch_assoc($cek);
        $pathBrosur = $nama_file ?: $lama['brosur_path']; /* pertahankan brosur lama jika tidak upload baru */
        mysqli_query($conn,
            "UPDATE informasi_diklat SET brosur_path='$pathBrosur', tempat='$tempat', diperbarui_pada=NOW()
             WHERE id_periode='$id_periode'"
        );
        $flash_msg = "Publikasi berhasil diperbarui.";
    } else {
        if (empty($nama_file)) {
            $flash_error = "Brosur wajib diupload untuk publikasi baru.";
            goto tampilForm;
        }
        mysqli_query($conn,
            "INSERT INTO informasi_diklat (id_periode, brosur_path, tempat)
             VALUES ('$id_periode','$nama_file','$tempat')"
        );
        $flash_msg = "Publikasi berhasil ditambahkan.";
    }
}

tampilForm:
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Publikasi Diklat — Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar_top.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        /* Card form */
        .pub-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            padding: 28px 32px;
            margin-bottom: 24px;
        }
        .pub-card h5 {
            font-size: 15px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f2f5;
        }
        .form-label     { font-size: 13px; font-weight: 600; color: #495057; }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #dee2e6;
            font-size: 13px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(13,27,42,0.08);
        }
        .btn-pub {
            background: var(--navy);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 700;
            transition: 0.2s;
        }
        .btn-pub:hover { background: #1b263b; }

        /* Riwayat publikasi */
        .riwayat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f5;
            gap: 12px;
            flex-wrap: wrap;
        }
        .riwayat-item:last-child { border-bottom: none; }
        .riwayat-item .periode-label { font-weight: 700; color: var(--navy); font-size: 14px; }
        .riwayat-item .meta          { font-size: 12px; color: #6c757d; }

        /* Preview brosur */
        #previewWrap { margin-top: 12px; }
        #previewImg  { max-height: 200px; border-radius: 8px; object-fit: contain; }
    </style>
</head>
<body>

<!-- TOP NAVBAR PUBLIKASI -->
<nav class="topnav">
    <a class="tn-brand" href="dashboard_publikasi.php">
        <img src="../img/logo.png" alt="Logo" style="height:28px;width:auto;margin-right:8px;vertical-align:middle;">
        Publikasi
    </a>
    <div class="tn-links">
        <a href="dashboard_publikasi.php" class="active">📰 Data Publikasi</a>
        <a href="../ganti_password.php">🔒 Ganti Password</a>
    </div>
    <div class="tn-user">
        <span class="tn-username">Publikasi: <span><?= htmlspecialchars($_SESSION['username']) ?></span></span>
        <button class="tn-logout" id="btnLogout">🚪 Logout</button>
    </div>
</nav>

<div class="page-wrapper">
    <div class="row g-4">

        <!-- Kolom kiri: Form Upload -->
        <div class="col-xl-5 col-lg-6">
            <div class="pub-card">
                <h5>📤 Upload Publikasi Diklat</h5>

                <?php if (!empty($flash_error)): ?>
                <div class="flash-error mb-3"><?= htmlspecialchars($flash_error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="formPublikasi">

                    <div class="mb-3">
                        <label class="form-label">Pilih Periode <span class="text-danger">*</span></label>
                        <select name="id_periode" class="form-select" required id="selectPeriode">
                            <option value="">— Pilih Periode —</option>
                            <?php
                            mysqli_data_seek($periode, 0);
                            while ($p = mysqli_fetch_assoc($periode)):
                            ?>
                            <option value="<?= $p['id_periode'] ?>">
                                Gelombang <?= $p['gelombang'] ?> — <?= $p['tahun'] ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Upload Brosur / Poster</label>
                        <input type="file" name="brosur" class="form-control"
                               accept=".jpg,.jpeg,.png,.pdf" id="inputBrosur"
                               onchange="previewBrosur(this)">
                        <div class="form-text">Format: JPG, PNG, PDF. Maks 5MB. Biarkan kosong jika tidak ingin mengubah brosur.</div>
                        <div id="previewWrap" class="d-none text-center">
                            <img id="previewImg" src="" alt="Preview Brosur" class="img-fluid">
                        </div>
                    </div>

                    <button type="button" class="btn-pub w-100" onclick="konfirmasi()">
                        📢 Publikasikan
                    </button>
                </form>
            </div>
        </div>

        <!-- Kolom kanan: Riwayat Publikasi -->
        <div class="col-xl-7 col-lg-6">
            <div class="pub-card">
                <h5>📋 Riwayat Publikasi</h5>
                <?php
                $riwayat = mysqli_query($conn,
                    "SELECT i.*, p.tahun, p.gelombang
                     FROM informasi_diklat i
                     JOIN periode_diklat p ON i.id_periode = p.id_periode
                     ORDER BY i.diperbarui_pada DESC"
                );
                if (mysqli_num_rows($riwayat) === 0):
                ?>
                <div class="text-center py-5 text-muted" style="font-size:13px;">
                    <div style="font-size:36px;margin-bottom:10px;">📭</div>
                    Belum ada publikasi.
                </div>
                <?php else: ?>
                <?php while ($r = mysqli_fetch_assoc($riwayat)): ?>
                <div class="riwayat-item">
                    <div>
                        <div class="periode-label">
                            Gelombang <?= $r['gelombang'] ?> — <?= $r['tahun'] ?>
                        </div>
                        <div class="meta">
                            📍 <?= htmlspecialchars($r['tempat']) ?> &nbsp;·&nbsp;
                            Diperbarui: <?= date('d M Y H:i', strtotime($r['diperbarui_pada'])) ?>
                        </div>
                    </div>
                    <?php if ($r['brosur_path']): ?>
                    <a href="../uploads/<?= htmlspecialchars($r['brosur_path']) ?>" target="_blank"
                       class="btn btn-sm btn-outline-primary" style="border-radius:8px;font-size:12px;white-space:nowrap;">
                        📄 Lihat Brosur
                    </a>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- End row -->
</div><!-- End page-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
/* Flash message dari PHP */
<?php if (!empty($flash_msg)): ?>
Swal.fire({ title: 'Berhasil!', text: '<?= addslashes($flash_msg) ?>', icon: 'success',
            confirmButtonColor: '#0d1b2a', timer: 2000, showConfirmButton: false });
<?php endif; ?>

/* Preview brosur gambar */
function previewBrosur(input) {
    const wrap = document.getElementById('previewWrap');
    const img  = document.getElementById('previewImg');
    if (input.files && input.files[0]) {
        const ext = input.files[0].name.split('.').pop().toLowerCase();
        if (['jpg','jpeg','png'].includes(ext)) {
            const reader = new FileReader();
            reader.onload = e => { img.src = e.target.result; wrap.classList.remove('d-none'); };
            reader.readAsDataURL(input.files[0]);
        } else {
            wrap.classList.add('d-none');
        }
    }
}

/* Konfirmasi sebelum submit */
function konfirmasi() {
    const sel = document.getElementById('selectPeriode');
    if (!sel.value) {
        Swal.fire({ title: 'Pilih Periode', text: 'Periode wajib dipilih.', icon: 'warning', confirmButtonColor: '#0d1b2a' });
        return;
    }
    const teks = sel.options[sel.selectedIndex].text;
    Swal.fire({
        title: 'Konfirmasi Publikasi',
        html: `Publikasikan informasi untuk periode <strong>${teks}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d1b2a',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Publikasikan',
        cancelButtonText: 'Batal'
    }).then(r => { if (r.isConfirmed) document.getElementById('formPublikasi').submit(); });
}

/* Konfirmasi logout */
document.getElementById('btnLogout').addEventListener('click', function() {
    Swal.fire({
        title: 'Keluar dari Sistem?', icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Logout', cancelButtonText: 'Batal', reverseButtons: true
    }).then(r => { if (r.isConfirmed) window.location.href = '../logout.php'; });
});
</script>
</body>
</html>
