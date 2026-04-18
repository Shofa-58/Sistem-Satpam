<?php
session_start();
include "../koneksi.php";
include "../auto_update_status.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("location:../login.php");
    exit;
}

/* ============================================================
   PROSES TAMBAH PERIODE
   ============================================================ */
if (isset($_POST['tambah_periode'])) {
    $tahun            = (int) $_POST['tahun'];
    $gelombang        = (int) $_POST['gelombang'];
    $tanggal_mulai    = $_POST['tanggal_mulai'];
    $tanggal_selesai  = $_POST['tanggal_selesai'];
    $biaya            = (int) $_POST['biaya'];
    $lokasi_spesifik  = mysqli_real_escape_string($conn, $_POST['lokasi_spesifik']);
    $lokasi_fasilitas = mysqli_real_escape_string($conn, $_POST['lokasi_fasilitas']);
    $fasilitas        = mysqli_real_escape_string($conn, $_POST['fasilitas']);
    $info_kebutuhan   = mysqli_real_escape_string($conn, $_POST['info_kebutuhan']);
    $batas_verifikasi = $_POST['batas_verifikasi'];
    $status           = mysqli_real_escape_string($conn, $_POST['status']);

    /* Validasi tanggal mulai < tanggal selesai */
    if ($tanggal_selesai < $tanggal_mulai) {
        $flash_msg  = urlencode("Tanggal selesai tidak boleh sebelum tanggal mulai!");
        $flash_type = "error";
        header("Location: admin_persiapan_diklat.php?msg=$flash_msg&type=$flash_type");
        exit;
    }

    mysqli_query($conn, "
        INSERT INTO periode_diklat
        (tahun, gelombang, tanggal_mulai, tanggal_selesai, biaya,
         lokasi_spesifik, lokasi_fasilitas, fasilitas, info_kebutuhan,
         batas_verifikasi, status)
        VALUES
        ('$tahun','$gelombang','$tanggal_mulai','$tanggal_selesai','$biaya',
         '$lokasi_spesifik','$lokasi_fasilitas','$fasilitas','$info_kebutuhan',
         '$batas_verifikasi','$status')
    ");

    header("Location: admin_persiapan_diklat.php?msg=Periode+berhasil+ditambahkan&type=success");
    exit;
}

/* ============================================================
   PROSES EDIT PERIODE
   ============================================================ */
if (isset($_POST['update_periode'])) {
    $id_p             = (int) $_POST['id_periode'];
    $tahun            = (int) $_POST['tahun'];
    $gelombang        = (int) $_POST['gelombang'];
    $tanggal_mulai    = $_POST['tanggal_mulai'];
    $tanggal_selesai  = $_POST['tanggal_selesai'];
    $biaya            = (int) $_POST['biaya'];
    $lokasi_spesifik  = mysqli_real_escape_string($conn, $_POST['lokasi_spesifik']);
    $lokasi_fasilitas = mysqli_real_escape_string($conn, $_POST['lokasi_fasilitas']);
    $fasilitas        = mysqli_real_escape_string($conn, $_POST['fasilitas']);
    $info_kebutuhan   = mysqli_real_escape_string($conn, $_POST['info_kebutuhan']);
    $batas_verifikasi = $_POST['batas_verifikasi'];
    $status           = mysqli_real_escape_string($conn, $_POST['status']);

    if ($tanggal_selesai < $tanggal_mulai) {
        $flash_msg  = urlencode("Tanggal selesai tidak boleh sebelum tanggal mulai!");
        $flash_type = "error";
        header("Location: admin_persiapan_diklat.php?msg=$flash_msg&type=$flash_type");
        exit;
    }

    mysqli_query($conn, "
        UPDATE periode_diklat
        SET tahun='$tahun', gelombang='$gelombang',
            tanggal_mulai='$tanggal_mulai', tanggal_selesai='$tanggal_selesai',
            biaya='$biaya', lokasi_spesifik='$lokasi_spesifik',
            lokasi_fasilitas='$lokasi_fasilitas', fasilitas='$fasilitas',
            info_kebutuhan='$info_kebutuhan', batas_verifikasi='$batas_verifikasi',
            status='$status'
        WHERE id_periode='$id_p'
    ");

    header("Location: admin_persiapan_diklat.php?msg=Periode+berhasil+diperbarui&type=success");
    exit;
}

/* ============================================================
   PROSES TAMBAH JADWAL
   ============================================================ */
if (isset($_POST['tambah_jadwal'])) {
    $tanggal    = $_POST['tanggal'];
    $kegiatan   = mysqli_real_escape_string($conn, $_POST['kegiatan']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $id_periode = (int) $_POST['id_periode'];

    if (!$tanggal || !$kegiatan) {
        header("Location: admin_persiapan_diklat.php?msg=Tanggal+dan+kegiatan+wajib+diisi&type=error");
        exit;
    }

    mysqli_query($conn, "
        INSERT INTO jadwal_diklat (tanggal, kegiatan, keterangan, id_periode)
        VALUES ('$tanggal','$kegiatan','$keterangan','$id_periode')
    ");

    header("Location: admin_persiapan_diklat.php?msg=Jadwal+berhasil+ditambahkan&type=success");
    exit;
}

/* ============================================================
   PROSES EDIT JADWAL
   ============================================================ */
if (isset($_POST['update_jadwal'])) {
    $id_j       = (int) $_POST['id_jadwal'];
    $tanggal    = $_POST['tanggal'];
    $kegiatan   = mysqli_real_escape_string($conn, $_POST['kegiatan']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $id_periode = (int) $_POST['id_periode'];

    mysqli_query($conn, "
        UPDATE jadwal_diklat
        SET tanggal='$tanggal', kegiatan='$kegiatan',
            keterangan='$keterangan', id_periode='$id_periode'
        WHERE id_jadwal='$id_j'
    ");

    header("Location: admin_persiapan_diklat.php?msg=Jadwal+berhasil+diperbarui&type=success");
    exit;
}

/* ============================================================
   PROSES HAPUS PERIODE & JADWAL
   ============================================================ */
if (isset($_GET['hapus_periode'])) {
    $id = (int) $_GET['hapus_periode'];
    mysqli_query($conn, "DELETE FROM periode_diklat WHERE id_periode='$id'");
    header("Location: admin_persiapan_diklat.php?msg=Periode+dihapus&type=success");
    exit;
}
if (isset($_GET['hapus_jadwal'])) {
    $id = (int) $_GET['hapus_jadwal'];
    mysqli_query($conn, "DELETE FROM jadwal_diklat WHERE id_jadwal='$id'");
    header("Location: admin_persiapan_diklat.php?msg=Jadwal+dihapus&type=success");
    exit;
}

/* ============================================================
   AMBIL DATA
   ============================================================ */

/* Semua periode */
$periode_result = mysqli_query($conn,
    "SELECT * FROM periode_diklat ORDER BY tahun DESC, gelombang DESC"
);
$semua_periode = [];
while ($p = mysqli_fetch_assoc($periode_result)) {
    $p['jadwal'] = []; /* slot untuk jadwal per periode */
    $semua_periode[$p['id_periode']] = $p;
}

/* Semua jadwal dikelompokkan per periode */
$jadwal_result = mysqli_query($conn,
    "SELECT j.*, p.tahun, p.gelombang
     FROM jadwal_diklat j
     LEFT JOIN periode_diklat p ON j.id_periode = p.id_periode
     ORDER BY j.tanggal ASC"
);
while ($j = mysqli_fetch_assoc($jadwal_result)) {
    if (isset($semua_periode[$j['id_periode']])) {
        $semua_periode[$j['id_periode']]['jadwal'][] = $j;
    }
}

/* Pesan flash */
$flash_msg  = $_GET['msg']  ?? '';
$flash_type = $_GET['type'] ?? 'info';

/* Cek evaluasi pending */
$pending_eval = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS jml FROM evaluasi WHERE dikonfirmasi_admin=0"
));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Persiapan Diklat — Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar_top.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        .section-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            padding: 24px;
            margin-bottom: 24px;
        }
        .section-card h5 {
            font-size: 15px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--gray-bg);
        }
        /* Tabel periode */
        .tbl-periode th { background: var(--navy); color:#fff; font-size:13px; white-space:nowrap; }
        .tbl-periode td { font-size:13px; vertical-align:middle; }

        /* Accordion jadwal per periode */
        .accordion-button:not(.collapsed) { background: var(--navy); color: var(--yellow); }
        .accordion-button:focus           { box-shadow: none; }
        .accordion-button                 { font-size: 14px; font-weight: 600; }

        .tbl-jadwal th { background: #f0f2f5; font-size:12px; color:#495057; }
        .tbl-jadwal td { font-size:13px; vertical-align:middle; }

        /* Badge status periode */
        .badge-periode {
            padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;
        }
        .badge-periode.pendaftaran { background:#fff3cd; color:#664d03; }
        .badge-periode.berjalan    { background:#cfe2ff; color:#084298; }
        .badge-periode.selesai     { background:#d1e7dd; color:#0a3622; }
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
        <a href="admin_persiapan_diklat.php" class="active">📅 Persiapan Diklat</a>
        <a href="admin_status_siswa.php">🔄 Status Siswa</a>
        <a href="tambah_akun.php">👤 Buat Akun</a>
    </div>
    <div class="tn-user">
        <span class="tn-username">Admin: <span><?= htmlspecialchars($_SESSION['username']) ?></span></span>
        <button class="tn-logout" id="btnLogout">🚪 Logout</button>
    </div>
</nav>

<div class="page-wrapper">

    <!-- ===== FORM TAMBAH PERIODE & JADWAL (layout 2 kolom) ===== -->
    <div class="row g-4 mb-4">

        <!-- Form Tambah Periode -->
        <div class="col-xl-6">
            <div class="section-card">
                <h5>➕ Tambah Periode Diklat</h5>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Tahun</label>
                            <input type="number" name="tahun" class="form-control" required placeholder="2024">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Gelombang</label>
                            <input type="number" name="gelombang" class="form-control" min="1" max="4" required placeholder="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="mulai_tambah" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" id="selesai_tambah" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Batas Verifikasi Admin</label>
                            <input type="date" name="batas_verifikasi" class="form-control" required>
                            <div class="form-text">Setelah tanggal ini, status siswa otomatis menjadi peserta.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Biaya Diklat (Rp)</label>
                            <input type="number" name="biaya" class="form-control" placeholder="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Lokasi Spesifik</label>
                            <input type="text" name="lokasi_spesifik" class="form-control" placeholder="Contoh: Pusdiklat Polda DIY">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Lokasi Pengambilan Fasilitas</label>
                            <input type="text" name="lokasi_fasilitas" class="form-control" placeholder="Contoh: Gudang Logistik Lt.1">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Fasilitas yang Diberikan</label>
                            <textarea name="fasilitas" class="form-control" rows="2" placeholder="Seragam PDH, Modul, Konsumsi 1x/hari"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Informasi Kebutuhan Peserta</label>
                            <textarea name="info_kebutuhan" class="form-control" rows="2" placeholder="Pakaian olahraga, Sepatu PDH hitam, dll"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Status</label>
                            <select name="status" class="form-select">
                                <option value="pendaftaran">Pendaftaran</option>
                                <option value="berjalan">Berjalan</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-warning w-100 fw-bold" name="tambah_periode">
                                Simpan Periode
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Form Tambah Jadwal -->
        <div class="col-xl-6">
            <div class="section-card">
                <h5>📋 Tambah Rundown Kegiatan</h5>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Pilih Periode</label>
                            <select name="id_periode" class="form-select">
                                <?php foreach ($semua_periode as $p): ?>
                                <option value="<?= $p['id_periode'] ?>">
                                    <?= $p['tahun'] ?> — Gelombang <?= $p['gelombang'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Kegiatan</label>
                            <input type="text" name="kegiatan" class="form-control" required placeholder="Contoh: Pembukaan Diklat">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3" placeholder="Keterangan tambahan (opsional)"></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-warning w-100 fw-bold" name="tambah_jadwal">
                                Tambah Jadwal
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div><!-- End row -->

    <!-- ===== DAFTAR PERIODE DIKLAT ===== -->
    <div class="section-card">
        <h5>📅 Daftar Periode Diklat</h5>
        <?php if (empty($semua_periode)): ?>
        <div class="text-center py-4 text-muted">Belum ada periode diklat.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table tbl-periode table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tahun</th>
                        <th>Gel.</th>
                        <th>Pelaksanaan</th>
                        <th>Batas Verif.</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($semua_periode as $p): ?>
                <tr>
                    <td><?= $p['tahun'] ?></td>
                    <td><?= $p['gelombang'] ?></td>
                    <td><?= date('d/m/Y', strtotime($p['tanggal_mulai'])) ?> s/d <?= date('d/m/Y', strtotime($p['tanggal_selesai'])) ?></td>
                    <td><?= $p['batas_verifikasi'] ? date('d/m/Y', strtotime($p['batas_verifikasi'])) : '—' ?></td>
                    <td>
                        <span class="badge-periode <?= $p['status'] ?>">
                            <?= ucfirst($p['status']) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-2 justify-content-center">
                            <!-- Tombol Edit — data-* dikirim ke modal -->
                            <button class="btn btn-sm btn-outline-primary btn-edit-periode"
                                    data-id="<?= $p['id_periode'] ?>"
                                    data-tahun="<?= $p['tahun'] ?>"
                                    data-gelombang="<?= $p['gelombang'] ?>"
                                    data-mulai="<?= $p['tanggal_mulai'] ?>"
                                    data-selesai="<?= $p['tanggal_selesai'] ?>"
                                    data-biaya="<?= $p['biaya'] ?>"
                                    data-lok_spes="<?= htmlspecialchars($p['lokasi_spesifik'] ?? '') ?>"
                                    data-lok_fas="<?= htmlspecialchars($p['lokasi_fasilitas'] ?? '') ?>"
                                    data-fasilitas="<?= htmlspecialchars($p['fasilitas'] ?? '') ?>"
                                    data-kebutuhan="<?= htmlspecialchars($p['info_kebutuhan'] ?? '') ?>"
                                    data-batas="<?= $p['batas_verifikasi'] ?>"
                                    data-status="<?= $p['status'] ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalEditPeriode">
                                ✏️ Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="konfirmasiHapus('?hapus_periode=<?= $p['id_periode'] ?>')">
                                🗑 Hapus
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== RUNDOWN PER PERIODE (Accordion) ===== -->
    <div class="section-card">
        <h5>📋 Rundown Kegiatan (Per Periode)</h5>
        <?php if (empty($semua_periode)): ?>
        <div class="text-center py-4 text-muted">Belum ada periode diklat.</div>
        <?php else: ?>
        <div class="accordion" id="accordionJadwal">
            <?php $i = 0; foreach ($semua_periode as $p): $i++; ?>
            <div class="accordion-item mb-2" style="border-radius:10px;overflow:hidden;border:1px solid #dee2e6;">
                <h2 class="accordion-header">
                    <button class="accordion-button <?= $i > 1 ? 'collapsed' : '' ?>" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapse<?= $p['id_periode'] ?>">
                        <?= $p['tahun'] ?> — Gelombang <?= $p['gelombang'] ?>
                        <span class="badge-periode <?= $p['status'] ?> ms-2"><?= ucfirst($p['status']) ?></span>
                        <span class="ms-2 text-white-50" style="font-weight:400;font-size:12px;">
                            (<?= count($p['jadwal']) ?> kegiatan)
                        </span>
                    </button>
                </h2>
                <div id="collapse<?= $p['id_periode'] ?>"
                     class="accordion-collapse collapse <?= $i === 1 ? 'show' : '' ?>">
                    <div class="accordion-body p-0">
                        <?php if (empty($p['jadwal'])): ?>
                        <div class="text-center py-4 text-muted" style="font-size:13px;">
                            Belum ada rundown untuk periode ini.
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table tbl-jadwal table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="padding-left:20px;">Tanggal</th>
                                        <th>Kegiatan</th>
                                        <th>Keterangan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($p['jadwal'] as $j): ?>
                                <tr>
                                    <td style="padding-left:20px;"><?= date('d/m/Y', strtotime($j['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($j['kegiatan']) ?></td>
                                    <td><?= htmlspecialchars($j['keterangan']) ?></td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <!-- Edit Jadwal -->
                                            <button class="btn btn-sm btn-outline-primary btn-edit-jadwal"
                                                    data-id="<?= $j['id_jadwal'] ?>"
                                                    data-tanggal="<?= $j['tanggal'] ?>"
                                                    data-kegiatan="<?= htmlspecialchars($j['kegiatan']) ?>"
                                                    data-keterangan="<?= htmlspecialchars($j['keterangan']) ?>"
                                                    data-id_periode="<?= $j['id_periode'] ?>"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditJadwal">
                                                ✏️
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="konfirmasiHapus('?hapus_jadwal=<?= $j['id_jadwal'] ?>')">
                                                🗑
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div><!-- End page-wrapper -->

<!-- ===== MODAL EDIT PERIODE ===== -->
<div class="modal fade" id="modalEditPeriode" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--navy);color:#fff;">
                <h5 class="modal-title">✏️ Edit Periode Diklat</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditPeriode">
                    <input type="hidden" name="id_periode" id="ep_id">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Tahun</label>
                            <input type="number" name="tahun" id="ep_tahun" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Gelombang</label>
                            <input type="number" name="gelombang" id="ep_gel" class="form-control" min="1" max="4" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="ep_mulai" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" style="font-size:13px;">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" id="ep_selesai" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Batas Verifikasi</label>
                            <input type="date" name="batas_verifikasi" id="ep_batas" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Biaya (Rp)</label>
                            <input type="number" name="biaya" id="ep_biaya" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Lokasi Spesifik</label>
                            <input type="text" name="lokasi_spesifik" id="ep_lok_spes" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Lokasi Fasilitas</label>
                            <input type="text" name="lokasi_fasilitas" id="ep_lok_fas" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Fasilitas</label>
                            <textarea name="fasilitas" id="ep_fasilitas" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Kebutuhan Peserta</label>
                            <textarea name="info_kebutuhan" id="ep_kebutuhan" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Status</label>
                            <select name="status" id="ep_status" class="form-select">
                                <option value="pendaftaran">Pendaftaran</option>
                                <option value="berjalan">Berjalan</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="formEditPeriode" name="update_periode" class="btn btn-warning fw-bold">
                    💾 Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL EDIT JADWAL ===== -->
<div class="modal fade" id="modalEditJadwal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--navy);color:#fff;">
                <h5 class="modal-title">✏️ Edit Jadwal / Rundown</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="formEditJadwal">
                    <input type="hidden" name="id_jadwal" id="ej_id">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Periode</label>
                            <select name="id_periode" id="ej_periode" class="form-select">
                                <?php foreach ($semua_periode as $p): ?>
                                <option value="<?= $p['id_periode'] ?>">
                                    <?= $p['tahun'] ?> — Gel. <?= $p['gelombang'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Tanggal</label>
                            <input type="date" name="tanggal" id="ej_tanggal" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Kegiatan</label>
                            <input type="text" name="kegiatan" id="ej_kegiatan" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:13px;">Keterangan</label>
                            <textarea name="keterangan" id="ej_keterangan" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="formEditJadwal" name="update_jadwal" class="btn btn-warning fw-bold">
                    💾 Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
/* Flash message setelah redirect */
<?php if ($flash_msg): ?>
Swal.fire({
    title: '<?= $flash_type === "success" ? "Berhasil!" : "Perhatian" ?>',
    text:  '<?= addslashes(urldecode($flash_msg)) ?>',
    icon:  '<?= $flash_type ?>',
    confirmButtonColor: '#0d1b2a',
    timer: 2000,
    showConfirmButton: false
});
<?php endif; ?>

/* Isi form modal EDIT PERIODE dari data-* pada tombol */
document.querySelectorAll('.btn-edit-periode').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('ep_id').value        = this.dataset.id;
        document.getElementById('ep_tahun').value     = this.dataset.tahun;
        document.getElementById('ep_gel').value       = this.dataset.gelombang;
        document.getElementById('ep_mulai').value     = this.dataset.mulai;
        document.getElementById('ep_selesai').value   = this.dataset.selesai;
        document.getElementById('ep_biaya').value     = this.dataset.biaya;
        document.getElementById('ep_lok_spes').value  = this.dataset.lok_spes;
        document.getElementById('ep_lok_fas').value   = this.dataset.lok_fas;
        document.getElementById('ep_fasilitas').value = this.dataset.fasilitas;
        document.getElementById('ep_kebutuhan').value = this.dataset.kebutuhan;
        document.getElementById('ep_batas').value     = this.dataset.batas;
        /* Set status dropdown */
        const sel = document.getElementById('ep_status');
        for (let opt of sel.options) { if (opt.value === this.dataset.status) opt.selected = true; }
    });
});

/* Isi form modal EDIT JADWAL dari data-* */
document.querySelectorAll('.btn-edit-jadwal').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('ej_id').value          = this.dataset.id;
        document.getElementById('ej_tanggal').value     = this.dataset.tanggal;
        document.getElementById('ej_kegiatan').value    = this.dataset.kegiatan;
        document.getElementById('ej_keterangan').value  = this.dataset.keterangan;
        const sel = document.getElementById('ej_periode');
        for (let opt of sel.options) { if (opt.value === this.dataset.id_periode) opt.selected = true; }
    });
});

/* Konfirmasi hapus dengan SweetAlert2 */
function konfirmasiHapus(url) {
    Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: 'Data yang dihapus tidak bisa dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then(r => { if (r.isConfirmed) window.location.href = url; });
}

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
