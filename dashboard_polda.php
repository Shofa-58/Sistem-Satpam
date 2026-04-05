<?php
session_start();
include "koneksi.php";

/* ============================================================
   GUARD: hanya polda
   ============================================================ */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'polda') {
    header("Location: login.php");
    exit;
}

$id_akun_polda = (int) $_SESSION['id_akun'];

/* ============================================================
   AMBIL SEMUA PERIODE — untuk tab navigasi
   ============================================================ */
$semua_periode = mysqli_query($conn,
    "SELECT * FROM periode_diklat ORDER BY tahun DESC, gelombang DESC"
);

/* Periode aktif yang dipilih (default: gelombang paling baru berstatus berjalan, atau pertama) */
$id_periode_aktif = isset($_GET['periode']) ? (int) $_GET['periode'] : 0;
if (!$id_periode_aktif) {
    $cari_aktif = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_periode FROM periode_diklat
         WHERE status = 'berjalan'
         ORDER BY tahun DESC, gelombang DESC LIMIT 1"
    ));
    $id_periode_aktif = $cari_aktif ? (int) $cari_aktif['id_periode'] : 0;
    if (!$id_periode_aktif) {
        $cari_aktif = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id_periode FROM periode_diklat ORDER BY tahun DESC, gelombang DESC LIMIT 1"
        ));
        $id_periode_aktif = $cari_aktif ? (int) $cari_aktif['id_periode'] : 0;
    }
}

/* Data periode terpilih */
$periode = null;
if ($id_periode_aktif) {
    $periode = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM periode_diklat WHERE id_periode = '$id_periode_aktif'"
    ));
}

$pesan_sukses = '';
$pesan_error  = '';

/* ============================================================
   PROSES 1: SIMPAN LAPORAN KEGIATAN POLDA
   ============================================================ */
if (isset($_POST['simpan_laporan'])) {
    $id_p        = (int) $_POST['id_periode'];
    $pengeluaran = (int) str_replace('.', '', $_POST['pengeluaran']);
    $pemasukan   = (int) str_replace('.', '', $_POST['pemasukan']);
    $deskripsi   = mysqli_real_escape_string($conn, trim($_POST['deskripsi_kegiatan']));
    $file_path   = '';

    /* Upload file laporan (opsional) */
    if (!empty($_FILES['file_laporan']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file_laporan']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $folder = "uploads/laporan_polda";
            if (!is_dir($folder)) mkdir($folder, 0777, true);
            $fname  = "laporan_polda_{$id_p}_" . time() . ".$ext";
            if (move_uploaded_file($_FILES['file_laporan']['tmp_name'], "$folder/$fname")) {
                $file_path = "$folder/$fname";
            }
        }
    }

    $file_safe = mysqli_real_escape_string($conn, $file_path);

    /* Cek sudah ada laporan untuk periode ini */
    $existing = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_laporan_polda FROM laporan_polda WHERE id_periode = '$id_p'"
    ));

    if ($existing) {
        $update_file = $file_path ? ", file_laporan = '$file_safe'" : "";
        mysqli_query($conn,
            "UPDATE laporan_polda
             SET pengeluaran = '$pengeluaran',
                 pemasukan   = '$pemasukan',
                 deskripsi_kegiatan = '$deskripsi'
                 $update_file
             WHERE id_periode = '$id_p'"
        );
        $pesan_sukses = "Laporan kegiatan berhasil diperbarui.";
    } else {
        mysqli_query($conn,
            "INSERT INTO laporan_polda
                (id_periode, id_akun_polda, pengeluaran, pemasukan, deskripsi_kegiatan, file_laporan)
             VALUES
                ('$id_p', '$id_akun_polda', '$pengeluaran', '$pemasukan', '$deskripsi', '$file_safe')"
        );
        $pesan_sukses = "Laporan kegiatan berhasil disimpan.";
    }

    /* Buat/perbarui record laporan utama juga */
    $check_lap = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_laporan FROM laporan WHERE id_periode = '$id_p'"
    ));
    if (!$check_lap) {
        mysqli_query($conn,
            "INSERT INTO laporan (tgl_generate, id_periode) VALUES (NOW(), '$id_p')"
        );
    }

    header("Location: dashboard_polda.php?periode=$id_p&ok=laporan");
    exit;
}

