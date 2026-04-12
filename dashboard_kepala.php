<?php
session_start();
include "koneksi.php";

/* ============================================================
   GUARD: hanya kepala keamanan
   ============================================================ */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kepala_keamanan') {
    header("Location: login.php");
    exit;
}

/* ============================================================
   FUNGSI BANTU LOKAL
   ============================================================ */
function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function period_label($p) {
    if (!$p) return '-';
    $tahun = $p['tahun'] ?? '-';
    $gelombang = $p['gelombang'] ?? '-';
    return $tahun . ' - Gelombang ' . $gelombang;
}

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function result_badge($hasil) {
    $hasil = strtolower(trim((string)$hasil));
    if ($hasil === 'lulus') {
        return ['label' => 'LULUS', 'class' => 'badge-lulus'];
    }
    if ($hasil === 'tidak_lulus') {
        return ['label' => 'TIDAK LULUS', 'class' => 'badge-tidak-lulus'];
    }
    return ['label' => '-', 'class' => 'badge-netral'];
}

function upload_pdf($fieldName, $folder, $prefix) {
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload gagal untuk file {$fieldName}.");
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new Exception("File {$fieldName} harus berformat PDF.");
    }

    if (!is_dir($folder)) {
        if (!mkdir($folder, 0777, true) && !is_dir($folder)) {
            throw new Exception("Folder upload tidak bisa dibuat.");
        }
    }

    $nama = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    $target = rtrim($folder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nama;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new Exception("Gagal memindahkan file upload.");
    }

    return 'uploads/laporan_kepala/' . $nama;
}

/* ============================================================
   SETUP DATA AWAL
   ============================================================ */
$pesan_sukses = '';
$pesan_error  = '';

$perulist = [];
$q_periode = mysqli_query($conn, "SELECT * FROM periode_diklat ORDER BY tahun DESC, gelombang DESC, id_periode DESC");
if ($q_periode) {
    while ($row = mysqli_fetch_assoc($q_periode)) {
        $perulist[] = $row;
    }
}

$id_periode_aktif = isset($_GET['periode']) ? (int)$_GET['periode'] : 0;
if ($id_periode_aktif <= 0 && !empty($perulist)) {
    $id_periode_aktif = (int)$perulist[0]['id_periode'];
}

$periode_aktif = null;
foreach ($perulist as $p) {
    if ((int)$p['id_periode'] === $id_periode_aktif) {
        $periode_aktif = $p;
        break;
    }
}

if (!$periode_aktif && !empty($perulist)) {
    $periode_aktif = $perulist[0];
    $id_periode_aktif = (int)$periode_aktif['id_periode'];
}

