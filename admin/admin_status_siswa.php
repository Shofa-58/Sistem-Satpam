<?php
session_start();
include "../koneksi.php";
include "../auto_update_status.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("location:../login.php");
    exit;
}

/* ============================================================
   PROSES UPDATE STATUS
   Lulus/tidak_lulus HANYA lewat halaman Evaluasi (admin_evaluasi.php)
   Di sini hanya: calon→terverifikasi, terverifikasi→peserta
   ============================================================ */
if (isset($_POST['update_status'])) {
    $id          = mysqli_real_escape_string($conn, $_POST['id_siswa']);
    $status_baru = mysqli_real_escape_string($conn, $_POST['status']);

    $ambil = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT status FROM siswa WHERE id_peserta='$id'"
    ));
    $status_lama = $ambil['status'];

    /* Daftar transisi yang diizinkan dari halaman ini */
    $boleh = false;
    if ($status_lama === 'calon'         && $status_baru === 'terverifikasi') $boleh = true;
    if ($status_lama === 'terverifikasi' && $status_baru === 'peserta')       $boleh = true;
    /* lulus/tidak_lulus tidak diizinkan dari sini — harus lewat Evaluasi */

    if ($boleh) {
        mysqli_query($conn,
            "UPDATE siswa SET status='$status_baru' WHERE id_peserta='$id'"
        );
        /* Redirect dengan pesan sukses di URL */
        header("Location: admin_status_siswa.php?msg=Status+berhasil+diperbarui&type=success");
    } else {
        header("Location: admin_status_siswa.php?msg=Transisi+status+tidak+diizinkan&type=error");
    }
    exit;
}

/* Cek evaluasi pending untuk badge navbar */
$pending_eval = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS jml FROM evaluasi WHERE dikonfirmasi_admin=0"
));

/* Ambil semua siswa (termasuk filter id jika ada) */
$filter_id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$where_id   = $filter_id ? "WHERE id_peserta='$filter_id'" : '';
$data_siswa = mysqli_query($conn,
    "SELECT * FROM siswa $where_id ORDER BY id_peserta DESC"
);