/* ============================================================
   PROSES 2: SIMPAN NILAI SATU SISWA
   ============================================================ */
if (isset($_POST['simpan_nilai'])) {
    $id_s        = (int) $_POST['id_siswa'];
    $id_p        = (int) $_POST['id_periode'];
    $nilai_teori    = max(0, min(100, (float) $_POST['nilai_teori']));
    $nilai_fisik    = max(0, min(100, (float) $_POST['nilai_fisik']));
    $nilai_disiplin = max(0, min(100, (float) $_POST['nilai_disiplin']));
    $nilai_praktik  = max(0, min(100, (float) $_POST['nilai_praktik']));
    $catatan        = mysqli_real_escape_string($conn, trim($_POST['catatan'] ?? ''));
    $rata_rata      = round(($nilai_teori + $nilai_fisik + $nilai_disiplin + $nilai_praktik) / 4, 2);
    $hasil          = $rata_rata >= 70 ? 'lulus' : 'tidak_lulus';
    $tgl_input      = date('Y-m-d');

    /* Cek sudah ada evaluasi untuk siswa + periode ini */
    $cek_ev = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id_evaluasi FROM evaluasi
         WHERE id_siswa = '$id_s' AND id_periode = '$id_p'"
    ));

    if ($cek_ev) {
        /* Update — reset dikonfirmasi_admin jika nilainya diubah */
        mysqli_query($conn,
            "UPDATE evaluasi
             SET nilai_teori    = '$nilai_teori',
                 nilai_fisik    = '$nilai_fisik',
                 nilai_disiplin = '$nilai_disiplin',
                 nilai_praktik  = '$nilai_praktik',
                 rata_rata      = '$rata_rata',
                 hasil          = '$hasil',
                 catatan        = '$catatan',
                 tgl_input      = '$tgl_input',
                 dikonfirmasi_admin = 0
             WHERE id_siswa   = '$id_s'
             AND   id_periode = '$id_p'"
        );
    } else {
        mysqli_query($conn,
            "INSERT INTO evaluasi
                (nilai_teori, nilai_fisik, nilai_disiplin, nilai_praktik,
                 rata_rata, hasil, catatan, tgl_input, id_siswa, id_periode, dikonfirmasi_admin)
             VALUES
                ('$nilai_teori','$nilai_fisik','$nilai_disiplin','$nilai_praktik',
                 '$rata_rata','$hasil','$catatan','$tgl_input','$id_s','$id_p', 0)"
        );
    }

    header("Location: dashboard_polda.php?periode=$id_p&ok=nilai");
    exit;
}

/* ============================================================
   LOAD DATA UNTUK TAMPILAN
   ============================================================ */
/* Laporan kegiatan yang sudah ada */
$laporan_polda = null;
if ($id_periode_aktif) {
    $laporan_polda = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM laporan_polda WHERE id_periode = '$id_periode_aktif'"
    ));
}

