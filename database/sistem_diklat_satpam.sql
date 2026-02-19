-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 19, 2026 at 03:14 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `dokumen_pendaftaran`
--

DROP TABLE IF EXISTS `dokumen_pendaftaran`;
CREATE TABLE `dokumen_pendaftaran` (
  `id_dokumen` int(11) NOT NULL,
  `jenis` enum('ktp','ijazah','kk','pembayaran','skck','vaksin') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status_verifikasi` enum('pending','valid','revisi') NOT NULL,
  `catatan_admin` text DEFAULT NULL,
  `tgl_upload` datetime NOT NULL,
  `id_peserta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` enum('calon','terverifikasi','peserta','lulus',' tidak_lulus') NOT NULL,
  `id_akun` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peserta_periode`
--

DROP TABLE IF EXISTS `peserta_periode`;
CREATE TABLE `peserta_periode` (
  `id_peserta_periode` int(11) NOT NULL,
  `tanggal_terima` date NOT NULL,
  `id_peserta` int(11) DEFAULT NULL,
  `id_periode` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  ADD KEY `fk_dokumen_peserta` (`id_peserta`);

--
-- Indexes for table `evaluasi`
--
ALTER TABLE `evaluasi`
  ADD PRIMARY KEY (`id_evaluasi`),
  ADD KEY `fk_evaluasi_peserta` (`id_peserta`);

--
-- Indexes for table `jadwal_diklat`
--
ALTER TABLE `jadwal_diklat`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `fk_jadwal_periode` (`id_periode`);

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
  ADD KEY `fk_peserta_akun` (`id_akun`);

--
-- Indexes for table `peserta_periode`
--
ALTER TABLE `peserta_periode`
  ADD PRIMARY KEY (`id_peserta_periode`),
  ADD KEY `fk_pp_peserta` (`id_peserta`),
  ADD KEY `fk_pp_periode` (`id_periode`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `akun`
--
ALTER TABLE `akun`
  MODIFY `id_akun` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dokumen_pendaftaran`
--
ALTER TABLE `dokumen_pendaftaran`
  MODIFY `id_dokumen` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluasi`
--
ALTER TABLE `evaluasi`
  MODIFY `id_evaluasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jadwal_diklat`
--
ALTER TABLE `jadwal_diklat`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `periode_diklat`
--
ALTER TABLE `periode_diklat`
  MODIFY `id_periode` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `peserta`
--
ALTER TABLE `peserta`
  MODIFY `id_peserta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `peserta_periode`
--
ALTER TABLE `peserta_periode`
  MODIFY `id_peserta_periode` int(11) NOT NULL AUTO_INCREMENT;

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
