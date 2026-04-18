<?php
include "koneksi.php";
include "helpers.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nama          = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $alamat        = mysqli_real_escape_string($conn, trim($_POST['alamat']));
    $no_telp       = mysqli_real_escape_string($conn, trim($_POST['no_telp']));
    $email         = mysqli_real_escape_string($conn, trim($_POST['email']));
    $tgl_lahir     = $_POST['tgl_lahir'];
    $agama         = $_POST['agama'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $tinggi_badan  = (int) $_POST['tinggi_badan'];
    $berat_badan   = (int) $_POST['berat_badan'];

    /* Cek email duplikat */
    $cekEmail = mysqli_query($conn, "SELECT id_peserta FROM siswa WHERE email='$email' LIMIT 1");
    if (mysqli_num_rows($cekEmail) > 0) {
        $error_msg = "Email sudah terdaftar. Gunakan email lain.";
        goto tampilForm;
    }

    /* Generate username & password otomatis */
    $username = generateUsername($conn);
    $password = generatePassword(9);

    /* Simpan akun */
    $insertAkun = mysqli_query($conn,
        "INSERT INTO akun (username, password, role) VALUES ('$username', '$password', 'siswa')"
    );
    if (!$insertAkun) {
        $error_msg = "Gagal membuat akun: " . mysqli_error($conn);
        goto tampilForm;
    }
    $id_akun = mysqli_insert_id($conn);

    /* Simpan data siswa */
    $insertSiswa = mysqli_query($conn,
        "INSERT INTO siswa
            (nama, alamat, no_telp, email, tgl_lahir, agama,
             jenis_kelamin, status, id_akun, tinggi_badan, berat_badan)
         VALUES
            ('$nama','$alamat','$no_telp','$email','$tgl_lahir','$agama',
             '$jenis_kelamin','calon','$id_akun','$tinggi_badan','$berat_badan')"
    );
    if (!$insertSiswa) {
        mysqli_query($conn, "DELETE FROM akun WHERE id_akun='$id_akun'");
        $error_msg = "Gagal menyimpan data: " . mysqli_error($conn);
        goto tampilForm;
    }
    $id_siswa = mysqli_insert_id($conn);

    /* Buat folder upload per siswa */
    $folder      = "uploads/" . $id_siswa;
    if (!is_dir($folder)) mkdir($folder, 0777, true);

    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
    $max_size    = 5 * 1024 * 1024; /* 5 MB */

    /**
     * uploadDokumen — upload 1 file dan simpan ke tabel dokumen_pendaftaran
     * Return: true jika sukses, string pesan error jika gagal
     */
    function uploadDokumen($input_name, $jenis, $id_siswa, $folder, $conn, $allowed_ext, $max_size) {
        if (empty($_FILES[$input_name]['name'])) return true;

        $tmp  = $_FILES[$input_name]['tmp_name'];
        $size = $_FILES[$input_name]['size'];
        $ext  = strtolower(pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION));

        if ($size > $max_size)          return "File $jenis terlalu besar (maks 5MB).";
        if (!in_array($ext, $allowed_ext)) return "Format file $jenis tidak valid. Gunakan JPG, PNG, atau PDF.";

        $file_path = "$folder/$jenis.$ext";
        if (!move_uploaded_file($tmp, $file_path)) return "Gagal mengupload file $jenis.";

        $fp  = mysqli_real_escape_string($conn, $file_path);
        $jen = mysqli_real_escape_string($conn, $jenis);
        $tgl = date("Y-m-d H:i:s");
        mysqli_query($conn,
            "INSERT INTO dokumen_pendaftaran (jenis, file_path, status_verifikasi, tgl_upload, id_siswa)
             VALUES ('$jen','$fp','pending','$tgl','$id_siswa')"
        );
        return true;
    }

    /* Daftar dokumen yang perlu diupload — surat_kesehatan ditambahkan (fix bug mismatch admin) */
    $dokumen_list = [
        'ktp'             => 'KTP',
        'ijazah'          => 'Ijazah',
        'kk'              => 'KK',
        'skck'            => 'SKCK',
        'pembayaran'      => 'Bukti Pembayaran',
        'surat_kesehatan' => 'Surat Kesehatan',
    ];

    foreach ($dokumen_list as $input => $label) {
        $hasil = uploadDokumen($input, $input, $id_siswa, $folder, $conn, $allowed_ext, $max_size);
        if ($hasil !== true) {
            /* Rollback semua data jika upload gagal */
            mysqli_query($conn, "DELETE FROM dokumen_pendaftaran WHERE id_siswa='$id_siswa'");
            mysqli_query($conn, "DELETE FROM siswa WHERE id_peserta='$id_siswa'");
            mysqli_query($conn, "DELETE FROM akun WHERE id_akun='$id_akun'");
            $error_msg = $hasil;
            goto tampilForm;
        }
    }

    /* Kirim email kredensial ke pendaftar */
    $emailTerkirim = kirimEmailAkun($email, $nama, $username, $password);

    session_start();
    $_SESSION['daftar_sukses']  = true;
    $_SESSION['daftar_nama']    = $nama;
    $_SESSION['daftar_email']   = $email;
    $_SESSION['email_terkirim'] = $emailTerkirim;

    header("Location: daftar_sukses.php");
    exit;
}

