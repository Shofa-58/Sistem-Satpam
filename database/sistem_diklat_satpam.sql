-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 20, 2026 at 08:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_diklat_satpam`
--

-- --------------------------------------------------------

--
-- Table structure for table `akun`
--

DROP TABLE IF EXISTS `akun`;
CREATE TABLE `akun` (
  `id_akun` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('siswa','admin','publikasi','kepala_keamanan','polda','ceo') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `akun`
--

INSERT INTO `akun` (`id_akun`, `username`, `password`, `role`) VALUES
(1, 'siswa01', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'siswa'),
(2, 'admin01', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'admin'),
(3, 'publikasi01', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'publikasi'),
(4, 'polda01', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'polda'),
(5, 'kepala01', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'kepala_keamanan'),
(6, 'ceo01', 'a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3', 'ceo');

-- --------------------------------------------------------

--
-- Table structure for table `dokumen_pendaftaran`
--

DROP TABLE IF EXISTS `dokumen_pendaftaran`;
CREATE TABLE `dokumen_pendaftaran` (
  `id_dokumen` int(11) NOT NULL,
  `jenis` enum('ktp','ijazah','kk','pembayaran','skck','surat_kesehatan') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status_verifikasi` enum('pending','valid','revisi') NOT NULL,
  `catatan_admin` text DEFAULT NULL,
  `tgl_upload` datetime NOT NULL,
  `id_peserta` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dokumen_pendaftaran`
--

INSERT INTO `dokumen_pendaftaran` (`id_dokumen`, `jenis`, `file_path`, `status_verifikasi`, `catatan_admin`, `tgl_upload`, `id_peserta`, `created_at`) VALUES
(1, 'ktp', 'uploads/1/ktp.jpg', 'valid', NULL, '2026-02-20 14:10:42', 1, '2026-02-20 07:28:36'),
(2, 'ijazah', 'uploads/1/ijazah.jpg', 'valid', NULL, '2026-02-20 14:10:42', 1, '2026-02-20 07:28:36'),
(3, 'kk', 'uploads/1/kk.jpg', 'valid', NULL, '2026-02-20 14:10:42', 1, '2026-02-20 07:28:36'),
(4, 'pembayaran', 'uploads/1/bayar.jpg', 'pending', 'Menunggu konfirmasi', '2026-02-20 14:10:42', 1, '2026-02-20 07:28:36');

-- --------------------------------------------------------

--
-- Table structure for table `evaluasi`
--

DROP TABLE IF EXISTS `evaluasi`;
CREATE TABLE `evaluasi` (
  `id_evaluasi` int(11) NOT NULL,
  `nilai_teori` decimal(5,2) NOT NULL,
  `nilai_fisik` decimal(5,2) NOT NULL,
  `nilai_mental` decimal(5,2) NOT NULL,
  `rata_rata` decimal(5,2) NOT NULL,
  `hasil` enum('lulus','tidak_lulus') NOT NULL,
  `tgl_input` date NOT NULL,
  `id_peserta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluasi`
--

INSERT INTO `evaluasi` (`id_evaluasi`, `nilai_teori`, `nilai_fisik`, `nilai_mental`, `rata_rata`, `hasil`, `tgl_input`, `id_peserta`) VALUES
(1, 85.00, 80.00, 90.00, 85.00, 'lulus', '2026-03-18', 1);

-- --------------------------------------------------------

--
-- Table structure for table `informasi_diklat`
--

DROP TABLE IF EXISTS `informasi_diklat`;
CREATE TABLE `informasi_diklat` (
  `id_info` int(11) NOT NULL,
  `id_periode` int(11) NOT NULL,
  `brosur_path` varchar(255) DEFAULT NULL,
  `estimasi_bulan` tinyint(4) NOT NULL,
  `tanggal_fix` date DEFAULT NULL,
  `tempat` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `informasi_diklat`
--

INSERT INTO `informasi_diklat` (`id_info`, `id_periode`, `brosur_path`, `estimasi_bulan`, `tanggal_fix`, `tempat`) VALUES
(1, 1, 'uploads/brosur_diklat1.jpg', 1, '2026-01-10', 'Pusdiklat Bandung'),
(2, 2, 'uploads/brosur_diklat2.jpg', 4, NULL, 'Pusdiklat Bandung');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_diklat`
--

