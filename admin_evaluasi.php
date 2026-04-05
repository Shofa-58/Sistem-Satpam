<?php
session_start();
include "koneksi.php";
include "helpers.php";

/* ============================================================
   GUARD: hanya admin
   ============================================================ */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

/* ============================================================
   AMBIL SEMUA PERIODE
   ============================================================ */
$semua_periode = mysqli_query($conn,
    "SELECT * FROM periode_diklat ORDER BY tahun DESC, gelombang DESC"
);

/* Periode aktif */
$id_periode_aktif = isset($_GET['periode']) ? (int) $_GET['periode'] : 0;
if (!$id_periode_aktif) {
    $tmp = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_periode FROM periode_diklat
         ORDER BY tahun DESC, gelombang DESC LIMIT 1"
    ));
    $id_periode_aktif = $tmp ? (int) $tmp['id_periode'] : 0;
}

$periode = null;
if ($id_periode_aktif) {
    $periode = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM periode_diklat WHERE id_periode = '$id_periode_aktif'"
    ));
}

$pesan_sukses = '';
$pesan_error  = '';

/* ============================================================
   PROSES 1: KONFIRMASI NILAI SATU SISWA
   Mengubah dikonfirmasi_admin = 1 dan update status siswa
   ============================================================ */
if (isset($_POST['konfirmasi_nilai'])) {
    $id_s = (int) $_POST['id_siswa'];
    $id_p = (int) $_POST['id_periode'];

    /* Ambil hasil evaluasi Polda */
    $ev = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM evaluasi
         WHERE id_siswa = '$id_s' AND id_periode = '$id_p'
         LIMIT 1"
    ));

    if (!$ev) {
        $pesan_error = "Data evaluasi tidak ditemukan.";
        goto tampilForm;
    }

    /* Tandai dikonfirmasi */
    mysqli_query($conn,
        "UPDATE evaluasi SET dikonfirmasi_admin = 1
         WHERE id_siswa = '$id_s' AND id_periode = '$id_p'"
    );

    /* Update status siswa sesuai hasil */
    $status_baru = ($ev['hasil'] === 'lulus') ? 'lulus' : 'tidak_lulus';
    mysqli_query($conn,
        "UPDATE siswa SET status = '$status_baru' WHERE id_peserta = '$id_s'"
    );

    header("Location: admin_evaluasi.php?periode=$id_p&ok=nilai");
    exit;
}

/* ============================================================
   PROSES 2: KONFIRMASI SEMUA NILAI SEKALIGUS
   ============================================================ */
if (isset($_POST['konfirmasi_semua'])) {
    $id_p = (int) $_POST['id_periode'];

    /* Ambil semua evaluasi yang belum dikonfirmasi untuk periode ini */
    $q_ev = mysqli_query($conn,
        "SELECT e.id_evaluasi, e.id_siswa, e.hasil
         FROM evaluasi e
         WHERE e.id_periode = '$id_p'
         AND   e.dikonfirmasi_admin = 0"
    );

    $count_konfirm = 0;
    while ($ev = mysqli_fetch_assoc($q_ev)) {
        mysqli_query($conn,
            "UPDATE evaluasi SET dikonfirmasi_admin = 1
             WHERE id_evaluasi = '{$ev['id_evaluasi']}'"
        );
        $status_baru = ($ev['hasil'] === 'lulus') ? 'lulus' : 'tidak_lulus';
        mysqli_query($conn,
            "UPDATE siswa SET status = '$status_baru'
             WHERE id_peserta = '{$ev['id_siswa']}'"
        );
        $count_konfirm++;
    }

    /* Buat/perbarui record laporan + tandai file penilaian ada */
    $check_lap = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_laporan FROM laporan WHERE id_periode = '$id_p'"
    ));
    if (!$check_lap) {
        mysqli_query($conn,
            "INSERT INTO laporan (tgl_generate, id_periode) VALUES (NOW(), '$id_p')"
        );
    } else {
        mysqli_query($conn,
            "UPDATE laporan SET file_laporan_penilaian = 'confirmed'
             WHERE id_periode = '$id_p'"
        );
    }

    /* Kirim notifikasi email ke kepala keamanan (jika ada) */
    $kk = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT a.id_akun, a.username
         FROM akun a WHERE a.role = 'kepala_keamanan' LIMIT 1"
    ));

    /* Untuk saat ini log saja, kirim email bisa dikembangkan nanti */
    /* kirimEmailAkun($kk_email, 'Kepala Keamanan', 'notif', '...'); */

    header("Location: admin_evaluasi.php?periode=$id_p&ok=semua&n=$count_konfirm");
    exit;
}