tampilForm:
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pendaftaran — Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/daftar.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body class="daftar-page">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container daftar-container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard_umum.php">
            <img src="img/logo.png" alt="Logo" class="logo-navbar">
            <span class="ms-2">Gemilang</span>
        </a>
    </div>
</nav>

<section class="py-5">
    <div class="container daftar-container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-7">
                <div class="form-card">

                    <h3 class="form-title text-center mb-4">Form Pendaftaran</h3>

                    <!-- Error dari server ditampilkan sebagai alert Bootstrap -->
                    <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                        <span style="font-size:18px;">⚠️</span>
                        <div><?php echo htmlspecialchars($error_msg); ?></div>
                    </div>
                    <?php endif; ?>

                    <form id="formDaftar" action="daftar.php" method="POST"
                          enctype="multipart/form-data" novalidate>

                        <!-- ===== DATA DIRI ===== -->
                        <h5 class="section-label">Data Diri</h5>

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>">
                            <div class="invalid-feedback">Nama wajib diisi.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor HP <span class="text-danger">*</span></label>
                            <input type="text" name="no_telp" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['no_telp'] ?? ''); ?>">
                            <div class="invalid-feedback">Nomor HP wajib diisi.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            <div class="form-text">Username & password akan dikirimkan ke email ini.</div>
                            <div class="invalid-feedback">Format email tidak valid.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat <span class="text-danger">*</span></label>
                            <textarea name="alamat" class="form-control" rows="3" required><?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
                            <div class="invalid-feedback">Alamat wajib diisi.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                            <input type="date" name="tgl_lahir" class="form-control" required
                                   value="<?php echo htmlspecialchars($_POST['tgl_lahir'] ?? ''); ?>">
                            <div class="invalid-feedback">Tanggal lahir wajib diisi.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-block">Jenis Kelamin <span class="text-danger">*</span></label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="jenis_kelamin" value="L" required
                                       <?php echo (($_POST['jenis_kelamin'] ?? '') === 'L') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Laki-laki</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="jenis_kelamin" value="P"
                                       <?php echo (($_POST['jenis_kelamin'] ?? '') === 'P') ? 'checked' : ''; ?>>
                                <label class="form-check-label">Perempuan</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tinggi Badan (cm) <span class="text-danger">*</span></label>
                            <input type="number" name="tinggi_badan" class="form-control" required min="100" max="250"
                                   value="<?php echo htmlspecialchars($_POST['tinggi_badan'] ?? ''); ?>">
                            <div class="invalid-feedback">Tinggi badan tidak valid (100–250 cm).</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Berat Badan (kg) <span class="text-danger">*</span></label>
                            <input type="number" name="berat_badan" class="form-control" required min="30" max="200"
                                   value="<?php echo htmlspecialchars($_POST['berat_badan'] ?? ''); ?>">
                            <div class="invalid-feedback">Berat badan tidak valid (30–200 kg).</div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Agama <span class="text-danger">*</span></label>
                            <select name="agama" class="form-select" required>
                                <option value="">Pilih Agama</option>
                                <?php foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag): ?>
                                <option <?php echo (($_POST['agama'] ?? '') === $ag) ? 'selected' : ''; ?>>
                                    <?php echo $ag; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Agama wajib dipilih.</div>
                        </div>

                        <hr>

                        <!-- ===== UPLOAD DOKUMEN ===== -->
                        <h5 class="section-label">Upload Dokumen</h5>
                        <div class="alert alert-info d-flex gap-2 align-items-start mb-3" style="font-size:13px;padding:10px 14px;">
                            <span style="flex-shrink:0;">ℹ️</span>
                            <div>Format: <strong>JPG, PNG, PDF</strong> — Maks. <strong>5MB</strong> per file. Semua dokumen wajib diupload.</div>
                        </div>

                        <?php
                        /* Render input dokumen secara dinamis agar konsisten */
                        $dok_labels = [
                            'ktp'             => 'KTP',
                            'ijazah'          => 'Ijazah Terakhir',
                            'kk'              => 'Kartu Keluarga (KK)',
                            'skck'            => 'SKCK',
                            'pembayaran'      => 'Bukti Pembayaran DP',
                            'surat_kesehatan' => 'Surat Kesehatan',
                        ];
                        $dok_hints = [
                            'surat_kesehatan' => 'Surat keterangan sehat dari dokter/puskesmas.',
                            'pembayaran'      => 'Bukti transfer atau kwitansi DP.',
                        ];
                        $last_key = array_key_last($dok_labels);
                        foreach ($dok_labels as $key => $label):
                            $is_last = ($key === $last_key);
                        ?>
                        <div class="<?php echo $is_last ? 'mb-4' : 'mb-3'; ?>">
                            <label class="form-label"><?php echo $label; ?> <span class="text-danger">*</span></label>
                            <input type="file" name="<?php echo $key; ?>" class="form-control file-input"
                                   accept=".jpg,.jpeg,.png,.pdf" required
                                   data-label="<?php echo $label; ?>">
                            <?php if (isset($dok_hints[$key])): ?>
                            <div class="form-text"><?php echo $dok_hints[$key]; ?></div>
                            <?php endif; ?>
                            <div class="invalid-feedback">File <?php echo $label; ?> wajib diupload.</div>
                        </div>
                        <?php endforeach; ?>

                        <button type="submit" class="btn btn-primary w-100 fw-bold" id="btnSubmit">
                            Kirim Pendaftaran
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
<script>
const MAX_SIZE = 5 * 1024 * 1024; /* 5MB dalam bytes */

