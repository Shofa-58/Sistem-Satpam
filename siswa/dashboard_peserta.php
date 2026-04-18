<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

$id_akun  = $_SESSION['id_akun'];
$siswa    = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT s.* FROM siswa s WHERE s.id_akun='$id_akun' LIMIT 1"
));
if (!$siswa) { echo "Data tidak ditemukan."; exit; }
$id_siswa = $siswa['id_peserta'];

$periode = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT pd.* FROM peserta_periode pp
     JOIN periode_diklat pd ON pp.id_periode = pd.id_periode
     WHERE pp.id_peserta='$id_siswa'
     ORDER BY pp.created_at DESC LIMIT 1"
));

$jadwal = null;
if ($periode) {
    $jadwal = mysqli_query($conn,
        "SELECT * FROM jadwal_diklat
         WHERE id_periode='{$periode['id_periode']}'
         ORDER BY tanggal ASC"
    );
}

$evaluasi = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM evaluasi WHERE id_siswa='$id_siswa' AND dikonfirmasi_admin=1 LIMIT 1"
));

$dokRevisi   = mysqli_query($conn,
    "SELECT jenis, catatan_admin FROM dokumen_pendaftaran
     WHERE id_siswa='$id_siswa' AND status_verifikasi='revisi'"
);
$adaRevisi   = mysqli_num_rows($dokRevisi) > 0;
$jmlDokValid = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS jml FROM dokumen_pendaftaran WHERE id_siswa='$id_siswa' AND status_verifikasi='valid'"
))['jml'];
$jmlDokTotal = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS jml FROM dokumen_pendaftaran WHERE id_siswa='$id_siswa'"
))['jml'];