tampilForm:

/* ============================================================
   LOAD DATA TAMPILAN
   ============================================================ */
/* Laporan dari Polda */
$laporan_polda = null;
if ($id_periode_aktif) {
    $laporan_polda = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT lp.*, a.username as akun_polda
         FROM laporan_polda lp
         LEFT JOIN akun a ON lp.id_akun_polda = a.id_akun
         WHERE lp.id_periode = '$id_periode_aktif'"
    ));
}

/* Daftar siswa + evaluasi */
$daftar_siswa = [];
if ($id_periode_aktif) {
    $q = mysqli_query($conn,
        "SELECT s.id_peserta, s.nama, s.email,
                e.id_evaluasi, e.nilai_teori, e.nilai_fisik,
                e.nilai_disiplin, e.nilai_praktik, e.rata_rata,
                e.hasil, e.catatan, e.dikonfirmasi_admin, e.tgl_input
         FROM siswa s
         JOIN peserta_periode pp ON s.id_peserta = pp.id_peserta
         LEFT JOIN evaluasi e ON e.id_siswa = s.id_peserta
             AND e.id_periode = '$id_periode_aktif'
         WHERE pp.id_periode = '$id_periode_aktif'
         AND   s.status IN ('peserta','lulus','tidak_lulus')
         ORDER BY s.nama ASC"
    );
    while ($r = mysqli_fetch_assoc($q)) $daftar_siswa[] = $r;
}

/* Stats */
$total       = count($daftar_siswa);
$dinilai     = 0;
$dikonfirmasi = 0;
$lulus       = 0;
foreach ($daftar_siswa as $ds) {
    if ($ds['id_evaluasi'])        $dinilai++;
    if ($ds['dikonfirmasi_admin']) $dikonfirmasi++;
    if ($ds['hasil'] === 'lulus')  $lulus++;
}
$bisa_konfirmasi_semua = ($dinilai > 0 && $dinilai > $dikonfirmasi);

