<?php
session_start();
include "koneksi.php";

/* ============================================================
   GUARD: hanya siswa
   ============================================================ */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: login.php");
    exit;
}

$id_akun = (int) $_SESSION['id_akun'];

$siswa = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT s.*
    FROM siswa s
    WHERE s.id_akun = '$id_akun'
    LIMIT 1
"));

if (!$siswa) {
    echo "Data siswa tidak ditemukan.";
    exit;
}

$id_siswa = (int) $siswa['id_peserta'];

/* ============================================================
   DATA PERIODE / STATUS
   ============================================================ */
$riwayat = [];
$q = mysqli_query($conn, "
    SELECT pp.*, p.tahun, p.gelombang, p.tanggal_mulai, p.tanggal_selesai, p.status AS status_periode,
           p.lokasi_spesifik, p.fasilitas, p.info_kebutuhan, p.biaya,
           e.nilai_teori, e.nilai_fisik, e.nilai_disiplin, e.nilai_praktik, e.rata_rata, e.hasil, e.catatan,
           e.dikonfirmasi_admin, e.tgl_input
    FROM peserta_periode pp
    JOIN periode_diklat p ON pp.id_periode = p.id_periode
    LEFT JOIN evaluasi e ON e.id_siswa = pp.id_peserta AND e.id_periode = pp.id_periode
    WHERE pp.id_peserta = '$id_siswa'
    ORDER BY p.tahun DESC, p.gelombang DESC
");
while ($r = mysqli_fetch_assoc($q)) {
    $riwayat[] = $r;
}

/* Dokumen */
$dokumen = [];
$q2 = mysqli_query($conn, "
    SELECT jenis, status_verifikasi, catatan_admin, tgl_revisi
    FROM dokumen_pendaftaran
    WHERE id_siswa = '$id_siswa'
    ORDER BY FIELD(jenis, 'ktp','ijazah','kk','skck','surat_kesehatan','pembayaran')
");
while ($r = mysqli_fetch_assoc($q2)) {
    $dokumen[] = $r;
}

/* Notifikasi */
$notif_terakhir = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT *
    FROM notifikasi
    WHERE id_siswa = '$id_siswa'
    ORDER BY tgl_kirim DESC
    LIMIT 1
"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Status Pendaftaran dan Kelulusan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/status_siswa.css">
</head>
<body>

<nav class="navbar navbar-dark nav-mobile">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">Status Siswa</span>
        <div class="d-flex gap-2">
            <a href="dashboard_peserta.php" class="btn btn-sm btn-warning">Dashboard</a>
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>
</nav>

<div class="mobile-hero">
    <div class="container">
        <h4><?php echo htmlspecialchars($siswa['nama']); ?></h4>
        <p>PB-056 - status pendaftaran, revisi dokumen, dan hasil akhir kelulusan.</p>
    </div>
</div>

<div class="container py-3 mobile-wrap">
    <div class="card mobile-card mb-3">
        <div class="card-body">
            <div class="info-grid">
                <div>
                    <span class="mini-label">Username</span>
                    <div class="fw-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                </div>
                <div>
                    <span class="mini-label">Status Akun</span>
                    <div class="fw-semibold">
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $siswa['status'] ?? '-'))); ?>
                    </div>
                </div>
                <div>
                    <span class="mini-label">Email</span>
                    <div class="fw-semibold"><?php echo htmlspecialchars($siswa['email']); ?></div>
                </div>
                <div>
                    <span class="mini-label">No. Telp</span>
                    <div class="fw-semibold"><?php echo htmlspecialchars($siswa['no_telp']); ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($notif_terakhir): ?>
    <div class="alert alert-warning alert-rounded">
        <strong>Notifikasi terbaru:</strong><br>
        <?php echo htmlspecialchars($notif_terakhir['pesan']); ?>
    </div>
    <?php endif; ?>

    <div class="card mobile-card mb-3">
        <div class="card-header mobile-card-header">Status Dokumen</div>
        <div class="card-body">
            <?php if (!$dokumen): ?>
                <div class="text-muted">Belum ada dokumen yang tercatat.</div>
            <?php else: ?>
                <?php foreach ($dokumen as $doc): ?>
                    <div class="doc-row">
                        <div>
                            <div class="fw-semibold text-uppercase"><?php echo htmlspecialchars($doc['jenis']); ?></div>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($doc['catatan_admin'] ?: '-'); ?>
                            </small>
                        </div>
                        <div class="text-end">
                            <?php if ($doc['status_verifikasi'] === 'valid'): ?>
                                <span class="badge bg-success">Valid</span>
                            <?php elseif ($doc['status_verifikasi'] === 'revisi'): ?>
                                <span class="badge bg-danger">Revisi</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                            <?php if (!empty($doc['tgl_revisi'])): ?>
                                <div class="small text-muted mt-1"><?php echo htmlspecialchars($doc['tgl_revisi']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$riwayat): ?>
        <div class="card mobile-card text-center py-5">
            <div class="text-muted">Belum ada data periode diklat.</div>
        </div>
    <?php else: ?>
        <?php foreach ($riwayat as $r): ?>
            <div class="card mobile-card mb-3">
                <div class="card-header mobile-card-header d-flex justify-content-between align-items-center">
                    <span>Periode <?php echo htmlspecialchars($r['tahun']); ?> - G<?php echo htmlspecialchars($r['gelombang']); ?></span>
                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars(ucfirst($r['status_periode'])); ?></span>
                </div>
                <div class="card-body">
                    <div class="mini-label mb-1">Tanggal Diklat</div>
                    <div class="fw-semibold mb-2">
                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($r['tanggal_mulai']))); ?>
                        s/d
                        <?php echo htmlspecialchars(date('d/m/Y', strtotime($r['tanggal_selesai']))); ?>
                    </div>

                    <div class="mini-label mb-1">Lokasi</div>
                    <div class="fw-semibold mb-2"><?php echo htmlspecialchars($r['lokasi_spesifik']); ?></div>

                    <div class="mini-label mb-1">Fasilitas</div>
                    <div class="small mb-2"><?php echo htmlspecialchars($r['fasilitas']); ?></div>

                    <div class="mini-label mb-1">Kebutuhan</div>
                    <div class="small mb-3"><?php echo htmlspecialchars($r['info_kebutuhan'] ?: '-'); ?></div>

                    <div class="mini-label mb-1">Status Pendaftaran</div>
                    <div class="fw-semibold mb-3">
                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $siswa['status'] ?? '-'))); ?>
                    </div>

                    <div class="mini-label mb-1">Hasil Kelulusan</div>
                    <?php if ($r['rata_rata'] === null): ?>
                        <div class="text-muted">Belum ada hasil evaluasi.</div>
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="fw-semibold">Rata-rata: <?php echo number_format((float)$r['rata_rata'], 2, ',', '.'); ?></div>
                            <?php if ($r['hasil'] === 'lulus'): ?>
                                <span class="badge bg-success">LULUS</span>
                            <?php else: ?>
                                <span class="badge bg-danger">TIDAK LULUS</span>
                            <?php endif; ?>
                        </div>

                        <div class="score-grid mt-3">
                            <div class="score-box">
                                <span>Teori</span>
                                <strong><?php echo number_format((float)$r['nilai_teori'], 2, ',', '.'); ?></strong>
                            </div>
                            <div class="score-box">
                                <span>Fisik</span>
                                <strong><?php echo number_format((float)$r['nilai_fisik'], 2, ',', '.'); ?></strong>
                            </div>
                            <div class="score-box">
                                <span>Disiplin</span>
                                <strong><?php echo number_format((float)$r['nilai_disiplin'], 2, ',', '.'); ?></strong>
                            </div>
                            <div class="score-box">
                                <span>Praktik</span>
                                <strong><?php echo number_format((float)$r['nilai_praktik'], 2, ',', '.'); ?></strong>
                            </div>
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                <?php echo htmlspecialchars($r['dikonfirmasi_admin'] ? 'Sudah dikonfirmasi admin' : 'Belum dikonfirmasi admin'); ?>
                            </small>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($r['catatan'])): ?>
                        <div class="note-box mt-3">
                            <strong>Catatan:</strong><br>
                            <?php echo nl2br(htmlspecialchars($r['catatan'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