DROP TABLE IF EXISTS `jadwal_diklat`;
CREATE TABLE `jadwal_diklat` (
  `id_jadwal` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `kegiatan` varchar(150) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `id_periode` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_diklat`
--

INSERT INTO `jadwal_diklat` (`id_jadwal`, `tanggal`, `kegiatan`, `keterangan`, `id_periode`) VALUES
(1, '2026-01-12', 'Pembukaan Diklat', 'Upacara pembukaan', 1),
(2, '2026-01-13', 'Latihan Fisik', 'Lapangan utama', 1),
(3, '2026-01-15', 'Materi Hukum', 'Ruang kelas', 1);

-- --------------------------------------------------------

--
-- Table structure for table `laporan`
--

DROP TABLE IF EXISTS `laporan`;
CREATE TABLE `laporan` (
  `id_laporan` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `file_pdf` varchar(255) NOT NULL,
  `tgl_generate` datetime NOT NULL,
  `id_periode` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan`
--

INSERT INTO `laporan` (`id_laporan`, `judul`, `file_pdf`, `tgl_generate`, `id_periode`) VALUES
(1, 'Laporan Diklat Gelombang 1', 'laporan/laporan1.pdf', '2026-03-25 00:00:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `periode_diklat`
--

DROP TABLE IF EXISTS `periode_diklat`;
CREATE TABLE `periode_diklat` (
  `id_periode` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `gelombang` tinyint(4) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `biaya` int(11) NOT NULL,
  `lokasi` varchar(150) NOT NULL,
  `fasilitas` text NOT NULL,
  `status` enum('pendaftaran','berjalan','selesai') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `periode_diklat`
--

INSERT INTO `periode_diklat` (`id_periode`, `tahun`, `gelombang`, `tanggal_mulai`, `tanggal_selesai`, `biaya`, `lokasi`, `fasilitas`, `status`) VALUES
(1, '2026', 1, '2026-01-10', '2026-03-20', 3500000, 'Bandung', 'Seragam, Modul, Konsumsi', 'berjalan'),
(2, '2026', 2, '2026-04-10', '2026-06-20', 3500000, 'Bandung', 'Seragam, Modul, Konsumsi', 'pendaftaran');

-- --------------------------------------------------------

--
-- Table structure for table `peserta`
--

DROP TABLE IF EXISTS `peserta`;
CREATE TABLE `peserta` (
  `id_peserta` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `no_telp` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `tgl_lahir` date NOT NULL,
  `agama` varchar(20) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `status` enum('calon','terverifikasi','peserta','lulus','tidak_lulus') DEFAULT NULL,
  `id_akun` int(11) DEFAULT NULL,
  `tinggi_badan` smallint(6) NOT NULL,
  `berat_badan` smallint(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peserta`
--

INSERT INTO `peserta` (`id_peserta`, `nama`, `alamat`, `no_telp`, `email`, `tgl_lahir`, `agama`, `jenis_kelamin`, `status`, `id_akun`, `tinggi_badan`, `berat_badan`, `created_at`) VALUES
(1, 'Budi Santoso', 'Bandung', '08123456789', 'budi@gmail.com', '2002-05-12', 'Islam', 'L', 'peserta', 1, 170, 65, '2026-02-20 07:28:36'),
(2, 'Andi Wijaya', 'Garut', '08222222222', 'andi@gmail.com', '2001-02-02', 'Islam', 'L', 'calon', NULL, 168, 60, '2026-02-20 07:28:36');

-- --------------------------------------------------------

--
-- Table structure for table `peserta_periode`
--

DROP TABLE IF EXISTS `peserta_periode`;
CREATE TABLE `peserta_periode` (
  `id_peserta_periode` int(11) NOT NULL,
  `tanggal_terima` date NOT NULL,
  `id_peserta` int(11) DEFAULT NULL,
  `id_periode` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peserta_periode`
--

INSERT INTO `peserta_periode` (`id_peserta_periode`, `tanggal_terima`, `id_peserta`, `id_periode`, `created_at`) VALUES
(1, '2026-01-05', 1, 1, '2026-02-20 07:28:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `akun`
--
ALTER TABLE `akun`
  ADD PRIMARY KEY (`id_akun`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `dokumen_pendaftaran`
--
ALTER TABLE `dokumen_pendaftaran`
  ADD PRIMARY KEY (`id_dokumen`),
  ADD UNIQUE KEY `unique_dokumen` (`id_peserta`,`jenis`);

--
-- Indexes for table `evaluasi`
--
ALTER TABLE `evaluasi`
  ADD PRIMARY KEY (`id_evaluasi`),
  ADD UNIQUE KEY `unique_evaluasi` (`id_peserta`);

--
-- Indexes for table `informasi_diklat`
--
ALTER TABLE `informasi_diklat`
  ADD PRIMARY KEY (`id_info`),
  ADD UNIQUE KEY `unique_info_periode` (`id_periode`);

--
-- Indexes for table `jadwal_diklat`
--
ALTER TABLE `jadwal_diklat`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD UNIQUE KEY `unique_jadwal` (`id_periode`,`tanggal`,`kegiatan`);

--
-- Indexes for table `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id_laporan`),
  ADD KEY `fk_laporan_periode` (`id_periode`);

--
-- Indexes for table `periode_diklat`
--
ALTER TABLE `periode_diklat`
  ADD PRIMARY KEY (`id_periode`);

--
-- Indexes for table `peserta`
--
ALTER TABLE `peserta`
  ADD PRIMARY KEY (`id_peserta`),
  ADD UNIQUE KEY `id_akun` (`id_akun`);

--
-- Indexes for table `peserta_periode`
--
ALTER TABLE `peserta_periode`
  ADD PRIMARY KEY (`id_peserta_periode`),
  ADD UNIQUE KEY `unique_peserta_periode` (`id_peserta`,`id_periode`),
  ADD KEY `fk_pp_periode` (`id_periode`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `akun`
--
ALTER TABLE `akun`
  MODIFY `id_akun` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `dokumen_pendaftaran`
--
ALTER TABLE `dokumen_pendaftaran`
  MODIFY `id_dokumen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `evaluasi`
--
ALTER TABLE `evaluasi`
  MODIFY `id_evaluasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `informasi_diklat`
--
ALTER TABLE `informasi_diklat`
  MODIFY `id_info` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jadwal_diklat`
--
ALTER TABLE `jadwal_diklat`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `periode_diklat`
--
ALTER TABLE `periode_diklat`
  MODIFY `id_periode` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `peserta`
--
ALTER TABLE `peserta`
  MODIFY `id_peserta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `peserta_periode`
--
ALTER TABLE `peserta_periode`
  MODIFY `id_peserta_periode` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dokumen_pendaftaran`
--
ALTER TABLE `dokumen_pendaftaran`
  ADD CONSTRAINT `fk_dokumen_peserta` FOREIGN KEY (`id_peserta`) REFERENCES `peserta` (`id_peserta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `evaluasi`
--
ALTER TABLE `evaluasi`
  ADD CONSTRAINT `fk_evaluasi_peserta` FOREIGN KEY (`id_peserta`) REFERENCES `peserta` (`id_peserta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `informasi_diklat`
--
ALTER TABLE `informasi_diklat`
  ADD CONSTRAINT `informasi_diklat_ibfk_1` FOREIGN KEY (`id_periode`) REFERENCES `periode_diklat` (`id_periode`) ON DELETE CASCADE;

--
-- Constraints for table `jadwal_diklat`
--
ALTER TABLE `jadwal_diklat`
  ADD CONSTRAINT `fk_jadwal_periode` FOREIGN KEY (`id_periode`) REFERENCES `periode_diklat` (`id_periode`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `laporan`
--
ALTER TABLE `laporan`
  ADD CONSTRAINT `fk_laporan_periode` FOREIGN KEY (`id_periode`) REFERENCES `periode_diklat` (`id_periode`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `peserta`
--
ALTER TABLE `peserta`
  ADD CONSTRAINT `fk_peserta_akun` FOREIGN KEY (`id_akun`) REFERENCES `akun` (`id_akun`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `peserta_periode`
--
ALTER TABLE `peserta_periode`
  ADD CONSTRAINT `fk_pp_periode` FOREIGN KEY (`id_periode`) REFERENCES `periode_diklat` (`id_periode`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pp_peserta` FOREIGN KEY (`id_peserta`) REFERENCES `peserta` (`id_peserta`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