/* Daftar siswa peserta periode ini */
$daftar_siswa = [];
if ($id_periode_aktif) {
    $q = mysqli_query($conn,
        "SELECT s.id_peserta, s.nama, s.email, s.jenis_kelamin,
                e.id_evaluasi, e.nilai_teori, e.nilai_fisik,
                e.nilai_disiplin, e.nilai_praktik, e.rata_rata,
                e.hasil, e.catatan, e.dikonfirmasi_admin
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
$total_siswa  = count($daftar_siswa);
$sudah_dinilai = 0;
$jumlah_lulus  = 0;
foreach ($daftar_siswa as $ds) {
    if ($ds['id_evaluasi']) $sudah_dinilai++;
    if ($ds['hasil'] === 'lulus') $jumlah_lulus++;
}

/* Pesan dari redirect */
if (isset($_GET['ok'])) {
    if ($_GET['ok'] === 'laporan') $pesan_sukses = "Laporan kegiatan berhasil disimpan.";
    if ($_GET['ok'] === 'nilai')   $pesan_sukses = "Nilai siswa berhasil disimpan.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Polda DIY — Diklat Satpam</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/fase4b.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark nav4b">
    <div class="container-fluid">
        <span class="navbar-brand">🚔 Polda DIY — Sistem Diklat</span>
        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="text-muted" style="font-size:13px;">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </div>
</nav>

<!-- Page header -->
<div class="page-header">
    <div class="container">
        <h3>Laporan & Penilaian Diklat</h3>
        <p>Input laporan kegiatan dan nilai peserta per periode gelombang</p>
    </div>
</div>

<div class="container pb-5">

    <!-- =====================================================
         ALERT
    ===================================================== -->
    <?php if ($pesan_sukses): ?>
    <div class="alert-konfirmasi-done mb-3">
        ✅ <?php echo htmlspecialchars($pesan_sukses); ?>
    </div>
    <?php endif; ?>

    <!-- =====================================================
         TAB PERIODE
    ===================================================== -->
    <div class="periode-tabs">
        <?php
        mysqli_data_seek($semua_periode, 0);
        while ($p = mysqli_fetch_assoc($semua_periode)):
            $active = ($p['id_periode'] == $id_periode_aktif) ? 'active' : '';
            $status_label = [
                'pendaftaran' => '📋',
                'berjalan'    => '🟢',
                'selesai'     => '✅',
            ][$p['status']] ?? '';
        ?>
        <a href="?periode=<?php echo $p['id_periode']; ?>"
           class="periode-tab <?php echo $active; ?>">
            <?php echo $status_label; ?>
            <?php echo $p['tahun']; ?> — G<?php echo $p['gelombang']; ?>
        </a>
        <?php endwhile; ?>
    </div>

    <?php if (!$id_periode_aktif || !$periode): ?>
    <div class="card4b">
        <div class="card-body text-center py-5 text-muted">
            <div style="font-size:48px;margin-bottom:16px;">📋</div>
            <h5>Belum ada periode diklat</h5>
            <p class="mb-0">Hubungi admin untuk membuat periode diklat terlebih dahulu.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- Info periode -->
    <div class="laporan-info-card mb-4">
        <div class="info-row">
            <span class="info-label">Periode</span>
            <span class="info-val">
                <?php echo $periode['tahun']; ?> Gelombang <?php echo $periode['gelombang']; ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Tanggal</span>
            <span class="info-val">
                <?php echo $periode['tanggal_mulai']; ?> s/d <?php echo $periode['tanggal_selesai']; ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Lokasi</span>
            <span class="info-val"><?php echo htmlspecialchars($periode['lokasi_spesifik'] ?: '-'); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Status</span>
            <span class="info-val">
                <strong style="text-transform:capitalize"><?php echo $periode['status']; ?></strong>
            </span>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-row">
        <div class="stat-box4b">
            <div class="stat-n"><?php echo $total_siswa; ?></div>
            <div class="stat-lbl">Total Peserta</div>
        </div>
        <div class="stat-box4b">
            <div class="stat-n"><?php echo $sudah_dinilai; ?></div>
            <div class="stat-lbl">Sudah Dinilai</div>
        </div>
        <div class="stat-box4b">
            <div class="stat-n" style="color:#198754"><?php echo $jumlah_lulus; ?></div>
            <div class="stat-lbl">Lulus</div>
        </div>
        <div class="stat-box4b">
            <div class="stat-n" style="color:#dc3545"><?php echo ($sudah_dinilai - $jumlah_lulus); ?></div>
            <div class="stat-lbl">Tidak Lulus</div>
        </div>
    </div>

    <div class="row g-4">

        <!-- =====================================================
             KOLOM KIRI: LAPORAN KEGIATAN
        ===================================================== -->
        <div class="col-lg-5">
            <div class="card4b">
                <div class="card-header">
                    📄 Laporan Kegiatan Diklat
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id_periode" value="<?php echo $id_periode_aktif; ?>">

                        <div class="section-lbl">Keuangan Kegiatan</div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:13px;">
                                Pengeluaran (Rp)
                            </label>
                            <input type="text"
                                   name="pengeluaran"
                                   class="form-control"
                                   placeholder="Contoh: 45000000"
                                   value="<?php echo $laporan_polda ? $laporan_polda['pengeluaran'] : ''; ?>"
                                   style="border-radius:10px"
                                   oninput="formatRupiah(this)">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:13px;">
                                Pemasukan (Rp)
                            </label>
                            <input type="text"
                                   name="pemasukan"
                                   class="form-control"
                                   placeholder="Contoh: 50000000"
                                   value="<?php echo $laporan_polda ? $laporan_polda['pemasukan'] : ''; ?>"
                                   style="border-radius:10px"
                                   oninput="formatRupiah(this)">
                        </div>

                        <div class="section-lbl mt-3">Deskripsi Kegiatan</div>

                        <div class="mb-3">
                            <textarea name="deskripsi_kegiatan"
                                      class="form-control"
                                      rows="5"
                                      placeholder="Ringkasan pelaksanaan diklat, kendala, catatan lapangan..."
                                      style="border-radius:10px;font-size:13px"><?php
                                echo $laporan_polda
                                    ? htmlspecialchars($laporan_polda['deskripsi_kegiatan'])
                                    : '';
                            ?></textarea>
                        </div>

                        <div class="section-lbl">Upload File Laporan (Opsional)</div>

                        <div class="upload-zone mb-3"
                             id="uzLaporan"
                             onclick="document.getElementById('fileLaporan').click()">
                            <input type="file"
                                   name="file_laporan"
                                   id="fileLaporan"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   onchange="setFileUpload(this,'uzLaporan','uzText')">
                            <div class="uz-icon">📎</div>
                            <div class="uz-text" id="uzText">
                                <?php if ($laporan_polda && $laporan_polda['file_laporan']): ?>
                                    File sudah ada — klik untuk ganti
                                <?php else: ?>
                                    Tap untuk pilih PDF/JPG/PNG · Maks 10MB
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($laporan_polda && $laporan_polda['file_laporan']): ?>
                        <div class="mb-3">
                            <a href="<?php echo htmlspecialchars($laporan_polda['file_laporan']); ?>"
                               target="_blank"
                               style="font-size:13px;">
                               📄 Lihat file laporan saat ini
                            </a>
                        </div>
                        <?php endif; ?>

                        <button type="submit" name="simpan_laporan" class="btn-konfirmasi">
                            💾 Simpan Laporan Kegiatan
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($laporan_polda): ?>
            <div class="card4b mt-3">
                <div class="card-body" style="background:#d1e7dd;border-radius:14px;">
                    <p class="mb-1" style="font-size:13px;color:#0a3622;font-weight:700;">
                        ✅ Laporan sudah tersimpan
                    </p>
                    <p class="mb-0" style="font-size:12px;color:#155724;">
                        Terakhir diperbarui: <?php echo $laporan_polda['tgl_input']; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- =====================================================
             KOLOM KANAN: PENILAIAN SISWA
        ===================================================== -->
        <div class="col-lg-7">
            <div class="card4b">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>📊 Penilaian Siswa Peserta</span>
                    <small class="text-muted" style="font-weight:400;font-size:12px;">
                        Skala 0–100 · Lulus ≥ 70
                    </small>
                </div>
                <div class="card-body p-0">

                    <?php if (empty($daftar_siswa)): ?>
                    <div class="text-center py-5 text-muted">
                        <div style="font-size:36px;margin-bottom:12px;">👥</div>
                        <p class="mb-0">Belum ada peserta terdaftar di periode ini.</p>
                        <small>Pastikan admin sudah menetapkan siswa ke periode diklat.</small>
                    </div>

                    <?php else: ?>
                    <div class="p-3">
                    <?php foreach ($daftar_siswa as $siswa): ?>

                    <div class="siswa-eval-card">
                        <!-- Info siswa -->
                        <div class="siswa-info">
                            <div class="siswa-avatar">
                                <?php echo strtoupper(mb_substr($siswa['nama'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="siswa-nama">
                                    <?php echo htmlspecialchars($siswa['nama']); ?>
                                </div>
                                <div class="siswa-meta">
                                    <?php echo htmlspecialchars($siswa['email']); ?>
                                </div>
                            </div>
                            <div class="ms-auto">
                                <?php if ($siswa['dikonfirmasi_admin']): ?>
                                    <span class="badge-status badge-dikonfirmasi">✓ Dikonfirmasi</span>
                                <?php elseif ($siswa['id_evaluasi']): ?>
                                    <span class="badge-status <?php echo $siswa['hasil'] === 'lulus' ? 'badge-lulus' : 'badge-tidak-lulus'; ?>">
                                        <?php echo $siswa['hasil'] === 'lulus' ? '✅ Lulus' : '❌ Tidak Lulus'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge-status badge-belum">Belum dinilai</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($siswa['dikonfirmasi_admin']): ?>
                        <!-- Sudah dikonfirmasi admin: readonly -->
                        <div class="nilai-display">
                            <div class="nd-item">
                                <div class="nd-n"><?php echo $siswa['nilai_fisik']; ?></div>
                                <div class="nd-lbl">Fisik</div>
                            </div>
                            <div class="nd-item">
                                <div class="nd-n"><?php echo $siswa['nilai_disiplin']; ?></div>
                                <div class="nd-lbl">Disiplin</div>
                            </div>
                            <div class="nd-item">
                                <div class="nd-n"><?php echo $siswa['nilai_teori']; ?></div>
                                <div class="nd-lbl">Teori</div>
                            </div>
                            <div class="nd-item">
                                <div class="nd-n"><?php echo $siswa['nilai_praktik']; ?></div>
                                <div class="nd-lbl">Praktik</div>
                            </div>
                        </div>
                        <div class="rata-badge">
                            <span class="rata-label">Rata-rata</span>
                            <span class="rata-nilai"><?php echo $siswa['rata_rata']; ?></span>
                            <span class="rata-hasil <?php echo $siswa['hasil'] === 'lulus' ? 'hasil-lulus' : 'hasil-tidak-lulus'; ?>">
                                <?php echo strtoupper(str_replace('_', ' ', $siswa['hasil'])); ?>
                            </span>
                        </div>
                        <p style="font-size:12px;color:#6c757d;margin:0;">
                            🔒 Nilai telah dikonfirmasi oleh admin — tidak bisa diubah.
                        </p>

                        <?php else: ?>
                        <!-- Belum dikonfirmasi: bisa input/edit -->
                        <form method="POST"
                              id="form_siswa_<?php echo $siswa['id_peserta']; ?>">
                            <input type="hidden" name="id_siswa"
                                   value="<?php echo $siswa['id_peserta']; ?>">
                            <input type="hidden" name="id_periode"
                                   value="<?php echo $id_periode_aktif; ?>">

                            <div class="nilai-grid">
                                <div class="nilai-item">
                                    <label>Nilai Fisik</label>
                                    <input type="number" name="nilai_fisik"
                                           min="0" max="100" step="0.5"
                                           value="<?php echo $siswa['nilai_fisik'] ?? ''; ?>"
                                           placeholder="0–100"
                                           class="nilai-input"
                                           data-form="<?php echo $siswa['id_peserta']; ?>"
                                           oninput="hitungRata(<?php echo $siswa['id_peserta']; ?>); warnaNilai(this)">
                                </div>
                                <div class="nilai-item">
                                    <label>Nilai Disiplin</label>
                                    <input type="number" name="nilai_disiplin"
                                           min="0" max="100" step="0.5"
                                           value="<?php echo $siswa['nilai_disiplin'] ?? ''; ?>"
                                           placeholder="0–100"
                                           class="nilai-input"
                                           data-form="<?php echo $siswa['id_peserta']; ?>"
                                           oninput="hitungRata(<?php echo $siswa['id_peserta']; ?>); warnaNilai(this)">
                                </div>
                                <div class="nilai-item">
                                    <label>Nilai Teori</label>
                                    <input type="number" name="nilai_teori"
                                           min="0" max="100" step="0.5"
                                           value="<?php echo $siswa['nilai_teori'] ?? ''; ?>"
                                           placeholder="0–100"
                                           class="nilai-input"
                                           data-form="<?php echo $siswa['id_peserta']; ?>"
                                           oninput="hitungRata(<?php echo $siswa['id_peserta']; ?>); warnaNilai(this)">
                                </div>
                                <div class="nilai-item">
                                    <label>Nilai Praktik</label>
                                    <input type="number" name="nilai_praktik"
                                           min="0" max="100" step="0.5"
                                           value="<?php echo $siswa['nilai_praktik'] ?? ''; ?>"
                                           placeholder="0–100"
                                           class="nilai-input"
                                           data-form="<?php echo $siswa['id_peserta']; ?>"
                                           oninput="hitungRata(<?php echo $siswa['id_peserta']; ?>); warnaNilai(this)">
                                </div>
                            </div>

                            <!-- Rata-rata live -->
                            <div class="rata-badge" id="rata_<?php echo $siswa['id_peserta']; ?>">
                                <span class="rata-label">Rata-rata</span>
                                <span class="rata-nilai" id="rataVal_<?php echo $siswa['id_peserta']; ?>">
                                    <?php echo $siswa['rata_rata'] ?? '—'; ?>
                                </span>
                                <span class="rata-hasil" id="rataHasil_<?php echo $siswa['id_peserta']; ?>">
                                    <?php if ($siswa['rata_rata'] !== null): ?>
                                        <?php if ($siswa['rata_rata'] >= 70): ?>
                                            <span class="hasil-lulus">LULUS</span>
                                        <?php else: ?>
                                            <span class="hasil-tidak-lulus">TIDAK LULUS</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="mb-3">
                                <label style="font-size:12px;font-weight:700;color:#495057;margin-bottom:5px;display:block;">
                                    Catatan (opsional)
                                </label>
                                <textarea name="catatan"
                                          class="form-control"
                                          rows="2"
                                          style="font-size:13px"
                                          placeholder="Catatan khusus untuk siswa ini..."><?php
                                    echo htmlspecialchars($siswa['catatan'] ?? '');
                                ?></textarea>
                            </div>

                            <button type="submit" name="simpan_nilai"
                                    class="btn btn-sm"
                                    style="background:var(--navy);color:#fff;border-radius:8px;
                                           padding:8px 20px;font-weight:600;font-size:13px;border:none;">
                                💾 Simpan Nilai
                            </button>

                            <?php if ($siswa['id_evaluasi']): ?>
                            <small class="ms-2 text-muted" style="font-size:12px;">
                                Terakhir diinput: <?php echo $siswa['tgl_input'] ?? '-'; ?>
                            </small>
                            <?php endif; ?>
                        </form>
                        <?php endif; /* dikonfirmasi_admin */ ?>

                    </div><!-- /siswa-eval-card -->
                    <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Pesan panduan -->
            <div style="font-size:12px;color:#6c757d;margin-top:12px;padding:0 4px;">
                ⚠️ Nilai yang sudah dikonfirmasi admin tidak dapat diubah.
                Pastikan semua nilai sudah benar sebelum admin melakukan konfirmasi.
            </div>
        </div>

    </div><!-- /row -->
    <?php endif; /* ada periode */ ?>

</div><!-- /container -->

<script>
/* Hitung rata-rata live */
function hitungRata(id) {
    var form  = document.getElementById('form_siswa_' + id);
    if (!form) return;

    var inputs = form.querySelectorAll('input.nilai-input');
    var total  = 0, count = 0;
    inputs.forEach(function(inp) {
        var v = parseFloat(inp.value);
        if (!isNaN(v)) { total += v; count++; }
    });

    var elVal  = document.getElementById('rataVal_' + id);
    var elHsl  = document.getElementById('rataHasil_' + id);

    if (count === 4) {
        var rata = (total / 4).toFixed(2);
        elVal.textContent = rata;
        elHsl.innerHTML = rata >= 70
            ? '<span class="hasil-lulus">LULUS</span>'
            : '<span class="hasil-tidak-lulus">TIDAK LULUS</span>';
    } else {
        elVal.textContent = '—';
        elHsl.innerHTML   = '';
    }
}

/* Warna input sesuai nilai */
function warnaNilai(inp) {
    var v = parseFloat(inp.value);
    inp.classList.remove('nilai-ok','nilai-warn','nilai-fail');
    if (isNaN(v) || inp.value === '') return;
    if (v >= 70)      inp.classList.add('nilai-ok');
    else if (v >= 50) inp.classList.add('nilai-warn');
    else              inp.classList.add('nilai-fail');
}

/* Format rupiah di input */
function formatRupiah(inp) {
    var raw = inp.value.replace(/\D/g,'');
    inp.value = raw;
}

/* Init warna & rata nilai yang sudah ada saat load */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input.nilai-input').forEach(function(inp) {
        if (inp.value) warnaNilai(inp);
    });
});
</script>

</body>
</html>