/* Validasi ukuran file saat dipilih — feedback instan sebelum submit */
document.querySelectorAll('.file-input').forEach(function(input) {
    input.addEventListener('change', function() {
        const file  = this.files[0];
        const label = this.dataset.label;
        if (file && file.size > MAX_SIZE) {
            Swal.fire({
                title: 'File Terlalu Besar',
                html: `<b>${label}</b> melebihi batas 5MB.<br>Ukuran file: <b>${(file.size/1024/1024).toFixed(1)} MB</b>.`,
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
            this.value = ''; /* reset pilihan file */
        }
    });
});

/* Validasi form keseluruhan sebelum submit */
document.getElementById('formDaftar').addEventListener('submit', function(e) {

    /* Pakai Bootstrap native validation */
    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('was-validated');

        /* Scroll ke field pertama yang error */
        const firstInvalid = this.querySelector(':invalid');
        if (firstInvalid) firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });

        Swal.fire({
            title: 'Form Belum Lengkap',
            text: 'Lengkapi semua field yang wajib diisi (ditandai merah).',
            icon: 'warning',
            confirmButtonColor: '#0d1b2a'
        });
        return;
    }

    /* Cek ulang ukuran file saat submit sebagai safety net */
    let ada_oversize = false, nama_error = '';
    document.querySelectorAll('.file-input').forEach(function(input) {
        if (input.files[0] && input.files[0].size > MAX_SIZE) {
            ada_oversize = true;
            nama_error   = input.dataset.label;
        }
    });
    if (ada_oversize) {
        e.preventDefault();
        Swal.fire({
            title: 'File Terlalu Besar',
            text: `File ${nama_error} melebihi 5MB. Pilih file yang lebih kecil.`,
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
        return;
    }

    /* Loading state di tombol submit */
    const btn  = document.getElementById('btnSubmit');
    btn.innerHTML = '⏳ Mengirim pendaftaran...';
    btn.disabled  = true;
});

/* Popup error dari server-side (jika PHP redirect balik dengan error) */
<?php if (!empty($error_msg)): ?>
window.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Pendaftaran Gagal',
        text: '<?php echo addslashes($error_msg); ?>',
        icon: 'error',
        confirmButtonColor: '#dc3545'
    });
});
<?php endif; ?>
</script>
</body>
</html>