/* Pesan flash dari redirect */
$flash_msg  = $_GET['msg']  ?? '';
$flash_type = $_GET['type'] ?? 'info';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Status Siswa — Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar_top.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .card-main {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .card-main .ch {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f2f5;
        }
        .card-main .ch h5 { margin: 0; font-size: 15px; font-weight: 700; color: var(--navy); }

        /* Tabel */
        .tbl-status th { background: var(--navy); color: #fff; font-size: 13px; font-weight: 600; white-space: nowrap; }
        .tbl-status td { vertical-align: middle; font-size: 13px; }
        .tbl-status tbody tr:hover { background: #f8f9fb; }

        /* Info box untuk peserta/lulus — tidak bisa diubah di sini */
        .info-eval {
            background: #f0f4ff;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 12px;
            color: #3b5bdb;
        }
    </style>
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="topnav">
    <a class="tn-brand" href="#"><img src="../img/logo.png" alt="Logo" style="height:28px;width:auto;margin-right:8px;vertical-align:middle;">⚙️ Admin Gemilang</a>
    <div class="tn-links">
        <a href="dashboard_admin.php">👥 Data Peserta</a>
        <a href="admin_evaluasi.php">
            📊 Evaluasi
            <?php if ($pending_eval['jml'] > 0): ?>
                <span class="tn-badge"><?= $pending_eval['jml'] ?></span>
            <?php endif; ?>
        </a>
        <a href="admin_persiapan_diklat.php">📅 Persiapan Diklat</a>
        <a href="admin_status_siswa.php" class="active">🔄 Status Siswa</a>
        <a href="tambah_akun.php">👤 Buat Akun</a>
    </div>
    <div class="tn-user">
        <span class="tn-username">Admin: <span><?= htmlspecialchars($_SESSION['username']) ?></span></span>
        <button class="tn-logout" id="btnLogout">🚪 Logout</button>
    </div>
</nav>

<div class="page-wrapper">

    <!-- Info status yang tidak bisa diubah di sini -->
    <div class="flash-error" style="background:#e8f4fd;border-left-color:#0d6efd;color:#084298;margin-bottom:16px;">
        ℹ️ Status <strong>Lulus</strong> dan <strong>Tidak Lulus</strong> hanya bisa diubah melalui
        <a href="admin_evaluasi.php" style="color:#084298;font-weight:700;">halaman Evaluasi Nilai</a>.
    </div>

    <div class="card-main">
        <div class="ch">
            <h5>🔄 Kelola Status Siswa</h5>
        </div>

        <div class="table-responsive">
            <table class="table tbl-status table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th class="text-center">Status Sekarang</th>
                        <th>Batas Revisi</th>
                        <th class="text-center" style="min-width:220px;">Update Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($data_siswa) === 0): ?>
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">Tidak ada data.</td>
                </tr>
                <?php endif; ?>
                <?php while ($d = mysqli_fetch_array($data_siswa)): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;color:var(--navy);"><?= htmlspecialchars($d['nama']) ?></div>
                    </td>
                    <td><?= htmlspecialchars($d['email']) ?></td>
                    <td class="text-center">
                        <span class="status_badge <?= $d['status'] ?>">
                            <?= ucfirst(str_replace('_',' ', $d['status'])) ?>
                        </span>
                    </td>
                    <td><?= $d['batas_revisi'] ?? '—' ?></td>
                    <td class="text-center">
                        <?php
                        /* Peserta/lulus/tidak_lulus tidak bisa diubah dari halaman ini */
                        if (in_array($d['status'], ['peserta', 'lulus', 'tidak_lulus'])):
                        ?>
                        <div class="info-eval">
                            <?php if ($d['status'] === 'peserta'): ?>
                                Ubah status via
                                <a href="admin_evaluasi.php" style="color:#0d6efd;font-weight:600;">Evaluasi</a>
                            <?php else: ?>
                                Status final — sudah dikonfirmasi
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <form method="POST" class="d-flex justify-content-center gap-2">
                            <input type="hidden" name="id_siswa" value="<?= $d['id_peserta'] ?>">
                            <select name="status" class="form-select form-select-sm w-auto" required>
                                <option value="">— Pilih —</option>
                                <?php if ($d['status'] === 'calon'): ?>
                                    <option value="terverifikasi">✅ Terverifikasi</option>
                                <?php elseif ($d['status'] === 'terverifikasi'): ?>
                                    <option value="peserta">🎓 Jadikan Peserta</option>
                                <?php endif; ?>
                            </select>
                            <button type="submit" name="update_status"
                                    class="btn btn-warning btn-sm fw-bold"
                                    onclick="return konfirmasiUpdate(this)">
                                Update
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- End page-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
/* Tampilkan flash message dari URL setelah redirect */
<?php if ($flash_msg): ?>
Swal.fire({
    title: '<?= $flash_type === "success" ? "Berhasil!" : "Perhatian" ?>',
    text:  '<?= htmlspecialchars(urldecode($flash_msg)) ?>',
    icon:  '<?= $flash_type ?>',
    confirmButtonColor: '#0d1b2a',
    timer: 2500,
    showConfirmButton: false
});
<?php endif; ?>

/* Konfirmasi sebelum update status */
function konfirmasiUpdate(btn) {
    const select = btn.closest('form').querySelector('select');
    const status = select.options[select.selectedIndex].text;
    if (!select.value) {
        Swal.fire({ title: 'Pilih Status', text: 'Pilih status tujuan dahulu.', icon: 'warning', confirmButtonColor: '#0d1b2a' });
        return false;
    }
    /* Tidak langsung return false — ini hanya konfirmasi, submit tetap jalan via Swal */
    event.preventDefault();
    Swal.fire({
        title: 'Ubah Status?',
        text: `Status akan diubah menjadi: ${status}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d1b2a',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Ubah',
        cancelButtonText: 'Batal'
    }).then(r => { if (r.isConfirmed) btn.closest('form').submit(); });
    return false;
}

/* Konfirmasi logout */
document.getElementById('btnLogout').addEventListener('click', function() {
    Swal.fire({
        title: 'Keluar dari Sistem?',
        text: 'Sesi Anda akan diakhiri.',
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