$timeline_steps = [
    'calon'         => ['label' => 'Mendaftar',     'icon' => '📝', 'desc' => 'Data diterima, menunggu verifikasi admin'],
    'terverifikasi' => ['label' => 'Terverifikasi', 'icon' => '✅', 'desc' => 'Dokumen valid, menunggu periode diklat'],
    'peserta'       => ['label' => 'Peserta',        'icon' => '🎓', 'desc' => 'Aktif mengikuti kegiatan diklat'],
    'lulus'         => ['label' => 'Lulus',          'icon' => '🏆', 'desc' => 'Dinyatakan lulus program diklat'],
];
$status_order = ['calon', 'terverifikasi', 'peserta', 'lulus'];
$status_now   = $siswa['status'] === 'tidak_lulus' ? 'lulus' : $siswa['status'];
$current_idx  = array_search($status_now, $status_order);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Siswa — Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;}
        :root{--navy:#0d1b2a;--yellow:#ffc107;--gray-bg:#f0f2f5;--gray-border:#e9ecef;}
        body{font-family:'Inter',Arial,sans-serif;background:var(--gray-bg);margin:0;padding:0;color:#333;}

        /* TOPBAR */
        .m-topbar{position:sticky;top:0;z-index:100;background:var(--navy);height:56px;
            display:flex;align-items:center;justify-content:space-between;padding:0 16px;
            box-shadow:0 2px 8px rgba(0,0,0,0.2);}
        .m-topbar-left{display:flex;align-items:center;gap:10px;}
        .m-topbar-brand{display:flex;align-items:center;gap:8px;color:var(--yellow);
            font-weight:700;font-size:15px;text-decoration:none;}
        .m-topbar-brand img{height:26px;}
        .m-menu-btn{background:none;border:none;color:rgba(255,255,255,0.8);
            font-size:22px;cursor:pointer;padding:4px 8px;line-height:1;}
        .m-topbar-right{display:flex;align-items:center;gap:10px;}
        .m-username{font-size:12px;color:rgba(255,255,255,0.65);}
        .m-logout-btn{background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);
            color:#ff8a8a;border-radius:6px;padding:5px 10px;font-size:12px;font-weight:600;cursor:pointer;}
        .m-logout-btn:hover{background:#dc3545;color:#fff;}

        /* DRAWER */
        .m-drawer{position:fixed;top:0;left:0;bottom:0;width:260px;background:var(--navy);
            z-index:200;transform:translateX(-100%);transition:transform 0.25s ease;
            display:flex;flex-direction:column;}
        .m-drawer.open{transform:translateX(0);}
        .m-drawer-header{height:56px;display:flex;align-items:center;gap:10px;padding:0 20px;
            border-bottom:1px solid rgba(255,255,255,0.1);color:var(--yellow);font-weight:700;font-size:15px;}
        .m-drawer-header img{height:26px;}
        .m-drawer-menu{flex:1;list-style:none;margin:0;padding:12px 0;}
        .m-drawer-menu li a{display:flex;align-items:center;gap:12px;padding:13px 20px;
            color:rgba(255,255,255,0.75);text-decoration:none;font-size:14px;font-weight:500;transition:all 0.15s;}
        .m-drawer-menu li a:hover,.m-drawer-menu li a.active{background:rgba(255,255,255,0.08);color:#fff;}
        .m-drawer-menu li a.revisi-link{color:#ff8a8a!important;}
        .m-drawer-footer{padding:16px 20px;border-top:1px solid rgba(255,255,255,0.1);}
        .m-drawer-logout{display:flex;align-items:center;gap:10px;width:100%;
            background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.25);
            color:#ff8a8a;border-radius:8px;padding:10px 14px;font-size:13px;font-weight:600;cursor:pointer;}
        .m-drawer-logout:hover{background:#dc3545;color:#fff;}
        .m-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:150;}
        .m-overlay.show{display:block;}

        /* KONTEN */
        .m-content{padding:14px;max-width:520px;margin:0 auto;}

        /* CARD */
        .s-card{background:#fff;border-radius:12px;padding:16px;margin-bottom:14px;
            box-shadow:0 2px 8px rgba(0,0,0,0.05);border:1px solid var(--gray-border);}
        .s-card-title{font-size:11px;font-weight:700;color:#8a929a;text-transform:uppercase;
            letter-spacing:0.6px;margin:0 0 12px;padding-bottom:10px;border-bottom:1.5px solid var(--gray-bg);}

        /* ALERT REVISI */
        .revisi-alert{background:#f8d7da;border-left:4px solid #dc3545;border-radius:10px;
            padding:14px 16px;margin-bottom:14px;}
        .revisi-alert .ra-title{font-weight:700;color:#842029;font-size:14px;margin-bottom:8px;}
        .revisi-alert .ra-list{font-size:13px;color:#6a1922;padding-left:16px;margin:0 0 10px;line-height:1.7;}
        .revisi-alert .ra-batas{font-size:12px;color:#6a1922;margin-bottom:10px;}
        .btn-revisi{display:inline-block;background:#dc3545;color:#fff;border-radius:8px;
            padding:9px 16px;font-weight:700;font-size:13px;text-decoration:none;}

        /* PROFIL */
        .profil-row{display:flex;align-items:center;gap:14px;margin-bottom:14px;}
        .profil-avatar{width:50px;height:50px;border-radius:50%;background:var(--navy);
            color:var(--yellow);font-size:20px;font-weight:800;display:flex;
            align-items:center;justify-content:center;flex-shrink:0;}
        .profil-nama{font-size:16px;font-weight:700;color:var(--navy);}
        .profil-username{font-size:12px;color:#8a929a;}

        /* STATUS BADGE */
        .status-badge{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;
            border-radius:50px;font-size:13px;font-weight:700;margin-bottom:16px;}
        .status-badge.calon{background:#e9ecef;color:#495057;}
        .status-badge.terverifikasi{background:#cfe2ff;color:#084298;}
        .status-badge.peserta{background:#ffe5d0;color:#7d3b08;}
        .status-badge.lulus{background:#d1e7dd;color:#0a3622;}
        .status-badge.tidak_lulus{background:#f8d7da;color:#842029;}

        /* TIMELINE */
        .timeline{list-style:none;margin:0;padding:0;position:relative;}
        .timeline::before{content:'';position:absolute;left:18px;top:0;bottom:0;
            width:2px;background:var(--gray-border);}
        .timeline li{position:relative;padding:0 0 16px 46px;}
        .timeline li:last-child{padding-bottom:0;}
        .tl-dot{position:absolute;left:8px;top:1px;width:20px;height:20px;border-radius:50%;
            display:flex;align-items:center;justify-content:center;font-size:10px;z-index:1;}
        .tl-dot.done{background:#198754;color:#fff;}
        .tl-dot.current{background:#0d6efd;color:#fff;box-shadow:0 0 0 3px #cfe2ff;}
        .tl-dot.future{background:var(--gray-border);color:#adb5bd;}
        .tl-dot.fail{background:#dc3545;color:#fff;}
        .tl-label{font-size:13px;font-weight:700;color:var(--navy);margin-bottom:2px;}
        .tl-label.future{color:#adb5bd;font-weight:500;}
        .tl-label.current{color:#0d6efd;}
        .tl-desc{font-size:12px;color:#8a929a;line-height:1.4;}
        .tl-now-badge{font-size:10px;background:#0d6efd;color:#fff;border-radius:8px;padding:1px 6px;margin-left:4px;}

        /* DOKUMEN BAR */
        .dok-bar-row{display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;}
        .dok-bar-wrap{height:8px;background:var(--gray-border);border-radius:4px;}
        .dok-bar-fill{height:100%;border-radius:4px;background:#198754;}

        /* INFO ROW */
        .info-row{display:flex;justify-content:space-between;align-items:flex-start;
            gap:8px;font-size:13px;padding:9px 0;border-bottom:1px solid var(--gray-bg);}
        .info-row:last-child{border-bottom:none;}
        .info-row .il{color:#8a929a;flex-shrink:0;min-width:95px;}
        .info-row .iv{font-weight:600;color:var(--navy);text-align:right;word-break:break-word;}

        /* RUNDOWN TABEL */
        .rundown-table{font-size:13px;margin:0;}
        .rundown-table thead th{background:var(--navy);color:#fff;font-size:12px;
            font-weight:600;white-space:nowrap;padding:9px 10px;border:none;}
        .rundown-table tbody td{padding:9px 10px;vertical-align:top;border-color:var(--gray-bg);}
        .rundown-table tbody tr:last-child td{border-bottom:none;}
        .td-tgl{white-space:nowrap;font-weight:600;color:var(--navy);font-size:12px;}
        .td-keg{font-weight:600;}
        .td-ket{font-size:12px;color:#8a929a;margin-top:2px;}

        /* NILAI */
        .nilai-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;}
        .nilai-box{background:var(--gray-bg);border-radius:10px;padding:14px 10px;text-align:center;}
        .nval{font-size:28px;font-weight:800;color:var(--navy);line-height:1;}
        .nlbl{font-size:12px;color:#8a929a;margin-top:5px;}
        .rata-row{display:flex;justify-content:space-between;align-items:center;
            background:var(--gray-bg);border-radius:10px;padding:12px 16px;flex-wrap:wrap;gap:8px;}
        .rata-angka{font-size:30px;font-weight:800;color:var(--navy);line-height:1;}
        .rata-lbl{font-size:12px;color:#8a929a;margin-bottom:4px;}
        .hasil-badge{padding:9px 18px;border-radius:50px;font-weight:700;font-size:14px;white-space:nowrap;}
        .hasil-lulus{background:#d1e7dd;color:#0a3622;}
        .hasil-tidak{background:#f8d7da;color:#842029;}
        .catatan-box{margin-top:12px;background:#f8f9fa;border-radius:8px;
            padding:11px 14px;font-size:13px;color:#495057;line-height:1.6;}

        /* FASILITAS */
        .fas-box{margin-top:12px;font-size:13px;color:#495057;line-height:1.7;}
        .fas-box .fb-label{font-size:11px;font-weight:700;color:#8a929a;
            text-transform:uppercase;letter-spacing:0.5px;margin-bottom:5px;}
        .kebutuhan-box{margin-top:12px;background:#fff3cd;border-radius:10px;
            padding:12px 14px;font-size:13px;color:#856404;line-height:1.7;}
        .kebutuhan-box .kb-label{font-size:11px;font-weight:700;color:#664d03;
            text-transform:uppercase;margin-bottom:5px;}

        /* EMPTY STATE */
        .empty-state{text-align:center;padding:28px 16px;color:#8a929a;}
        .empty-state .es-icon{font-size:36px;margin-bottom:10px;}
        .empty-state p{font-size:13px;margin:0;line-height:1.6;}
    </style>
</head>
<body>

<div class="m-overlay" id="overlay"></div>

<nav class="m-drawer" id="drawer">
    <div class="m-drawer-header">
        <img src="../img/logo.png" alt="Logo"> Gemilang
    </div>
    <ul class="m-drawer-menu">
        <li><a href="dashboard_peserta.php" class="active">🏠 Dashboard</a></li>
        <li><a href="../ganti_password.php">🔑 Ganti Password</a></li>
        <?php if ($adaRevisi): ?>
        <li><a href="revisi_dokumen.php" class="revisi-link">⚠️ Revisi Dokumen</a></li>
        <?php endif; ?>
    </ul>
    <div class="m-drawer-footer">
        <button class="m-drawer-logout" id="btnLogoutDrawer">🚪 Logout</button>
    </div>
</nav>

<header class="m-topbar">
    <div class="m-topbar-left">
        <button class="m-menu-btn" id="menuBtn">☰</button>
        <a class="m-topbar-brand" href="dashboard_peserta.php">
            <img src="../img/logo.png" alt="Logo"> Gemilang
        </a>
    </div>
    <div class="m-topbar-right">
        <span class="m-username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        <button class="m-logout-btn" id="btnLogout">Keluar</button>
    </div>
</header>

<div class="m-content">

    <?php if ($adaRevisi): ?>
    <div class="revisi-alert">
        <div class="ra-title">⚠️ Dokumen Perlu Direvisi!</div>
        <ul class="ra-list">
        <?php
        mysqli_data_seek($dokRevisi, 0);
        while ($r = mysqli_fetch_assoc($dokRevisi)) {
            echo "<li><strong>" . strtoupper($r['jenis']) . "</strong>"
               . ($r['catatan_admin'] ? ": " . htmlspecialchars($r['catatan_admin']) : '') . "</li>";
        }
        ?>
        </ul>
        <?php if ($siswa['batas_revisi']): ?>
        <p class="ra-batas">Batas revisi: <strong><?php echo $siswa['batas_revisi']; ?></strong></p>
        <?php endif; ?>
        <a href="revisi_dokumen.php" class="btn-revisi">Perbaiki Sekarang →</a>
    </div>
    <?php endif; ?>

    <!-- PROFIL & STATUS -->
    <div class="s-card">
        <div class="profil-row">
            <div class="profil-avatar"><?php echo strtoupper(mb_substr($siswa['nama'], 0, 1)); ?></div>
            <div>
                <div class="profil-nama"><?php echo htmlspecialchars($siswa['nama']); ?></div>
                <div class="profil-username">@<?php echo htmlspecialchars($_SESSION['username']); ?></div>
            </div>
        </div>
        <?php
        $bi = ['calon'=>'📝','terverifikasi'=>'✅','peserta'=>'🎓','lulus'=>'🏆','tidak_lulus'=>'❌'][$siswa['status']] ?? '❓';
        ?>
        <span class="status-badge <?php echo $siswa['status']; ?>">
            <?php echo $bi . ' ' . ucfirst(str_replace('_',' ',$siswa['status'])); ?>
        </span>
        <p class="s-card-title">Progres Pendaftaran</p>
        <?php if ($siswa['status'] === 'tidak_lulus'): ?>
        <div style="background:#f8d7da;border-radius:8px;padding:10px 14px;font-size:13px;color:#842029;margin-bottom:12px;">
            ❌ Dinyatakan <strong>tidak lulus</strong>. Hubungi admin untuk info lebih lanjut.
        </div>
        <?php endif; ?>
        <ul class="timeline">
        <?php foreach ($status_order as $idx => $st):
            if ($siswa['status'] === 'tidak_lulus' && $st === 'lulus') { $dc='fail'; $lc=''; $ic='❌'; $dd='Tidak lulus pada program diklat ini'; }
            elseif ($idx < $current_idx) { $dc='done'; $lc=''; $ic='✓'; $dd=$timeline_steps[$st]['desc']; }
            elseif ($idx === $current_idx) { $dc='current'; $lc='current'; $ic=$timeline_steps[$st]['icon']; $dd=$timeline_steps[$st]['desc']; }
            else { $dc='future'; $lc='future'; $ic='○'; $dd=$timeline_steps[$st]['desc']; }
        ?>
        <li>
            <div class="tl-dot <?php echo $dc; ?>"><?php echo $ic; ?></div>
            <div class="tl-label <?php echo $lc; ?>">
                <?php echo $timeline_steps[$st]['label']; ?>
                <?php if ($idx === $current_idx && $siswa['status'] !== 'tidak_lulus'): ?>
                <span class="tl-now-badge">Saat ini</span>
                <?php endif; ?>
            </div>
            <div class="tl-desc"><?php echo $dd; ?></div>
        </li>
        <?php endforeach; ?>
        </ul>
    </div>

    <!-- STATUS DOKUMEN -->
    <?php if (in_array($siswa['status'], ['calon','terverifikasi'])): ?>
    <div class="s-card">
        <p class="s-card-title">Dokumen Pendaftaran</p>
        <?php $pct = $jmlDokTotal > 0 ? round($jmlDokValid/$jmlDokTotal*100) : 0; ?>
        <div class="dok-bar-row">
            <span style="color:#8a929a;"><?php echo "$jmlDokValid / $jmlDokTotal dokumen valid"; ?></span>
            <span style="font-weight:700;color:<?php echo $pct==100?'#198754':'#0d6efd';?>"><?php echo $pct; ?>%</span>
        </div>
        <div class="dok-bar-wrap"><div class="dok-bar-fill" style="width:<?php echo $pct;?>%;"></div></div>
        <?php if ($siswa['status']==='calon' && !$adaRevisi): ?>
        <p style="font-size:12px;color:#8a929a;margin:8px 0 0;">⏳ Admin sedang memverifikasi dokumen Anda.</p>
        <?php elseif ($siswa['status']==='terverifikasi'): ?>
        <p style="font-size:12px;color:#198754;margin:8px 0 0;">✅ Dokumen diverifikasi. Menunggu penetapan periode.</p>
        <?php endif; ?>
        <?php if ($adaRevisi): ?>
        <a href="revisi_dokumen.php" style="display:block;margin-top:10px;font-size:13px;color:#dc3545;font-weight:700;">⚠️ Perbaiki dokumen yang perlu revisi →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- INFO DIKLAT -->
    <?php if ($periode): ?>
    <div class="s-card">
        <p class="s-card-title">Informasi Diklat</p>
        <div class="info-row"><span class="il">Periode</span><span class="iv"><?php echo $periode['tahun']; ?> — Gel. <?php echo $periode['gelombang']; ?></span></div>
        <div class="info-row">
            <span class="il">Tanggal</span>
            <span class="iv"><?php echo date('d M Y', strtotime($periode['tanggal_mulai'])); ?> s/d <?php echo date('d M Y', strtotime($periode['tanggal_selesai'])); ?></span>
        </div>
        <div class="info-row"><span class="il">Lokasi</span><span class="iv"><?php echo htmlspecialchars($periode['lokasi_spesifik'] ?: '—'); ?></span></div>
        <?php if ($periode['lokasi_fasilitas']): ?>
        <div class="info-row"><span class="il">Ambil Fasilitas</span><span class="iv"><?php echo htmlspecialchars($periode['lokasi_fasilitas']); ?></span></div>
        <?php endif; ?>
        <?php if ($periode['fasilitas']): ?>
        <div class="fas-box"><div class="fb-label">Fasilitas</div><?php echo nl2br(htmlspecialchars($periode['fasilitas'])); ?></div>
        <?php endif; ?>
        <?php if ($periode['info_kebutuhan']): ?>
        <div class="kebutuhan-box"><div class="kb-label">🎒 Yang Perlu Dibawa</div><?php echo nl2br(htmlspecialchars($periode['info_kebutuhan'])); ?></div>
        <?php endif; ?>
    </div>

    <!-- RUNDOWN — TABEL -->
    <?php if ($jadwal && mysqli_num_rows($jadwal) > 0): ?>
    <div class="s-card">
        <p class="s-card-title">Rundown Kegiatan</p>
        <div class="table-responsive">
            <table class="table table-bordered rundown-table mb-0">
                <thead>
                    <tr>
                        <th style="width:95px;">Tanggal</th>
                        <th>Kegiatan</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($j = mysqli_fetch_assoc($jadwal)): ?>
                <tr>
                    <td class="td-tgl"><?php echo date('d M Y', strtotime($j['tanggal'])); ?></td>
                    <td><div class="td-keg"><?php echo htmlspecialchars($j['kegiatan']); ?></div></td>
                    <td>
                        <?php if ($j['keterangan']): ?>
                        <div class="td-ket"><?php echo htmlspecialchars($j['keterangan']); ?></div>
                        <?php else: ?><span style="color:#c8cdd3;">—</span><?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif (in_array($siswa['status'],['peserta','terverifikasi'])): ?>
    <div class="s-card">
        <div class="empty-state">
            <div class="es-icon">📋</div>
            <p>Informasi diklat akan muncul setelah admin menetapkan periode.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- HASIL EVALUASI — di bawah rundown -->
    <?php if ($evaluasi): ?>
    <div class="s-card">
        <p class="s-card-title">Hasil Evaluasi Diklat</p>
        <div class="nilai-grid">
            <div class="nilai-box"><div class="nval"><?php echo $evaluasi['nilai_fisik']; ?></div><div class="nlbl">Fisik</div></div>
            <div class="nilai-box"><div class="nval"><?php echo $evaluasi['nilai_disiplin']; ?></div><div class="nlbl">Disiplin</div></div>
            <div class="nilai-box"><div class="nval"><?php echo $evaluasi['nilai_teori']; ?></div><div class="nlbl">Teori</div></div>
            <div class="nilai-box"><div class="nval"><?php echo $evaluasi['nilai_praktik']; ?></div><div class="nlbl">Praktik</div></div>
        </div>
        <div class="rata-row">
            <div><div class="rata-lbl">Rata-rata</div><div class="rata-angka"><?php echo $evaluasi['rata_rata']; ?></div></div>
            <?php if ($evaluasi['hasil']==='lulus'): ?>
            <span class="hasil-badge hasil-lulus">🏆 LULUS</span>
            <?php else: ?>
            <span class="hasil-badge hasil-tidak">❌ TIDAK LULUS</span>
            <?php endif; ?>
        </div>
        <?php if ($evaluasi['catatan']): ?>
        <div class="catatan-box"><strong>Catatan:</strong> <?php echo htmlspecialchars($evaluasi['catatan']); ?></div>
        <?php endif; ?>
    </div>
    <?php elseif (in_array($siswa['status'],['peserta','lulus','tidak_lulus'])): ?>
    <div class="s-card">
        <p class="s-card-title">Hasil Evaluasi Diklat</p>
        <div class="empty-state">
            <div class="es-icon">⏳</div>
            <p>Nilai sedang dalam proses input oleh admin.<br>Akan muncul setelah dikonfirmasi.</p>
        </div>
    </div>
    <?php endif; ?>

    <div style="height:24px;"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
const menuBtn = document.getElementById('menuBtn');
const drawer  = document.getElementById('drawer');
const overlay = document.getElementById('overlay');
function openDrawer()  { drawer.classList.add('open'); overlay.classList.add('show'); }
function closeDrawer() { drawer.classList.remove('open'); overlay.classList.remove('show'); }
menuBtn.addEventListener('click', openDrawer);
overlay.addEventListener('click', closeDrawer);
function doLogout() {
    Swal.fire({
        title:'Keluar dari Sistem?', text:'Sesi Anda akan diakhiri.', icon:'warning',
        showCancelButton:true, confirmButtonColor:'#dc3545', cancelButtonColor:'#6c757d',
        confirmButtonText:'Ya, Logout', cancelButtonText:'Batal', reverseButtons:true
    }).then(r => { if (r.isConfirmed) window.location.href = '../logout.php'; });
}
document.getElementById('btnLogout').addEventListener('click', doLogout);
document.getElementById('btnLogoutDrawer').addEventListener('click', doLogout);
</script>
</body>
</html>