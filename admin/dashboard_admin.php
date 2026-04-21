<?php
session_start();
include "../koneksi.php";
include "../auto_update_status.php"; /* PBI-030: auto-update status siswa berdasarkan timer periode */

/* Redirect jika bukan admin */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("location: ../login.php");
    exit;
}


/* ============================================================
   HAPUS SISWA — rollback semua data terkait sebelum hapus
   Hanya siswa yang masih calon/terverifikasi yang bisa dihapus
   untuk menghindari inkonsistensi data evaluasi yang sudah ada
   ============================================================ */
if (isset($_GET['hapus_siswa'])) {
    $id_hapus = (int) $_GET['hapus_siswa'];

    /* Ambil id_akun sebelum hapus (untuk hapus akun juga) */
    $sw = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_akun, status FROM siswa WHERE id_peserta='$id_hapus' LIMIT 1"
    ));

    if ($sw) {
        /* Hapus file dokumen dari disk */
        $docs = mysqli_query($conn, "SELECT file_path FROM dokumen_pendaftaran WHERE id_siswa='$id_hapus'");
        while ($doc = mysqli_fetch_assoc($docs)) {
            if ($doc['file_path'] && file_exists('../' . $doc['file_path'])) {
                @unlink('../' . $doc['file_path']);
            }
        }
        /* Hapus semua data relasi siswa */
        mysqli_query($conn, "DELETE FROM dokumen_pendaftaran WHERE id_siswa='$id_hapus'");
        mysqli_query($conn, "DELETE FROM peserta_periode     WHERE id_peserta='$id_hapus'");
        mysqli_query($conn, "DELETE FROM evaluasi            WHERE id_siswa='$id_hapus'");
        mysqli_query($conn, "DELETE FROM notifikasi          WHERE id_siswa='$id_hapus'");
        mysqli_query($conn, "DELETE FROM siswa               WHERE id_peserta='$id_hapus'");
        mysqli_query($conn, "DELETE FROM akun                WHERE id_akun='{$sw['id_akun']}'");

        header("Location: dashboard_admin.php?msg=Siswa+berhasil+dihapus&type=success");
    } else {
        header("Location: dashboard_admin.php?msg=Data+tidak+ditemukan&type=error");
    }
    exit;
}

/* Flash message dari redirect (misal setelah hapus) */
$flash_msg  = $_GET['msg']  ?? '';
$flash_type = $_GET['type'] ?? 'info';

/* ============================================================
   SEARCH & FILTER
   ============================================================ */
$search         = isset($_GET['q'])       ? mysqli_real_escape_string($conn, trim($_GET['q']))  : '';
$filter_status  = isset($_GET['status'])  ? mysqli_real_escape_string($conn, $_GET['status'])   : '';
$filter_periode = isset($_GET['periode']) ? (int) $_GET['periode']                              : 0;

/* Build WHERE clause */
$where_parts = [];
if ($search !== '') {
    $where_parts[] = "(s.nama LIKE '%$search%' OR s.email LIKE '%$search%' OR s.no_telp LIKE '%$search%')";
}
if ($filter_status !== '') {
    $where_parts[] = "s.status = '$filter_status'";
}
$where_sql = $where_parts ? "WHERE " . implode(' AND ', $where_parts) : '';

/* JOIN periode jika filter periode aktif */
$join_sql = '';
if ($filter_periode) {
    $join_sql = "JOIN peserta_periode pp ON s.id_peserta = pp.id_peserta AND pp.id_periode='$filter_periode'";
}

/* Query data peserta */
$data = mysqli_query($conn,
    "SELECT s.*,
            (SELECT COUNT(*) FROM dokumen_pendaftaran WHERE id_siswa=s.id_peserta AND status_verifikasi='revisi') AS jml_revisi,
            (SELECT COUNT(*) FROM dokumen_pendaftaran WHERE id_siswa=s.id_peserta AND status_verifikasi='pending') AS jml_pending
     FROM siswa s
     $join_sql
     $where_sql
     ORDER BY s.created_at DESC"
);

/* Statistik ringkas untuk stat cards */
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        COUNT(*) AS total,
        SUM(status='calon') AS calon,
        SUM(status='terverifikasi') AS terverifikasi,
        SUM(status='peserta') AS peserta,
        SUM(status='lulus') AS lulus,
        SUM(status='tidak_lulus') AS tidak_lulus
     FROM siswa"
));

/* Daftar periode untuk dropdown filter */
$semua_periode = mysqli_query($conn,
    "SELECT * FROM periode_diklat ORDER BY tahun DESC, gelombang DESC"
);

