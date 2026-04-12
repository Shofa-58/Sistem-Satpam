<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'ceo') {
    header("Location: login.php");
    exit;
}

function safe_date($date, $format = 'd M Y') {
    if (!$date) return '-';
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '-';
}

function safe_file_link($path) {
    if (!$path) return '-';
    $name = basename($path);
    return '<a href="' . htmlspecialchars($path) . '" target="_blank" rel="noopener">' . htmlspecialchars($name) . '</a>';
}

$periode_list = mysqli_query($conn, "SELECT * FROM periode_diklat ORDER BY tahun DESC, gelombang DESC");

$id_periode_aktif = isset($_GET['periode']) ? (int) $_GET['periode'] : 0;
if (!$id_periode_aktif) {
    $tmp = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_periode FROM periode_diklat ORDER BY tahun DESC, gelombang DESC LIMIT 1"));
    $id_periode_aktif = $tmp ? (int) $tmp['id_periode'] : 0;
}

$periode = null;
if ($id_periode_aktif) {
    $periode_q = mysqli_query($conn, "SELECT * FROM periode_diklat WHERE id_periode = '$id_periode_aktif' LIMIT 1");
    $periode = mysqli_fetch_assoc($periode_q);
}

$laporan_list = [];
if ($id_periode_aktif) {
    $q_laporan = mysqli_query($conn, "
        SELECT l.*, p.tahun, p.gelombang, p.status,
               COUNT(DISTINCT e.id_evaluasi) AS total_nilai,
               SUM(CASE WHEN e.hasil = 'lulus' THEN 1 ELSE 0 END) AS total_lulus,
               SUM(CASE WHEN e.hasil = 'tidak_lulus' THEN 1 ELSE 0 END) AS total_tidak_lulus
        FROM laporan l
        LEFT JOIN periode_diklat p ON l.id_periode = p.id_periode
        LEFT JOIN evaluasi e ON e.id_periode = l.id_periode
        WHERE l.id_periode = '$id_periode_aktif'
        GROUP BY l.id_laporan
        ORDER BY l.tgl_generate DESC
    ");
    while ($row = mysqli_fetch_assoc($q_laporan)) {
        $laporan_list[] = $row;
    }
}

$detail_laporan = null;
if (isset($_GET['lihat'])) {
    $id_laporan = (int) $_GET['lihat'];
    $q_detail = mysqli_query($conn, "
        SELECT l.*, p.tahun, p.gelombang, p.tanggal_mulai, p.tanggal_selesai, p.lokasi_spesifik, p.fasilitas, p.info_kebutuhan,
               COUNT(DISTINCT e.id_evaluasi) AS total_nilai,
               SUM(CASE WHEN e.hasil = 'lulus' THEN 1 ELSE 0 END) AS total_lulus,
               SUM(CASE WHEN e.hasil = 'tidak_lulus' THEN 1 ELSE 0 END) AS total_tidak_lulus
        FROM laporan l
        LEFT JOIN periode_diklat p ON l.id_periode = p.id_periode
        LEFT JOIN evaluasi e ON e.id_periode = l.id_periode
        WHERE l.id_laporan = '$id_laporan'
        GROUP BY l.id_laporan
        LIMIT 1
    ");
    $detail_laporan = mysqli_fetch_assoc($q_detail);
}

$stat_total_laporan = 0;
$stat_total_lulus = 0;
$stat_total_tidak_lulus = 0;
$stat_total_peserta = 0;

if ($id_periode_aktif) {
    $tmp1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS j FROM laporan WHERE id_periode = '$id_periode_aktif'"));
    $tmp2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS j FROM evaluasi WHERE id_periode = '$id_periode_aktif' AND hasil = 'lulus'"));
    $tmp3 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS j FROM evaluasi WHERE id_periode = '$id_periode_aktif' AND hasil = 'tidak_lulus'"));
    $tmp4 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS j FROM peserta_periode WHERE id_periode = '$id_periode_aktif'"));

    $stat_total_laporan = (int) ($tmp1['j'] ?? 0);
    $stat_total_lulus = (int) ($tmp2['j'] ?? 0);
    $stat_total_tidak_lulus = (int) ($tmp3['j'] ?? 0);
    $stat_total_peserta = (int) ($tmp4['j'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CEO - Laporan Kegiatan Diklat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/dashboard_ceo.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark ceo-nav">
    <div class="container-fluid">
        <span class="navbar-brand fw-semibold">CEO • Laporan Diklat</span>
        <div class="ms-auto d-flex gap-2">
            <a href="dashboard_ceo.php" class="btn btn-sm btn-light">Refresh</a>
            <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="ceo-hero mb-4">
        <h3 class="mb-1">Ringkasan Laporan</h3>
        <p class="mb-0">Pilih periode untuk melihat laporan kegiatan dan hasil diklat.</p>
    </div>

    <div class="periode-strip mb-4">
        <?php if (mysqli_num_rows($periode_list) > 0): ?>
            <?php mysqli_data_seek($periode_list, 0); ?>
            <?php while ($p = mysqli_fetch_assoc($periode_list)): ?>
                <a class="periode-pill <?php echo ($id_periode_aktif == (int)$p['id_periode']) ? 'active' : ''; ?>"
                   href="?periode=<?php echo (int)$p['id_periode']; ?>">
                    <?php echo htmlspecialchars($p['tahun']); ?> • G<?php echo htmlspecialchars($p['gelombang']); ?>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <span class="text-muted">Belum ada periode.</span>
        <?php endif; ?>
    </div>

    <?php if (!$periode): ?>
        <div class="ceo-card text-center py-5 text-muted">
            Belum ada periode diklat yang tersedia.
        </div>
    <?php else: ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stat_total_laporan; ?></div>
                    <div class="stat-label">Laporan</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stat_total_peserta; ?></div>
                    <div class="stat-label">Peserta</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card good">
                    <div class="stat-value"><?php echo $stat_total_lulus; ?></div>
                    <div class="stat-label">Lulus</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card bad">
                    <div class="stat-value"><?php echo $stat_total_tidak_lulus; ?></div>
                    <div class="stat-label">Tidak Lulus</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="ceo-card h-100">
                    <div class="card-head">
                        <h5 class="mb-0">Daftar Laporan</h5>
                        <small class="text-muted">Periode <?php echo htmlspecialchars($periode['tahun']); ?> G<?php echo htmlspecialchars($periode['gelombang']); ?></small>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Lulus</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($laporan_list)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada laporan untuk periode ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($laporan_list as $lap): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo safe_date($lap['tgl_generate'], 'd/m/Y H:i'); ?></td>
                                    <td>
                                        <?php if ((int)$lap['dikonfirmasi_kepala'] === 1): ?>
                                            <span class="badge text-bg-success">Dikonfirmasi</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-warning">Menunggu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo (int)($lap['total_lulus'] ?? 0); ?> / <?php echo (int)($lap['total_nilai'] ?? 0); ?></td>
                                    <td>
                                        <a href="?periode=<?php echo (int)$id_periode_aktif; ?>&lihat=<?php echo (int)$lap['id_laporan']; ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="ceo-card h-100">
                    <div class="card-head">
                        <h5 class="mb-0">Detail Laporan</h5>
                        <small class="text-muted">Ringkas</small>
                    </div>
                    <?php if ($detail_laporan): ?>
                        <div class="detail-box mb-3">
                            <div class="detail-row"><span>Periode</span><strong><?php echo htmlspecialchars($detail_laporan['tahun']); ?> • G<?php echo htmlspecialchars($detail_laporan['gelombang']); ?></strong></div>
                            <div class="detail-row"><span>Tanggal</span><strong><?php echo safe_date($detail_laporan['tgl_generate'], 'd/m/Y H:i'); ?></strong></div>
                            <div class="detail-row"><span>Lulus</span><strong><?php echo (int)($detail_laporan['total_lulus'] ?? 0); ?></strong></div>
                            <div class="detail-row"><span>Tidak Lulus</span><strong><?php echo (int)($detail_laporan['total_tidak_lulus'] ?? 0); ?></strong></div>
                            <div class="detail-row"><span>Konfirmasi Kepala</span><strong><?php echo ((int)$detail_laporan['dikonfirmasi_kepala'] === 1) ? 'Sudah' : 'Belum'; ?></strong></div>
                            <div class="detail-row"><span>CEO Lihat</span><strong><?php echo ((int)$detail_laporan['dilihat_ceo'] === 1) ? 'Sudah' : 'Belum'; ?></strong></div>
                        </div>

                        <div class="mini-section mb-3">
                            <h6 class="mb-2">Info Periode</h6>
                            <p class="mb-1"><strong>Lokasi:</strong> <?php echo htmlspecialchars($detail_laporan['lokasi_spesifik'] ?? '-'); ?></p>
                            <p class="mb-1"><strong>Mulai:</strong> <?php echo safe_date($detail_laporan['tanggal_mulai'] ?? null); ?></p>
                            <p class="mb-1"><strong>Selesai:</strong> <?php echo safe_date($detail_laporan['tanggal_selesai'] ?? null); ?></p>
                            <p class="mb-1"><strong>Fasilitas:</strong> <?php echo htmlspecialchars($detail_laporan['fasilitas'] ?? '-'); ?></p>
                            <p class="mb-0"><strong>Kebutuhan:</strong> <?php echo htmlspecialchars($detail_laporan['info_kebutuhan'] ?? '-'); ?></p>
                        </div>

                        <div class="mini-section">
                            <h6 class="mb-2">File Laporan</h6>
                            <ul class="list-unstyled mb-0 small file-list">
                                <li><strong>Polda:</strong> <?php echo safe_file_link($detail_laporan['file_laporan_polda'] ?? ''); ?></li>
                                <li><strong>Penilaian:</strong> <?php echo safe_file_link($detail_laporan['file_laporan_penilaian'] ?? ''); ?></li>
                                <li><strong>Surat:</strong> <?php echo safe_file_link($detail_laporan['file_surat_pernyataan'] ?? ''); ?></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="text-muted py-4 text-center">Pilih salah satu laporan untuk lihat detail.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