/* Pesan redirect */
if (isset($_GET['ok'])) {
    if ($_GET['ok'] === 'nilai') {
        $pesan_sukses = "Nilai siswa berhasil dikonfirmasi. Status siswa diperbarui.";
    }
    if ($_GET['ok'] === 'semua') {
        $n = (int) ($_GET['n'] ?? 0);
        $pesan_sukses = "$n nilai berhasil dikonfirmasi sekaligus. "
                      . "Kepala Keamanan dapat melihat laporan penilaian.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin — Evaluasi Diklat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/fase4b.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark nav4b">
    <div class="container-fluid">
        <span class="navbar-brand">⚙️ Admin — Evaluasi & Konfirmasi Nilai</span>
        <div class="ms-auto d-flex gap-2">
            <a href="dashboard_admin.php" class="btn btn-sm btn-outline-light">Dashboard</a>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </div>
</nav>

<!-- Page header -->
<div class="page-header">
    <div class="container">
        <h3>Evaluasi Hasil Diklat</h3>
        <p>Review laporan Polda DIY → konfirmasi nilai → kirim ke Kepala Keamanan</p>
    </div>
</div>

<div class="container pb-5">

    <!-- Alert -->
    <?php if ($pesan_sukses): ?>
    <div class="alert-konfirmasi-done mb-3">
        ✅ <?php echo htmlspecialchars($pesan_sukses); ?>
    </div>
    <?php endif; ?>

    <?php if ($pesan_error): ?>
    <div class="alert alert-danger" style="border-radius:10px;font-size:14px;">
        ⚠️ <?php echo htmlspecialchars($pesan_error); ?>
    </div>
    <?php endif; ?>

    <!-- Tab periode -->
    <div class="periode-tabs">
        <?php
        mysqli_data_seek($semua_periode, 0);
        while ($p = mysqli_fetch_assoc($semua_periode)):
            $active = ($p['id_periode'] == $id_periode_aktif) ? 'active' : '';
        ?>
        <a href="?periode=<?php echo $p['id_periode']; ?>"
           class="periode-tab <?php echo $active; ?>">
            <?php echo $p['tahun']; ?> — G<?php echo $p['gelombang']; ?>
        </a>
        <?php endwhile; ?>
    </div>

    <?php if (!$id_periode_aktif || !$periode): ?>
    <div class="card4b text-center py-5 text-muted">
        <p>Belum ada periode diklat.</p>
    </div>
    <?php else: ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box4b">
            <div class="stat-n"><?php echo $total; ?></div>
            <div class="stat-lbl">Total Peserta</div>
        </div>
        <div class="stat-box4b">
            <div class="stat-n"><?php echo $dinilai; ?></div>
            <div class="stat-lbl">Sudah Dinilai Polda</div>
        </div>
        <div class="stat-box4b">
            <div class="stat-n" style="color:#0d6efd"><?php echo $dikonfirmasi; ?></div>
            <div class="stat-lbl">Dikonfirmasi Admin</div>
        </div>
        <div class="stat-box4b">
            <div class="stat-n" style="color:#198754"><?php echo $lulus; ?></div>
            <div class="stat-lbl">Lulus</div>
        </div>
    </div>

    <div class="row g-4">

        <!-- =====================================================
             KOLOM KIRI: LAPORAN POLDA
        ===================================================== -->
        <div class="col-lg-4">
            <div class="card4b mb-3">
                <div class="card-header">📄 Laporan Kegiatan dari Polda</div>
                <div class="card-body">

                    <?php if ($laporan_polda): ?>
                    <div class="laporan-info-card">
                        <div class="info-row">
                            <span class="info-label">Pengeluaran</span>
                            <span class="info-val">
                                Rp <?php echo number_format($laporan_polda['pengeluaran'], 0, ',', '.'); ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Pemasukan</span>
                            <span class="info-val">
                                Rp <?php echo number_format($laporan_polda['pemasukan'], 0, ',', '.'); ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Selisih</span>
                            <span class="info-val">
                                <?php
                                $selisih = $laporan_polda['pemasukan'] - $laporan_polda['pengeluaran'];
                                $color   = $selisih >= 0 ? '#198754' : '#dc3545';
                                echo "<strong style='color:$color'>Rp "
                                   . number_format(abs($selisih), 0, ',', '.')
                                   . ($selisih >= 0 ? ' (surplus)' : ' (defisit)')
                                   . "</strong>";
                                ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Input oleh</span>
                            <span class="info-val"><?php echo htmlspecialchars($laporan_polda['akun_polda'] ?? '-'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tanggal</span>
                            <span class="info-val"><?php echo date('d/m/Y', strtotime($laporan_polda['tgl_input'])); ?></span>
                        </div>
                    </div>

                    <?php if ($laporan_polda['deskripsi_kegiatan']): ?>
                    <div class="mb-3">
                        <div class="section-lbl">Deskripsi Kegiatan</div>
                        <p style="font-size:13px;color:#495057;line-height:1.6;background:#f8f9fa;
                                  padding:12px;border-radius:8px;margin:0;">
                            <?php echo nl2br(htmlspecialchars($laporan_polda['deskripsi_kegiatan'])); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <?php if ($laporan_polda['file_laporan']): ?>
                    <a href="<?php echo htmlspecialchars($laporan_polda['file_laporan']); ?>"
                       target="_blank"
                       class="btn btn-sm btn-outline-primary w-100"
                       style="border-radius:8px;">
                        📎 Unduh File Laporan Polda
                    </a>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <div style="font-size:36px;margin-bottom:10px;">⏳</div>
                        <p class="mb-0" style="font-size:13px;">
                            Laporan dari Polda DIY belum tersedia untuk periode ini.
                        </p>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Tombol konfirmasi semua sekaligus -->
            <?php if ($bisa_konfirmasi_semua && $laporan_polda): ?>
            <div class="card4b">
                <div class="card-body"
                     style="background:#fff3cd;border-radius:14px;border-left:4px solid #ffc107;">
                    <p style="font-size:13px;font-weight:700;color:#664d03;margin-bottom:8px;">
                        ⚡ Konfirmasi Semua Sekaligus
                    </p>
                    <p style="font-size:12px;color:#856404;margin-bottom:12px;">
                        <?php echo $dinilai - $dikonfirmasi; ?> siswa sudah dinilai Polda tapi belum dikonfirmasi.
                        Konfirmasi sekaligus akan mengunci semua nilai dan memperbarui status siswa.
                    </p>
                    <form method="POST"
                          onsubmit="return confirm('Konfirmasi semua nilai yang belum dikonfirmasi?\nTindakan ini tidak bisa dibatalkan.')">
                        <input type="hidden" name="id_periode" value="<?php echo $id_periode_aktif; ?>">
                        <button type="submit" name="konfirmasi_semua"
                                class="btn-konfirmasi"
                                style="background:#ffc107;color:#000;font-weight:700;">
                            ✅ Konfirmasi Semua (<?php echo $dinilai - $dikonfirmasi; ?> siswa)
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Panduan alur -->
            <div class="card4b mt-3">
                <div class="card-body">
                    <div class="section-lbl">Alur Konfirmasi</div>
                    <ol style="font-size:12px;color:#495057;margin:0;padding-left:18px;">
                        <li class="mb-2">Polda DIY menginput nilai & laporan kegiatan</li>
                        <li class="mb-2">Admin mereview dan konfirmasi nilai satu per satu atau sekaligus</li>
                        <li class="mb-2">Status siswa otomatis berubah ke <strong>Lulus</strong> atau <strong>Tidak Lulus</strong></li>
                        <li class="mb-2">Kepala Keamanan dapat melihat laporan final</li>
                        <li>Siswa dapat melihat hasil di dashboard masing-masing</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- =====================================================
             KOLOM KANAN: DAFTAR NILAI SISWA
        ===================================================== -->
        <div class="col-lg-8">
            <div class="card4b">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>📊 Nilai Siswa — Periode <?php echo $periode['tahun']; ?> G<?php echo $periode['gelombang']; ?></span>
                    <small class="text-muted" style="font-weight:400;font-size:12px;">
                        Lulus ≥ 70 rata-rata
                    </small>
                </div>
                <div class="card-body p-0">

                    <?php if (empty($daftar_siswa)): ?>
                    <div class="text-center py-5 text-muted">
                        <div style="font-size:36px;margin-bottom:12px;">👥</div>
                        <p class="mb-0">Belum ada peserta di periode ini.</p>
                    </div>

                    <?php else: ?>
                    <div class="p-3">
                    <?php foreach ($daftar_siswa as $siswa): ?>

                    <div class="siswa-eval-card"
                         style="<?php echo $siswa['dikonfirmasi_admin'] ? 'border-top:4px solid #198754;' : ''; ?>">

                        <!-- Header siswa -->
                        <div class="siswa-info">
                            <div class="siswa-avatar">
                                <?php echo strtoupper(mb_substr($siswa['nama'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="siswa-nama"><?php echo htmlspecialchars($siswa['nama']); ?></div>
                                <div class="siswa-meta"><?php echo htmlspecialchars($siswa['email']); ?></div>
                            </div>
                            <div class="ms-auto text-end">
                                <?php if ($siswa['dikonfirmasi_admin']): ?>
                                    <span class="badge-status badge-dikonfirmasi">✓ Dikonfirmasi</span>
                                <?php elseif ($siswa['id_evaluasi']): ?>
                                    <span class="badge-status badge-belum"
                                          style="background:#fff3cd;color:#664d03;">
                                        ⏳ Menunggu konfirmasi
                                    </span>
                                <?php else: ?>
                                    <span class="badge-status badge-belum">Belum dinilai Polda</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!$siswa['id_evaluasi']): ?>
                        <!-- Belum ada nilai dari Polda -->
                        <p style="font-size:12px;color:#6c757d;margin:0;padding:8px;
                                  background:#f8f9fa;border-radius:8px;">
                            Polda DIY belum menginput nilai untuk siswa ini.
                        </p>

                        <?php else: ?>
                        <!-- Ada nilai dari Polda, tampilkan -->
                        <div class="nilai-display">
                            <div class="nd-item">
                                <div class="nd-n"><?php echo $siswa['nilai_fisik']; ?></div>
                                <div class="nd-lbl">Fisik</div>
                                <div class="nilai-bar-wrap">
                                    <div class="nilai-bar"
                                         style="width:<?php echo $siswa['nilai_fisik']; ?>%;
                                                background:<?php echo $siswa['nilai_fisik'] >= 70 ? '#198754' : ($siswa['nilai_fisik'] >= 50 ? '#ffc107' : '#dc3545'); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="nd-item">
                                <div class="nd-n"><?php echo $siswa['nilai_disiplin']; ?></div>
                                <div class="nd-lbl">Disiplin</div>
                                <div class="nilai-bar-wrap">
                                    <div class="nilai-bar"
                                         style="width:<?php echo $siswa['nilai_disiplin']; ?>%;
                                                background:<?php echo $siswa['nilai_disiplin'] >= 70 ? '#198754' : ($siswa['nilai_disiplin'] >= 50 ? '#ffc107' : '#dc3545'); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="nd-item">
                                <div class="nd-n"><?php echo $siswa['nilai_teori']; ?></div>
                                <div class="nd-lbl">Teori</div>
                                <div class="nilai-bar-wrap">
                                    <div class="nilai-bar"
                                         style="width:<?php echo $siswa['nilai_teori']; ?>%;
                                                background:<?php echo $siswa['nilai_teori'] >= 70 ? '#198754' : ($siswa['nilai_teori'] >= 50 ? '#ffc107' : '#dc3545'); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="nd-item">
                                <div class="nd-n"><?php echo $siswa['nilai_praktik']; ?></div>
                                <div class="nd-lbl">Praktik</div>
                                <div class="nilai-bar-wrap">
                                    <div class="nilai-bar"
                                         style="width:<?php echo $siswa['nilai_praktik']; ?>%;
                                                background:<?php echo $siswa['nilai_praktik'] >= 70 ? '#198754' : ($siswa['nilai_praktik'] >= 50 ? '#ffc107' : '#dc3545'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rata-badge mb-2">
                            <span class="rata-label">Rata-rata</span>
                            <span class="rata-nilai"><?php echo $siswa['rata_rata']; ?></span>
                            <span class="rata-hasil <?php echo $siswa['hasil'] === 'lulus' ? 'hasil-lulus' : 'hasil-tidak-lulus'; ?>">
                                <?php echo strtoupper(str_replace('_', ' ', $siswa['hasil'])); ?>
                            </span>
                        </div>

                        <?php if ($siswa['catatan']): ?>
                        <p style="font-size:12px;color:#6c757d;background:#f8f9fa;
                                  padding:8px 12px;border-radius:8px;margin:0 0 10px;">
                            📝 <?php echo htmlspecialchars($siswa['catatan']); ?>
                        </p>
                        <?php endif; ?>

                        <!-- Tombol konfirmasi (hanya jika belum dikonfirmasi) -->
                        <?php if (!$siswa['dikonfirmasi_admin']): ?>
                        <form method="POST"
                              onsubmit="return confirm('Konfirmasi nilai <?php echo htmlspecialchars($siswa['nama']); ?>?\n\nNilai akan dikunci dan status siswa diperbarui ke: <?php echo strtoupper(str_replace('_',' ',$siswa['hasil'])); ?>.\n\nTindakan ini tidak bisa dibatalkan.')">
                            <input type="hidden" name="id_siswa"   value="<?php echo $siswa['id_peserta']; ?>">
                            <input type="hidden" name="id_periode" value="<?php echo $id_periode_aktif; ?>">
                            <button type="submit" name="konfirmasi_nilai"
                                    class="btn btn-sm"
                                    style="background:var(--navy);color:#fff;border:none;
                                           border-radius:8px;padding:8px 20px;
                                           font-weight:700;font-size:13px;">
                                ✅ Konfirmasi Nilai Ini
                            </button>
                        </form>
                        <?php else: ?>
                        <div style="font-size:12px;color:#198754;">
                            ✔ Dikonfirmasi pada <?php echo $siswa['tgl_input']; ?>
                        </div>
                        <?php endif; ?>

                        <?php endif; /* ada evaluasi */ ?>

                    </div><!-- /siswa-eval-card -->
                    <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div><!-- /row -->
    <?php endif; /* ada periode */ ?>

</div><!-- /container -->

</body>
</html>