/* Cek evaluasi yang belum dikonfirmasi admin (untuk badge navbar) */
$pending_eval = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS jml FROM evaluasi WHERE dikonfirmasi_admin=0"
));

/* ============================================================
   NOTIFIKASI REVISI — Siswa yang sudah re-upload dokumen revisi
   Query: dokumen status 'pending' DAN tgl_revisi IS NOT NULL
   Artinya siswa sudah pernah diminta revisi, lalu upload ulang
   ============================================================ */
$notif_revisi = mysqli_query($conn,
    "SELECT s.id_peserta, s.nama, s.email,
            COUNT(d.id_dokumen) AS jml_reupload,
            MAX(d.tgl_revisi)   AS tgl_terakhir
     FROM siswa s
     JOIN dokumen_pendaftaran d ON d.id_siswa = s.id_peserta
     WHERE d.status_verifikasi = 'pending'
       AND d.tgl_revisi IS NOT NULL
     GROUP BY s.id_peserta
     ORDER BY MAX(d.tgl_revisi) DESC"
);
$ada_notif_revisi = mysqli_num_rows($notif_revisi) > 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin — Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar_top.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        /* Stat cards */
        .stat-row {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            text-decoration: none;
            border-top: 3px solid transparent;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,0,0,0.1); }
        .stat-card .val  { font-size: 28px; font-weight: 800; line-height: 1; color: var(--navy); }
        .stat-card .lbl  { font-size: 11px; color: #6c757d; margin-top: 4px; }

        /* Filter bar */
        .filter-bar {
            background: #fff;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .filter-bar label     { font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #495057; }
        .filter-bar .form-control,
        .filter-bar .form-select {
            border-radius: 8px;
            border: 1.5px solid #dee2e6;
            font-size: 13px;
        }
        .filter-bar .form-control:focus,
        .filter-bar .form-select:focus {
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(13,27,42,0.1);
        }
        .btn-filter {
            background: var(--navy); color: #fff; border: none;
            border-radius: 8px; padding: 8px 20px; font-size: 13px; font-weight: 600;
        }
        .btn-reset {
            background: #f8f9fa; color: #495057; border: 1.5px solid #dee2e6;
            border-radius: 8px; padding: 8px 14px; font-size: 13px;
            text-decoration: none; font-weight: 600;
        }

        /* Main table card */
        .main-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .main-card .card-header-bar {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .main-card .card-header-bar h5 {
            margin: 0; font-size: 15px; font-weight: 700; color: var(--navy);
        }

        /* Tabel peserta */
        .table-admin          { font-size: 13px; }
        .table-admin th       { background: var(--navy); color: #fff; font-weight: 600; white-space: nowrap; }
        .table-admin td       { vertical-align: middle; }
        .table-admin tbody tr:hover { background: #f8f9fb; }

        /* Badge dokumen */
        .doc-badge            { font-size: 11px; padding: 3px 8px; border-radius: 12px; font-weight: 600; }
        .doc-badge.revisi     { background: #f8d7da; color: #842029; }
        .doc-badge.pending    { background: #fff3cd; color: #664d03; }
        .doc-badge.ok         { background: #d1e7dd; color: #0a3622; }

        /* Action buttons */
        .btn-aksi {
            font-size: 12px; padding: 5px 10px; border-radius: 7px;
            font-weight: 600; cursor: pointer; text-decoration: none;
            display: inline-block; white-space: nowrap; border: none;
        }
        .btn-aksi.biodata { background: #cfe2ff; color: #084298; }
        .btn-aksi.dokumen { background: #d1e7dd; color: #0a3622; }
        .btn-aksi.status  { background: #fff3cd; color: #664d03; }
    </style>
</head>
<body>

<!-- ===================== TOP NAVBAR ===================== -->
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
        <a href="../ganti_password.php">🔒 Ganti Password</a>
    </div>

    <div class="tn-user">
        <span class="tn-username">Admin: <span><?= htmlspecialchars($_SESSION['username']) ?></span></span>
        <button class="tn-logout" id="btnLogout">🚪 Logout</button>
    </div>
</nav>

<!-- ===================== KONTEN ===================== -->
<div class="page-wrapper">

    <!-- Alert evaluasi pending -->
    <?php if ($pending_eval['jml'] > 0): ?>
    <div class="alert-eval-pending">
        ⚠️ Ada <strong><?= $pending_eval['jml'] ?> nilai siswa</strong> yang belum dikonfirmasi.
        <a href="admin_evaluasi.php" style="color:#664d03;font-weight:700;">→ Buka Evaluasi</a>
    </div>
    <?php endif; ?>

    <!-- ===== NOTIFIKASI REVISI RE-UPLOAD ===== -->
    <!-- Tampil jika ada siswa yang sudah upload ulang dokumen revisi & menunggu dicek -->
    <?php if ($ada_notif_revisi): ?>
    <div class="notif-revisi-panel">
        <div class="notif-title">
            🔔 Siswa yang sudah upload ulang dokumen revisi — menunggu dicek admin
        </div>
        <?php while ($nr = mysqli_fetch_assoc($notif_revisi)): ?>
        <div class="notif-revisi-item">
            <div>
                <div class="nama"><?= htmlspecialchars($nr['nama']) ?></div>
                <div class="info">
                    <?= $nr['jml_reupload'] ?> dokumen diupload ulang •
                    <?= date('d M Y H:i', strtotime($nr['tgl_terakhir'])) ?>
                </div>
            </div>
            <a href="admin_lihat_dokumen.php?id=<?= $nr['id_peserta'] ?>" class="btn-cek">
                🔍 Cek Dokumen
            </a>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Stat cards -->
    <div class="stat-row">
        <a href="?status=" class="stat-card" style="border-top-color:var(--navy);">
            <div class="val"><?= $stats['total'] ?></div>
            <div class="lbl">Total</div>
        </a>
        <a href="?status=calon" class="stat-card" style="border-top-color:#6c757d;">
            <div class="val" style="color:#6c757d"><?= $stats['calon'] ?></div>
            <div class="lbl">Calon</div>
        </a>
        <a href="?status=terverifikasi" class="stat-card" style="border-top-color:#0d6efd;">
            <div class="val" style="color:#0d6efd"><?= $stats['terverifikasi'] ?></div>
            <div class="lbl">Terverifikasi</div>
        </a>
        <a href="?status=peserta" class="stat-card" style="border-top-color:#fd7e14;">
            <div class="val" style="color:#fd7e14"><?= $stats['peserta'] ?></div>
            <div class="lbl">Peserta</div>
        </a>
        <a href="?status=lulus" class="stat-card" style="border-top-color:#198754;">
            <div class="val" style="color:#198754"><?= $stats['lulus'] ?></div>
            <div class="lbl">Lulus</div>
        </a>
        <a href="?status=tidak_lulus" class="stat-card" style="border-top-color:#dc3545;">
            <div class="val" style="color:#dc3545"><?= $stats['tidak_lulus'] ?></div>
            <div class="lbl">Tidak Lulus</div>
        </a>
    </div>

    <!-- Filter bar -->
    <form method="GET" action="dashboard_admin.php">
        <div class="filter-bar">
            <div>
                <label>Cari Peserta</label>
                <input type="text" name="q" class="form-control" placeholder="Nama / email / no HP..."
                       value="<?= htmlspecialchars($search) ?>" style="min-width:220px;">
            </div>
            <div>
                <label>Filter Status</label>
                <select name="status" class="form-select" style="min-width:160px;">
                    <option value="">Semua Status</option>
                    <option value="calon"         <?= $filter_status==='calon'?'selected':'' ?>>Calon</option>
                    <option value="terverifikasi"  <?= $filter_status==='terverifikasi'?'selected':'' ?>>Terverifikasi</option>
                    <option value="peserta"        <?= $filter_status==='peserta'?'selected':'' ?>>Peserta</option>
                    <option value="lulus"          <?= $filter_status==='lulus'?'selected':'' ?>>Lulus</option>
                    <option value="tidak_lulus"    <?= $filter_status==='tidak_lulus'?'selected':'' ?>>Tidak Lulus</option>
                </select>
            </div>
            <div>
                <label>Filter Periode</label>
                <select name="periode" class="form-select" style="min-width:180px;">
                    <option value="0">Semua Periode</option>
                    <?php
                    mysqli_data_seek($semua_periode, 0);
                    while ($p = mysqli_fetch_assoc($semua_periode)) {
                        $sel = $filter_periode == $p['id_periode'] ? 'selected' : '';
                        echo "<option value='{$p['id_periode']}' $sel>
                                {$p['tahun']} — Gel. {$p['gelombang']}
                              </option>";
                    }
                    ?>
                </select>
            </div>
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <button type="submit" class="btn-filter">🔍 Cari</button>
                <a href="dashboard_admin.php" class="btn-reset">Reset</a>
            </div>
        </div>
    </form>

    <!-- Tabel peserta -->
    <div class="main-card">
        <div class="card-header-bar">
            <h5>
                Data Pendaftar
                <span style="font-size:13px;color:#6c757d;font-weight:400;margin-left:8px;">
                    (<?= mysqli_num_rows($data) ?> data)
                </span>
            </h5>
            <?php if ($search || $filter_status || $filter_periode): ?>
            <span style="font-size:12px;color:#0d6efd;">
                Filter aktif
                <?php if ($search): ?> · "<?= htmlspecialchars($search) ?>"<?php endif; ?>
                <?php if ($filter_status): ?> · <?= ucfirst(str_replace('_',' ',$filter_status)) ?><?php endif; ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-admin table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="min-width:160px;">Nama</th>
                        <th>Email</th>
                        <th>No HP</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Dokumen</th>
                        <th>Batas Revisi</th>
                        <th>Tgl Daftar</th>
                        <th class="text-center" style="min-width:200px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($data) === 0): ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <div style="font-size:32px;margin-bottom:8px;">🔍</div>
                        <p class="mb-0">Tidak ada data yang sesuai filter.</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php while ($d = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td>
                        <div style="font-weight:600;color:var(--navy);">
                            <?= htmlspecialchars($d['nama']) ?>
                        </div>
                        <div style="font-size:11px;color:#6c757d;">
                            <?= $d['jenis_kelamin'] === 'L' ? '♂ Laki-laki' : '♀ Perempuan' ?>
                        </div>
                    </td>
                    <td style="font-size:12px;"><?= htmlspecialchars($d['email']) ?></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($d['no_telp']) ?></td>
                    <td class="text-center">
                        <span class="status_badge <?= $d['status'] ?>">
                            <?= ucfirst(str_replace('_',' ', $d['status'])) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ($d['jml_revisi'] > 0): ?>
                            <span class="doc-badge revisi">⚠️ <?= $d['jml_revisi'] ?> revisi</span>
                        <?php elseif ($d['jml_pending'] > 0): ?>
                            <span class="doc-badge pending">⏳ <?= $d['jml_pending'] ?> pending</span>
                        <?php else: ?>
                            <span class="doc-badge ok">✓ OK</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;">
                        <?php
                        if ($d['batas_revisi']) {
                            $lewat = strtotime(date('Y-m-d')) > strtotime($d['batas_revisi']);
                            $warna = $lewat ? '#dc3545' : '#198754';
                            echo "<span style='color:$warna'>{$d['batas_revisi']}</span>";
                        } else {
                            echo '<span style="color:#adb5bd;">—</span>';
                        }
                        ?>
                    </td>
                    <td style="font-size:12px;">
                        <?= date('d/m/Y', strtotime($d['created_at'])) ?>
                    </td>
                    <td class="text-center">
                        <div style="display:flex;gap:4px;justify-content:center;flex-wrap:wrap;">
                            <a href="admin_lihat_biodata.php?id=<?= $d['id_peserta'] ?>"
                               class="btn-aksi biodata">Biodata</a>
                            <a href="admin_lihat_dokumen.php?id=<?= $d['id_peserta'] ?>"
                               class="btn-aksi dokumen">
                                Dokumen
                                <!-- Titik merah jika ada dokumen perlu perhatian -->
                                <?php if ($d['jml_revisi'] > 0 || $d['jml_pending'] > 0): ?>
                                <span style="display:inline-block;width:6px;height:6px;
                                             background:#dc3545;border-radius:50%;
                                             margin-left:2px;vertical-align:middle;"></span>
                                <?php endif; ?>
                            </a>
                            <a href="admin_status_siswa.php?id=<?= $d['id_peserta'] ?>"
                               class="btn-aksi status">Status</a>
                            <!-- Tombol Hapus Siswa — SweetAlert konfirmasi sebelum eksekusi -->
                            <button onclick="hapusSiswa(<?= $d['id_peserta'] ?>, '<?= addslashes($d['nama']) ?>')"
                                    class="btn-aksi" style="background:#f8d7da;color:#842029;">
                                🗑 Hapus
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- End page-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>

/* Flash message setelah redirect (hapus siswa, dll) */
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

/* Enter di search field langsung submit */
document.querySelector('input[name="q"]')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') this.closest('form').submit();
});

/* Konfirmasi logout dengan SweetAlert2 */
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

/* Konfirmasi hapus siswa */
function hapusSiswa(id, nama) {
    Swal.fire({
        title: 'Hapus Siswa?',
        html: `Data <b>${nama}</b> beserta semua dokumen dan riwayatnya akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then(r => { if (r.isConfirmed) window.location.href = 'dashboard_admin.php?hapus_siswa=' + id; });
}
</script>
</body>
</html>