/* ============================================================
   PROSES SIMPAN LAPORAN
   PB-051 + PB-052
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_report') {
    $id_periode_post = isset($_POST['id_periode']) ? (int)$_POST['id_periode'] : 0;

    if ($id_periode_post <= 0) {
        $pesan_error = "Periode diklat belum dipilih.";
    } else {
        $laporan_lama = null;
        $q_lama = mysqli_query($conn, "
            SELECT *
            FROM laporan
            WHERE id_periode = '{$id_periode_post}'
            ORDER BY id_laporan DESC
            LIMIT 1
        ");
        if ($q_lama && mysqli_num_rows($q_lama) > 0) {
            $laporan_lama = mysqli_fetch_assoc($q_lama);
        }

        $upload_dir = __DIR__ . '/uploads/laporan_kepala';

        try {
            $file_polda = upload_pdf('file_laporan_polda', $upload_dir, 'laporan_polda');
            $file_penilaian = upload_pdf('file_laporan_penilaian', $upload_dir, 'laporan_penilaian');
            $file_surat = upload_pdf('file_surat_pernyataan', $upload_dir, 'surat_pernyataan');

            if (!$laporan_lama && !$file_polda && !$file_penilaian && !$file_surat) {
                throw new Exception("Minimal satu file harus diunggah saat membuat laporan baru.");
            }

            $final_polda = $file_polda ?: ($laporan_lama['file_laporan_polda'] ?? null);
            $final_penilaian = $file_penilaian ?: ($laporan_lama['file_laporan_penilaian'] ?? null);
            $final_surat = $file_surat ?: ($laporan_lama['file_surat_pernyataan'] ?? null);

            $tgl_generate = date('Y-m-d H:i:s');

            if ($laporan_lama) {
                $sql = "
                    UPDATE laporan SET
                        tgl_generate = '" . mysqli_real_escape_string($conn, $tgl_generate) . "',
                        dikonfirmasi_kepala = 1,
                        tgl_konfirmasi_kepala = NOW(),
                        dilihat_ceo = 0,
                        tgl_dilihat_ceo = NULL,
                        file_laporan_polda = " . ($final_polda !== null ? "'" . mysqli_real_escape_string($conn, $final_polda) . "'" : "NULL") . ",
                        file_laporan_penilaian = " . ($final_penilaian !== null ? "'" . mysqli_real_escape_string($conn, $final_penilaian) . "'" : "NULL") . ",
                        file_surat_pernyataan = " . ($final_surat !== null ? "'" . mysqli_real_escape_string($conn, $final_surat) . "'" : "NULL") . "
                    WHERE id_laporan = '" . (int)$laporan_lama['id_laporan'] . "'
                ";
            } else {
                $sql = "
                    INSERT INTO laporan
                        (tgl_generate, dikonfirmasi_kepala, tgl_konfirmasi_kepala, dilihat_ceo, tgl_dilihat_ceo, id_periode, file_laporan_polda, file_laporan_penilaian, file_surat_pernyataan)
                    VALUES
                        (
                            '" . mysqli_real_escape_string($conn, $tgl_generate) . "',
                            1,
                            NOW(),
                            0,
                            NULL,
                            '" . $id_periode_post . "',
                            " . ($final_polda !== null ? "'" . mysqli_real_escape_string($conn, $final_polda) . "'" : "NULL") . ",
                            " . ($final_penilaian !== null ? "'" . mysqli_real_escape_string($conn, $final_penilaian) . "'" : "NULL") . ",
                            " . ($final_surat !== null ? "'" . mysqli_real_escape_string($conn, $final_surat) . "'" : "NULL") . "
                        )
                ";
            }

            if (mysqli_query($conn, $sql)) {
                header("Location: dashboard_kepala.php?periode=" . $id_periode_post . "&status=sukses");
                exit;
            }

            $pesan_error = "Gagal menyimpan laporan: " . mysqli_error($conn);
        } catch (Throwable $e) {
            $pesan_error = $e->getMessage();
        }
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'sukses') {
    $pesan_sukses = "Laporan berhasil disimpan.";
}

/* ============================================================
   DATA TAMPILAN
   ============================================================ */
