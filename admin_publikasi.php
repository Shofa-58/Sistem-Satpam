<?php
include "koneksi.php";

if(isset($_POST['submit'])){

    $id_periode      = $_POST['id_periode'];
    $estimasi_bulan  = $_POST['estimasi_bulan'];
    $tanggal_fix     = $_POST['tanggal_fix'];
    $tempat          = $_POST['tempat'];

    $brosur_name = null;

    if(!empty($_FILES['brosur']['name'])){

        $allowed = ['jpg','jpeg','png'];
        $file    = $_FILES['brosur']['name'];
        $tmp     = $_FILES['brosur']['tmp_name'];
        $ext     = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if(in_array($ext, $allowed)){

            if(!is_dir("uploads")){
                mkdir("uploads",0777,true);
            }

            $brosur_name = "brosur_".time().".".$ext;
            move_uploaded_file($tmp,"uploads/".$brosur_name);
        }
    }

    $query = "INSERT INTO informasi_diklat
              (id_periode, brosur_path, estimasi_bulan, tanggal_fix, tempat)
              VALUES
              ('$id_periode','$brosur_name','$estimasi_bulan','$tanggal_fix','$tempat')";

    if(mysqli_query($conn,$query)){
        header("Location: publikasi.php");
        exit;
    }else{
        echo "Gagal upload: ".mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Upload Publikasi</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-white">

<div class="container" style="margin-top:100px; max-width:700px;">

    <div class="card p-4">
        <h4 class="mb-4">Upload Informasi Diklat</h4>

        <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label class="form-label">Angkatan (angka saja)</label>
                <input type="number" name="id_periode" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Estimasi Bulan (1-12)</label>
                <input type="number" name="estimasi_bulan" min="1" max="12" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Tanggal Fix</label>
                <input type="date" name="tanggal_fix" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Tempat</label>
                <input type="text" name="tempat" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Upload Brosur (jpg/png)</label>
                <input type="file" name="brosur" class="form-control" required>
            </div>

            <button type="submit" name="submit" class="btn btn-primary w-100">
                Upload Publikasi
            </button>

        </form>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>