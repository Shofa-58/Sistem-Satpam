<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pendaftaran - Gemilang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="dashboard.php">
        <img src="img/logo.png" alt="Logo" class="logo-navbar">
        <span class="ms-2">Gemilang</span>
    </a>
  </div>
</nav>

<!-- Form Section -->
<section class="py-5" style="margin-top:100px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">

                <div class="card p-4">

                    <h3 class="fw-bold text-center mb-4">Form Pendaftaran</h3>

                    <form enctype="multipart/form-data">

                        <!-- Data Diri -->
                        <h5 class="fw-bold mb-3">Data Diri</h5>

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nomor HP</label>
                            <input type="text" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap</label>
                            <textarea class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select class="form-select" required>
                                <option selected disabled>Pilih</option>
                                <option>Laki-laki</option>
                                <option>Perempuan</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Agama</label>
                            <select class="form-select" required>
                                <option selected disabled>Pilih</option>
                                <option>Islam</option>
                                <option>Kristen</option>
                                <option>Katolik</option>
                                <option>Hindu</option>
                                <option>Buddha</option>
                                <option>Konghucu</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Program Pelatihan</label>
                            <select class="form-select" required>
                                <option selected disabled>Pilih Program</option>
                                <option>Gada Pratama</option>
                                <option>Gada Madya</option>
                            </select>
                        </div>

                        <hr>

                        <!-- Upload Dokumen -->
                        <h5 class="fw-bold mt-4 mb-3">Upload Dokumen</h5>

                        <div class="mb-3">
                            <label class="form-label">Ijazah</label>
                            <input type="file" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SKCK</label>
                            <input type="file" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">KTP</label>
                            <input type="file" class="form-control" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Bukti Pembayaran</label>
                            <input type="file" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-navy w-100">
                            Kirim Pendaftaran
                        </button>

                    </form>

                </div>

            </div>
        </div>
    </div>
</section>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