$laporan_polda_list = [];
if ($id_periode_aktif > 0) {
    $q_laporan_polda = mysqli_query($conn, "
        SELECT lp.*, a.username AS username_polda, p.tahun, p.gelombang, p.status AS status_periode
        FROM laporan_polda lp
        LEFT JOIN akun a ON a.id_akun = lp.id_akun_polda
        LEFT JOIN periode_diklat p ON p.id_periode = lp.id_periode
        WHERE lp.id_periode = '{$id_periode_aktif}'
        ORDER BY lp.tgl_input DESC, lp.id_laporan_polda DESC
    ");
    if ($q_laporan_polda) {
        while ($row = mysqli_fetch_assoc($q_laporan_polda)) {
            $laporan_polda_list[] = $row;
        }
    }
}

$evaluasi_list = [];
if ($id_periode_aktif > 0) {
    $q_evaluasi = mysqli_query($conn, "
        SELECT e.*, s.nama, s.email, s.status AS status_siswa
        FROM evaluasi e
        LEFT JOIN siswa s ON s.id_peserta = e.id_siswa
        WHERE e.id_periode = '{$id_periode_aktif}'
        ORDER BY e.rata_rata DESC, s.nama ASC
    ");
    if ($q_evaluasi) {
        while ($row = mysqli_fetch_assoc($q_evaluasi)) {
            $evaluasi_list[] = $row;
        }
    }
}

$arsip_list = [];
$q_arsip = mysqli_query($conn, "
    SELECT l.*, p.tahun, p.gelombang, p.status AS status_periode
    FROM laporan l
    LEFT JOIN periode_diklat p ON p.id_periode = l.id_periode
    ORDER BY l.tgl_generate DESC, l.id_laporan DESC
");
if ($q_arsip) {
    while ($row = mysqli_fetch_assoc($q_arsip)) {
        $arsip_list[] = $row;
    }
}

$laporan_kepala = null;
if ($id_periode_aktif > 0) {
    foreach ($arsip_list as $row) {
        if ((int)$row['id_periode'] === (int)$id_periode_aktif) {
            $laporan_kepala = $row;
            break;
        }
    }
}

$total_evaluasi = count($evaluasi_list);
$total_lulus = 0;
$total_tidak_lulus = 0;
$total_rata = 0;
foreach ($evaluasi_list as $item) {
    $total_rata += (float)($item['rata_rata'] ?? 0);
    if (($item['hasil'] ?? '') === 'lulus') {
        $total_lulus++;
    } elseif (($item['hasil'] ?? '') === 'tidak_lulus') {
        $total_tidak_lulus++;
    }
}
$avg_rata = $total_evaluasi > 0 ? $total_rata / $total_evaluasi : 0;
$laporan_count = count($arsip_list);
$username = $_SESSION['username'] ?? '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kepala Keamanan - Laporan Diklat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/dashboard_kepala.css">
</head>
<body class="phase6-body">

<nav class="navbar navbar-expand-lg navbar-dark phase6-navbar">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-bold" href="dashboard_kepala.php">Kepala Keamanan</a>
        <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
            <a href="dashboard_admin.php" class="btn btn-sm btn-light">Dashboard Admin</a>
            <a href="dashboard_ceo.php" class="btn btn-sm btn-outline-light">Dashboard CEO</a>
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>
</nav>

<main class="container-fluid px-3 px-lg-4 py-4">
    <section class="phase6-hero mb-4">
        <div class="row g-3 align-items-stretch">
            <div class="col-lg-8">
                <div class="phase6-hero-card h-100">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                        <div>
                            <span class="phase6-eyebrow">PB-050 · PB-051 · PB-052 · PB-053</span>
                            <h1 class="phase6-title mt-2">Laporan Hasil Diklat</h1>
                            <p class="phase6-subtitle mb-0">
                                Kepala Keamanan melihat hasil diklat, menyusun laporan pelatihan, menyimpan arsip, dan menyiapkan berkas untuk CEO.
                            </p>
                        </div>
                        <div class="text-md-end">
                            <div class="phase6-badge phase6-badge-success">Role Aktif</div>
                            <div class="mt-2 text-white-50 small"><?php echo e($username); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="phase6-hero-card phase6-hero-card-alt h-100">
                    <div class="small text-uppercase text-white-50">Periode Terpilih</div>
                    <div class="display-6 fw-bold mt-1"><?php echo e(period_label($periode_aktif)); ?></div>
                    <div class="mt-2">
                        <span class="phase6-badge phase6-badge-warning"><?php echo e($periode_aktif['status'] ?? '-'); ?></span>
                    </div>
                    <div class="mt-3 small text-white-50">
                        Arsip laporan tersimpan di tabel <code class="text-white">laporan</code>.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if ($pesan_sukses !== ''): ?>
        <div class="alert alert-success shadow-sm border-0"><?php echo e($pesan_sukses); ?></div>
    <?php endif; ?>

    <?php if ($pesan_error !== ''): ?>
        <div class="alert alert-danger shadow-sm border-0"><?php echo e($pesan_error); ?></div>
    <?php endif; ?>

    <section class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="phase6-metric-card">
                <div class="text-white-50 small">Evaluasi Masuk</div>
                <div class="phase6-metric-value"><?php echo (int)$total_evaluasi; ?></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="phase6-metric-card">
                <div class="text-white-50 small">Lulus</div>
                <div class="phase6-metric-value"><?php echo (int)$total_lulus; ?></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="phase6-metric-card">
                <div class="text-white-50 small">Tidak Lulus</div>
                <div class="phase6-metric-value"><?php echo (int)$total_tidak_lulus; ?></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="phase6-metric-card">
                <div class="text-white-50 small">Arsip Laporan</div>
                <div class="phase6-metric-value"><?php echo (int)$laporan_count; ?></div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-lg-8">
            <div class="phase6-panel mb-4">
                <div class="phase6-panel-header d-flex flex-column flex-md-row justify-content-between gap-2">
                    <div>
                        <h2 class="phase6-panel-title mb-1">Laporan Hasil Diklat</h2>
                        <p class="phase6-panel-subtitle mb-0">Data evaluasi peserta dan laporan Polda untuk periode yang dipilih.</p>
                    </div>
                    <form method="get" class="d-flex gap-2 align-items-center flex-wrap">
                        <label class="text-white-50 small mb-0">Periode</label>
                        <select name="periode" class="form-select form-select-sm phase6-select" onchange="this.form.submit()">
                            <?php foreach ($perulist as $p): ?>
                                <option value="<?php echo (int)$p['id_periode']; ?>" <?php echo ((int)$p['id_periode'] === (int)$id_periode_aktif) ? 'selected' : ''; ?>>
                                    <?php echo e(period_label($p)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="table-responsive phase6-table-wrap">
                    <table class="table align-middle phase6-table mb-0">
                        <thead>
                            <tr>
                                <th>Nama Peserta</th>
                                <th>Teori</th>
                                <th>Fisik</th>
                                <th>Disiplin</th>
                                <th>Praktik</th>
                                <th>Rata-rata</th>
                                <th>Hasil</th>
                                <th>Konfirmasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($evaluasi_list)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-white-50">Belum ada evaluasi untuk periode ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($evaluasi_list as $item): ?>
                                    <?php $badge = result_badge($item['hasil'] ?? ''); ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo e($item['nama'] ?? '-'); ?></div>
                                            <div class="small text-white-50"><?php echo e($item['email'] ?? '-'); ?></div>
                                        </td>
                                        <td><?php echo e(number_format((float)($item['nilai_teori'] ?? 0), 2)); ?></td>
                                        <td><?php echo e(number_format((float)($item['nilai_fisik'] ?? 0), 2)); ?></td>
                                        <td><?php echo e(number_format((float)($item['nilai_disiplin'] ?? 0), 2)); ?></td>
                                        <td><?php echo e(number_format((float)($item['nilai_praktik'] ?? 0), 2)); ?></td>
                                        <td><?php echo e(number_format((float)($item['rata_rata'] ?? 0), 2)); ?></td>
                                        <td><span class="phase6-badge <?php echo e($badge['class']); ?>"><?php echo e($badge['label']); ?></span></td>
                                        <td>
                                            <?php if ((int)($item['dikonfirmasi_admin'] ?? 0) === 1): ?>
                                                <span class="phase6-badge phase6-badge-success">Sudah</span>
                                            <?php else: ?>
                                                <span class="phase6-badge phase6-badge-warning">Belum</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="phase6-panel mb-4">
                <div class="phase6-panel-header">
                    <h2 class="phase6-panel-title mb-1">Laporan Kegiatan dari Polda</h2>
                    <p class="phase6-panel-subtitle mb-0">Referensi utama sebelum laporan kepala keamanan disimpan.</p>
                </div>

                <div class="table-responsive phase6-table-wrap">
                    <table class="table align-middle phase6-table mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Polda</th>
                                <th>Pengeluaran</th>
                                <th>Pemasukan</th>
                                <th>Deskripsi</th>
                                <th>File</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($laporan_polda_list)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-white-50">Belum ada laporan dari Polda untuk periode ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($laporan_polda_list as $row): ?>
                                    <tr>
                                        <td><?php echo e(date('d M Y H:i', strtotime($row['tgl_input']))); ?></td>
                                        <td><?php echo e($row['username_polda'] ?? '-'); ?></td>
                                        <td><?php echo e(rupiah($row['pengeluaran'] ?? 0)); ?></td>
                                        <td><?php echo e(rupiah($row['pemasukan'] ?? 0)); ?></td>
                                        <td class="text-truncate" style="max-width: 260px;"><?php echo e($row['deskripsi_kegiatan'] ?? '-'); ?></td>
                                        <td>
                                            <?php if (!empty($row['file_laporan'])): ?>
                                                <a class="phase6-file-link" href="<?php echo e($row['file_laporan']); ?>" target="_blank" rel="noopener">Lihat File</a>
                                            <?php else: ?>
                                                <span class="text-white-50">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="phase6-panel">
                <div class="phase6-panel-header">
                    <h2 class="phase6-panel-title mb-1">Arsip Laporan Diklat</h2>
                    <p class="phase6-panel-subtitle mb-0">Semua laporan yang sudah disimpan dan siap dipakai pada tahap CEO.</p>
                </div>

                <div class="table-responsive phase6-table-wrap">
                    <table class="table align-middle phase6-table mb-0">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Konfirmasi Kepala</th>
                                <th>CEO</th>
                                <th>File Polda</th>
                                <th>File Penilaian</th>
                                <th>Surat Pernyataan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($arsip_list)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-white-50">Belum ada arsip laporan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($arsip_list as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo e(period_label($row)); ?></div>
                                            <div class="small text-white-50"><?php echo e(date('d M Y H:i', strtotime($row['tgl_generate']))); ?></div>
                                        </td>
                                        <td>
                                            <?php if ((int)($row['dikonfirmasi_kepala'] ?? 0) === 1): ?>
                                                <span class="phase6-badge phase6-badge-success">Terkonfirmasi</span>
                                            <?php else: ?>
                                                <span class="phase6-badge phase6-badge-warning">Belum</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ((int)($row['dilihat_ceo'] ?? 0) === 1): ?>
                                                <span class="phase6-badge phase6-badge-success">Sudah</span>
                                            <?php else: ?>
                                                <span class="phase6-badge phase6-badge-warning">Belum</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['file_laporan_polda'])): ?>
                                                <a class="phase6-file-link" target="_blank" rel="noopener" href="<?php echo e($row['file_laporan_polda']); ?>">Buka</a>
                                            <?php else: ?>
                                                <span class="text-white-50">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['file_laporan_penilaian'])): ?>
                                                <a class="phase6-file-link" target="_blank" rel="noopener" href="<?php echo e($row['file_laporan_penilaian']); ?>">Buka</a>
                                            <?php else: ?>
                                                <span class="text-white-50">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['file_surat_pernyataan'])): ?>
                                                <a class="phase6-file-link" target="_blank" rel="noopener" href="<?php echo e($row['file_surat_pernyataan']); ?>">Buka</a>
                                            <?php else: ?>
                                                <span class="text-white-50">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="phase6-panel phase6-panel-form">
                <div class="phase6-panel-header">
                    <h2 class="phase6-panel-title mb-1">Susun & Simpan Laporan</h2>
                    <p class="phase6-panel-subtitle mb-0">Form ini menggabungkan PB-051 dan PB-052.</p>
                </div>

                <form method="post" enctype="multipart/form-data" class="phase6-form">
                    <input type="hidden" name="action" value="save_report">

                    <div class="mb-3">
                        <label class="form-label">Periode Diklat</label>
                        <select name="id_periode" class="form-select phase6-select" required>
                            <?php foreach ($perulist as $p): ?>
                                <option value="<?php echo (int)$p['id_periode']; ?>" <?php echo ((int)$p['id_periode'] === (int)$id_periode_aktif) ? 'selected' : ''; ?>>
                                    <?php echo e(period_label($p)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File Laporan Polda</label>
                        <input type="file" name="file_laporan_polda" class="form-control phase6-input" accept=".pdf">
                        <div class="form-text text-white-50">PDF. Kosongkan kalau file lama masih dipakai.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File Laporan Penilaian Admin</label>
                        <input type="file" name="file_laporan_penilaian" class="form-control phase6-input" accept=".pdf">
                        <div class="form-text text-white-50">Rekap nilai peserta dari admin.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">File Surat Pernyataan</label>
                        <input type="file" name="file_surat_pernyataan" class="form-control phase6-input" accept=".pdf">
                        <div class="form-text text-white-50">Surat penutupan kegiatan.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ringkasan Otomatis</label>
                        <div class="phase6-summary">
                            <div class="d-flex justify-content-between"><span>Total peserta dinilai</span><strong><?php echo (int)$total_evaluasi; ?></strong></div>
                            <div class="d-flex justify-content-between"><span>Rata-rata nilai</span><strong><?php echo e(number_format($avg_rata, 2)); ?></strong></div>
                            <div class="d-flex justify-content-between"><span>Arsip tersimpan</span><strong><?php echo (int)$laporan_count; ?></strong></div>
                        </div>
                    </div>

                    <?php if ($laporan_kepala): ?>
                        <div class="mb-3">
                            <label class="form-label">File Tersimpan Saat Ini</label>
                            <div class="phase6-current-files">
                                <div class="mb-2">
                                    <span class="text-white-50 small">Polda</span><br>
                                    <?php echo !empty($laporan_kepala['file_laporan_polda']) ? '<a class="phase6-file-link" target="_blank" rel="noopener" href="'.e($laporan_kepala['file_laporan_polda']).'">Buka file lama</a>' : '<span class="text-white-50">-</span>'; ?>
                                </div>
                                <div class="mb-2">
                                    <span class="text-white-50 small">Penilaian</span><br>
                                    <?php echo !empty($laporan_kepala['file_laporan_penilaian']) ? '<a class="phase6-file-link" target="_blank" rel="noopener" href="'.e($laporan_kepala['file_laporan_penilaian']).'">Buka file lama</a>' : '<span class="text-white-50">-</span>'; ?>
                                </div>
                                <div>
                                    <span class="text-white-50 small">Surat Pernyataan</span><br>
                                    <?php echo !empty($laporan_kepala['file_surat_pernyataan']) ? '<a class="phase6-file-link" target="_blank" rel="noopener" href="'.e($laporan_kepala['file_surat_pernyataan']).'">Buka file lama</a>' : '<span class="text-white-50">-</span>'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn phase6-btn w-100">Simpan Laporan</button>
                </form>
            </div>
        </div>
    </section>
</main>

</body>
</html>
