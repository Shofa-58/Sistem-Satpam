-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 22 Apr 2026 pada 13.54
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

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
-- Struktur dari tabel `akun`
--

CREATE TABLE `akun` (
  `id_akun` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('siswa','admin','publikasi','kepala_keamanan','polda','ceo') NOT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `akun`
--

INSERT INTO `akun` (`id_akun`, `username`, `password`, `role`, `dibuat_pada`) VALUES
(2, 'admin01', 'Gemilang1', 'admin', '2026-03-27 17:40:04'),
(3, 'publikasi01', 'Gemilang1', 'publikasi', '2026-03-27 17:40:04'),
(4, 'polda01', 'Gemilang1', 'polda', '2026-03-27 17:40:04'),
(5, 'kepala01', 'Gemilang1', 'kepala_keamanan', '2026-03-27 17:40:04'),
(6, 'ceo01', 'Gemilang1', 'ceo', '2026-03-27 17:40:04'),
(7, 'siswa01', 'Gemilang1', 'siswa', '2024-01-10 00:30:00'),
(8, 'siswa02', 'Gemilang1', 'siswa', '2024-01-10 01:15:00'),
(9, 'siswa03', 'Gemilang1', 'siswa', '2024-06-12 00:45:00'),
(10, 'siswa04', 'Gemilang1', 'siswa', '2024-06-12 01:30:00'),
(11, 'siswa05', 'Gemilang1', 'siswa', '2025-01-18 02:00:00'),
(12, 'siswa06', 'Gemilang1', 'siswa', '2026-03-20 01:00:00'),
(13, 'siswa07', 'Gemilang1', 'siswa', '2026-03-21 02:30:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `dokumen_pendaftaran`
--

CREATE TABLE `dokumen_pendaftaran` (
  `id_dokumen` int(11) NOT NULL,
  `jenis` enum('ktp','ijazah','kk','pembayaran','skck','surat_kesehatan') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status_verifikasi` enum('pending','valid','revisi') NOT NULL,
  `catatan_admin` text DEFAULT NULL,
  `tgl_upload` datetime NOT NULL,
  `tgl_revisi` datetime DEFAULT NULL,
  `id_siswa` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `dokumen_pendaftaran`
--

INSERT INTO `dokumen_pendaftaran` (`id_dokumen`, `jenis`, `file_path`, `status_verifikasi`, `catatan_admin`, `tgl_upload`, `tgl_revisi`, `id_siswa`, `created_at`) VALUES
(1, 'ktp', 'uploads/1/ktp.jpg', 'valid', NULL, '2024-01-10 07:35:00', NULL, 1, '2026-04-21 10:24:01'),
(2, 'ijazah', 'uploads/1/ijazah.jpg', 'valid', NULL, '2024-01-10 07:36:00', NULL, 1, '2026-04-21 10:24:01'),
(3, 'kk', 'uploads/1/kk.jpg', 'valid', NULL, '2024-01-10 07:37:00', NULL, 1, '2026-04-21 10:24:01'),
(4, 'skck', 'uploads/1/skck.pdf', 'valid', NULL, '2024-01-10 07:38:00', NULL, 1, '2026-04-21 10:24:01'),
(5, 'pembayaran', 'uploads/1/pembayaran.jpg', 'valid', NULL, '2024-01-10 07:39:00', NULL, 1, '2026-04-21 10:24:01'),
(6, 'surat_kesehatan', 'uploads/1/surat_kesehatan.pdf', 'valid', NULL, '2024-01-10 07:40:00', NULL, 1, '2026-04-21 10:24:01'),
(7, 'ktp', 'uploads/6/ktp.jpg', 'valid', NULL, '2026-03-20 08:05:00', NULL, 6, '2026-04-21 10:24:01'),
(8, 'ijazah', 'uploads/6/ijazah.jpg', 'valid', NULL, '2026-03-20 08:07:00', NULL, 6, '2026-04-21 10:24:01'),
(9, 'kk', 'uploads/6/kk.jpg', 'valid', NULL, '2026-03-20 08:09:00', NULL, 6, '2026-04-21 10:24:01'),
(10, 'skck', 'uploads/6/skck.pdf', 'valid', NULL, '2026-03-20 08:11:00', NULL, 6, '2026-04-21 10:24:01'),
(11, 'pembayaran', 'uploads/6/pembayaran.jpg', 'valid', NULL, '2026-03-20 08:13:00', NULL, 6, '2026-04-21 10:24:01'),
(12, 'surat_kesehatan', 'uploads/6/surat_kesehatan.pdf', 'valid', NULL, '2026-03-20 08:15:00', NULL, 6, '2026-04-21 10:24:01');

-- --------------------------------------------------------

--
-- Struktur dari tabel `evaluasi`
--

CREATE TABLE `evaluasi` (
  `id_evaluasi` int(11) NOT NULL,
  `nilai_teori` decimal(5,2) NOT NULL,
  `nilai_fisik` decimal(5,2) NOT NULL,
  `nilai_disiplin` decimal(5,2) NOT NULL,
  `nilai_praktik` decimal(5,2) NOT NULL,
  `rata_rata` decimal(5,2) NOT NULL,
  `hasil` enum('lulus','tidak_lulus') NOT NULL,
  `catatan` text DEFAULT NULL,
  `tgl_input` date NOT NULL,
  `id_siswa` int(11) DEFAULT NULL,
  `id_periode` int(11) DEFAULT NULL,
  `dikonfirmasi_admin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = belum dikonfirmasi, 1 = sudah dikonfirmasi admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `evaluasi`
--

INSERT INTO `evaluasi` (`id_evaluasi`, `nilai_teori`, `nilai_fisik`, `nilai_disiplin`, `nilai_praktik`, `rata_rata`, `hasil`, `catatan`, `tgl_input`, `id_siswa`, `id_periode`, `dikonfirmasi_admin`) VALUES
(1, 88.00, 85.00, 90.00, 87.00, 87.50, 'lulus', 'Peserta menunjukkan semangat tinggi dan kedisiplinan yang baik sepanjang diklat.', '2024-02-14', 1, 1, 1),
(2, 84.00, 82.00, 85.00, 86.00, 84.25, 'lulus', 'Performa konsisten, unggul pada sesi praktik lapangan.', '2024-02-14', 2, 1, 1),
(3, 85.00, 88.00, 92.00, 90.00, 88.75, 'lulus', 'Peserta terbaik gelombang ini, sangat direkomendasikan.', '2024-07-30', 3, 2, 1),
(4, 68.00, 70.00, 72.00, 75.00, 71.25, 'tidak_lulus', 'Nilai teori di bawah standar minimum (70). Disarankan mengikuti diklat gelombang berikutnya.', '2024-07-30', 4, 2, 1),
(5, 92.00, 90.00, 88.00, 89.00, 89.75, 'lulus', 'Prestasi sangat memuaskan, nilai tertinggi selama program berjalan.', '2025-03-09', 5, 3, 1),
(6, 72.00, 75.00, 78.00, 80.00, 76.25, 'tidak_lulus', 'Nilai masih di bawah standar, perlu penguatan materi teori.', '2026-04-18', 6, 4, 0),
(7, 86.00, 88.00, 85.00, 87.00, 86.50, 'lulus', 'Performa bagus dan konsisten selama kegiatan diklat berlangsung.', '2026-04-18', 7, 4, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `informasi_diklat`
--

CREATE TABLE `informasi_diklat` (
  `id_info` int(11) NOT NULL,
  `id_periode` int(11) NOT NULL,
  `brosur_path` varchar(255) DEFAULT NULL,
  `tempat` varchar(150) NOT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  `diperbarui_pada` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `informasi_diklat`
--

INSERT INTO `informasi_diklat` (`id_info`, `id_periode`, `brosur_path`, `tempat`, `dibuat_pada`, `diperbarui_pada`) VALUES
(1, 1, 'brosur_1_diklat2024gel1.jpg', 'Pusdiklat Polda DIY, Jl. Ringroad Utara No. 1, Yogyakarta', '2026-04-21 10:30:07', '2023-12-20 03:00:00'),
(2, 2, 'brosur_2_diklat2024gel2.jpg', 'Pusdiklat Polda DIY, Jl. Ringroad Utara No. 1, Yogyakarta', '2026-04-21 10:30:07', '2024-06-01 02:30:00'),
(3, 3, 'brosur_3_diklat2025gel1.jpg', 'Pusdiklat Polda DIY, Jl. Ringroad Utara No. 1, Yogyakarta', '2026-04-21 10:30:07', '2025-01-10 04:00:00'),
(4, 4, 'brosur_4_diklat2026gel1.jpg', 'Pusdiklat Polda DIY, Jl. Ringroad Utara No. 1, Yogyakarta', '2026-04-21 10:30:07', '2026-03-15 01:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_diklat`
--

CREATE TABLE `jadwal_diklat` (
  `id_jadwal` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `kegiatan` varchar(150) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `id_periode` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jadwal_diklat`
--

INSERT INTO `jadwal_diklat` (`id_jadwal`, `tanggal`, `kegiatan`, `keterangan`, `id_periode`) VALUES
(1, '2024-01-15', 'Pembukaan & Orientasi Peserta', 'Upacara pembukaan oleh Kapolda DIY, pengenalan lingkungan diklat', 1),
(2, '2024-01-22', 'Materi Hukum & Etika Keamanan', 'Hukum Ketenagakerjaan, Etika Profesi Satpam, UU No. 2 Tahun 2002', 1),
(3, '2024-02-05', 'Latihan Fisik & Bela Diri Dasar', 'Latihan fisik intensif, teknik dasar bela diri, dan baris berbaris', 1),
(4, '2024-02-14', 'Ujian Akhir & Penutupan', 'Ujian tulis dan praktik lapangan, upacara penutupan dan penyerahan sertifikat', 1),
(5, '2024-07-01', 'Pembukaan Diklat Gelombang II', 'Upacara pembukaan, perkenalan instruktur dan tata tertib diklat', 2),
(6, '2024-07-10', 'Materi Teori Keamanan Gedung', 'Prosedur pengamanan gedung, penanganan tamu, dan penanganan keadaan darurat', 2),
(7, '2024-07-20', 'Praktik Lapangan & Simulasi', 'Simulasi pengamanan objek vital, penggunaan CCTV dan peralatan keamanan', 2),
(8, '2024-07-30', 'Evaluasi Akhir & Penutupan', 'Ujian komprehensif, penilaian akhir, dan upacara penutupan resmi', 2),
(9, '2025-02-10', 'Pembukaan & Orientasi Diklat 2025', 'Upacara pembukaan, pengenalan program terbaru, dan pembagian atribut', 3),
(10, '2025-02-18', 'Materi Keamanan & Keselamatan Kerja', 'K3, manajemen risiko, prosedur darurat kebakaran dan bencana', 3),
(11, '2025-03-01', 'Praktik Patroli & Penjagaan Pos', 'Teknik patroli area, penjagaan pos, dan pembuatan laporan harian', 3),
(12, '2025-03-09', 'Ujian Akhir Komprehensif', 'Ujian tulis, praktik lapangan, dan sidang penilaian bersama polda', 3),
(13, '2026-04-01', 'Pembukaan & Orientasi Peserta 2026', 'Upacara pembukaan resmi oleh Kepala Polda DIY', 4),
(14, '2026-04-10', 'Materi Dasar Ilmu Keamanan', 'Pengantar ilmu keamanan, peraturan perundang-undangan satpam terbaru', 4),
(15, '2026-04-18', 'Latihan Fisik & PBB', 'Baris berbaris, latihan fisik terprogram, dan simulasi pengamanan', 4),
(16, '2026-04-25', 'Materi Hukum & Evaluasi Tengah', 'Hukum acara pidana, hak dan kewajiban satpam, evaluasi kemajuan belajar', 4);

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan`
--

CREATE TABLE `laporan` (
  `id_laporan` int(11) NOT NULL,
  `tgl_generate` datetime NOT NULL,
  `dikonfirmasi_kepala` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = belum, 1 = sudah dikonfirmasi kepala keamanan',
  `tgl_konfirmasi_kepala` datetime DEFAULT NULL,
  `dilihat_ceo` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = belum dilihat CEO, 1 = sudah dilihat',
  `tgl_dilihat_ceo` datetime DEFAULT NULL,
  `id_periode` int(11) DEFAULT NULL,
  `file_laporan_polda` varchar(255) DEFAULT NULL COMMENT 'File laporan kegiatan dari Polda',
  `file_laporan_penilaian` varchar(255) DEFAULT NULL COMMENT 'File laporan penilaian dari admin',
  `file_surat_pernyataan` varchar(255) DEFAULT NULL COMMENT 'Surat pernyataan selesai dari kepala keamanan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laporan`
--

INSERT INTO `laporan` (`id_laporan`, `tgl_generate`, `dikonfirmasi_kepala`, `tgl_konfirmasi_kepala`, `dilihat_ceo`, `tgl_dilihat_ceo`, `id_periode`, `file_laporan_polda`, `file_laporan_penilaian`, `file_surat_pernyataan`) VALUES
(1, '2024-02-14 20:00:00', 1, '2024-02-16 09:00:00', 1, '2024-02-17 14:00:00', 1, NULL, 'confirmed', 'uploads/surat_pernyataan/surat_pernyataan_periode_1.pdf'),
(2, '2024-07-30 20:00:00', 1, '2024-08-01 10:00:00', 1, '2024-08-02 11:00:00', 2, NULL, 'confirmed', 'uploads/surat_pernyataan/surat_pernyataan_periode_2.pdf'),
(3, '2025-03-09 19:00:00', 1, '2025-03-11 08:30:00', 1, '2025-03-12 09:00:00', 3, NULL, 'confirmed', 'uploads/surat_pernyataan/surat_pernyataan_periode_3.pdf'),
(4, '2026-04-18 18:00:00', 0, NULL, 1, '2026-04-21 17:36:00', 4, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `laporan_polda`
--

CREATE TABLE `laporan_polda` (
  `id_laporan_polda` int(11) NOT NULL,
  `id_periode` int(11) NOT NULL,
  `id_akun_polda` int(11) NOT NULL,
  `pengeluaran` bigint(20) NOT NULL DEFAULT 0,
  `pemasukan` bigint(20) NOT NULL DEFAULT 0,
  `deskripsi_kegiatan` text DEFAULT NULL,
  `file_laporan` varchar(255) DEFAULT NULL COMMENT 'Upload file PDF laporan dari Polda',
  `tgl_input` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `laporan_polda`
--

INSERT INTO `laporan_polda` (`id_laporan_polda`, `id_periode`, `id_akun_polda`, `pengeluaran`, `pemasukan`, `deskripsi_kegiatan`, `file_laporan`, `tgl_input`) VALUES
(1, 1, 4, 18500000, 20000000, 'Pelaksanaan Diklat Satpam Gel. I 2024 berjalan lancar. Total 2 peserta, keduanya lulus dengan nilai memuaskan. Kegiatan meliputi materi teori hukum, latihan fisik, dan praktik lapangan.', 'uploads/laporan_polda/lpj_periode_1.pdf', '2026-04-21 17:29:49'),
(2, 2, 4, 19200000, 20000000, 'Pelaksanaan Diklat Satpam Gel. II 2024 selesai dengan 2 peserta. 1 peserta lulus dan 1 tidak memenuhi nilai minimum. Total kegiatan 4 pertemuan selama 31 hari.', 'uploads/laporan_polda/lpj_periode_2.pdf', '2026-04-21 17:29:49'),
(3, 3, 4, 21000000, 22000000, 'Diklat Satpam Gel. I 2025 berhasil dilaksanakan dengan 1 peserta yang dinyatakan lulus nilai tertinggi program. Surplus anggaran dialokasikan untuk peningkatan fasilitas diklat.', 'uploads/laporan_polda/lpj_periode_3.pdf', '2026-04-21 17:29:49'),
(4, 4, 4, 15000000, 24000000, 'Laporan sementara Diklat Satpam Gel. I 2026. Program masih berjalan per tanggal laporan. 2 peserta aktif mengikuti seluruh rangkaian kegiatan. Pemasukan dari biaya pendaftaran sudah diterima penuh.', NULL, '2026-04-21 17:29:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `jenis` enum('revisi','terverifikasi','hasil_nilai') NOT NULL,
  `pesan` text NOT NULL,
  `status_kirim` enum('terkirim','gagal') NOT NULL DEFAULT 'terkirim',
  `tgl_kirim` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifikasi`
--

INSERT INTO `notifikasi` (`id_notifikasi`, `id_siswa`, `jenis`, `pesan`, `status_kirim`, `tgl_kirim`) VALUES
(1, 1, 'revisi', 'Dokumen Anda memerlukan revisi:\nKTP: Foto kurang jelas, mohon upload ulang dengan pencahayaan cukup\n\nBatas waktu revisi: 2024-01-13\n\nSilakan login dan upload ulang dokumen sebelum batas waktu.', 'terkirim', '2024-01-11 14:00:00'),
(2, 1, '', 'Selamat! Anda dinyatakan LULUS program Diklat Satpam Gelombang I Tahun 2024. Sertifikat dapat diambil di kantor Pusdiklat mulai 17 Februari 2024.', 'terkirim', '2024-02-15 10:00:00'),
(3, 2, '', 'Selamat! Anda dinyatakan LULUS program Diklat Satpam Gelombang I Tahun 2024. Harap segera menghubungi panitia untuk pengambilan sertifikat.', 'terkirim', '2024-02-15 10:05:00'),
(4, 3, '', 'Selamat! Anda dinyatakan LULUS program Diklat Satpam Gelombang II Tahun 2024 dengan predikat terbaik.', 'terkirim', '2024-07-31 11:00:00'),
(5, 4, '', 'Anda dinyatakan TIDAK LULUS program Diklat Satpam Gelombang II Tahun 2024. Silakan hubungi admin untuk informasi pendaftaran diklat ulang.', 'terkirim', '2024-07-31 11:05:00'),
(6, 5, '', 'Selamat! Anda dinyatakan LULUS program Diklat Satpam Gelombang I Tahun 2025 dengan nilai tertinggi program.', 'terkirim', '2025-03-10 09:00:00'),
(7, 6, 'revisi', 'Dokumen Anda memerlukan revisi:\nSKCK: Masa berlaku sudah habis, mohon perbarui di Polsek setempat\n\nBatas waktu revisi: 2026-03-27\n\nSilakan login dan upload ulang dokumen sebelum batas waktu.', 'terkirim', '2026-03-22 13:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `periode_diklat`
--

CREATE TABLE `periode_diklat` (
  `id_periode` int(11) NOT NULL,
  `tahun` year(4) NOT NULL,
  `gelombang` tinyint(4) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `biaya` int(11) NOT NULL,
  `lokasi_spesifik` varchar(150) NOT NULL,
  `lokasi_fasilitas` varchar(200) DEFAULT NULL COMMENT 'Lokasi pengambilan fasilitas diklat',
  `fasilitas` text NOT NULL,
  `info_kebutuhan` text DEFAULT NULL COMMENT 'Informasi barang/kebutuhan yang perlu dibawa peserta',
  `batas_verifikasi` date DEFAULT NULL COMMENT 'Batas waktu verifikasi admin — setelah ini status otomatis jadi peserta',
  `status` enum('pendaftaran','berjalan','selesai') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `periode_diklat`
--

INSERT INTO `periode_diklat` (`id_periode`, `tahun`, `gelombang`, `tanggal_mulai`, `tanggal_selesai`, `biaya`, `lokasi_spesifik`, `lokasi_fasilitas`, `fasilitas`, `info_kebutuhan`, `batas_verifikasi`, `status`) VALUES
(1, '2024', 1, '2024-01-15', '2024-02-15', 2500000, 'Pusdiklat Polda DIY, Jl. Ringroad Utara No. 1, Yogyakarta', 'Gudang Logistik Lt. 1, Gedung A Pusdiklat', 'Seragam PDH 2 stel, Modul Diklat, Konsumsi 2x sehari', 'Pakaian olahraga 2 stel, Sepatu PDH hitam, Perlengkapan mandi', '2024-01-12', 'selesai'),
(2, '2024', 2, '2024-07-01', '2024-07-31', 2500000, 'Pusdiklat Polda DIY, Jl. Ringroad Utara No. 1, Yogyakarta', 'Gudang Logistik Lt. 1, Gedung A Pusdiklat', 'Seragam PDH 2 stel, Modul Diklat, Konsumsi 2x sehari', 'Pakaian olahraga 2 stel, Sepatu PDH hitam, Perlengkapan mandi', '2024-06-28', 'selesai'),
(3, '2025', 1, '2025-02-10', '2025-03-10', 2750000, 'Pusdiklat Polda DIY, Jl. Ringroad Utara No. 1, Yogyakarta', 'Gudang Logistik Lt. 2, Gedung B Pusdiklat', 'Seragam PDH 2 stel, Modul Diklat Edisi Revisi, Konsumsi 2x sehari', 'Pakaian olahraga 2 stel, Sepatu PDH hitam, Perlengkapan mandi, Buku catatan', '2025-02-07', 'selesai'),
(4, '2026', 1, '2026-04-01', '2026-04-30', 3000000, 'Pusdiklat Polda DIY, Jl. Ringroad Utara No. 1, Yogyakarta', 'Gudang Logistik Lt. 1, Gedung A Pusdiklat', 'Seragam PDH 2 stel, Modul Diklat 2026, Konsumsi 2x sehari, Tas ransel', 'Pakaian olahraga 2 stel, Sepatu PDH hitam, Perlengkapan mandi', '2026-03-28', 'berjalan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peserta_periode`
--

CREATE TABLE `peserta_periode` (
  `id_peserta_periode` int(11) NOT NULL,
  `tanggal_terima` date NOT NULL,
  `id_peserta` int(11) DEFAULT NULL,
  `id_periode` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `peserta_periode`
--

INSERT INTO `peserta_periode` (`id_peserta_periode`, `tanggal_terima`, `id_peserta`, `id_periode`, `created_at`) VALUES
(1, '2024-01-14', 1, 1, '2024-01-14 03:00:00'),
(2, '2024-01-14', 2, 1, '2024-01-14 03:30:00'),
(3, '2024-06-30', 3, 2, '2024-06-30 02:00:00'),
(4, '2024-06-30', 4, 2, '2024-06-30 02:30:00'),
(5, '2025-02-09', 5, 3, '2025-02-09 01:00:00'),
(6, '2026-03-29', 6, 4, '2026-03-29 03:00:00'),
(7, '2026-03-29', 7, 4, '2026-03-29 03:30:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id_peserta` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `alamat` text NOT NULL,
  `no_telp` varchar(15) NOT NULL,
  `email` varchar(100) NOT NULL,
  `tgl_lahir` date NOT NULL,
  `agama` varchar(20) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `status` enum('calon','terverifikasi','peserta','lulus','tidak_lulus') DEFAULT NULL,
  `batas_revisi` date DEFAULT NULL,
  `id_akun` int(11) DEFAULT NULL,
  `tinggi_badan` smallint(6) NOT NULL,
  `berat_badan` smallint(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id_peserta`, `nama`, `alamat`, `no_telp`, `email`, `tgl_lahir`, `agama`, `jenis_kelamin`, `status`, `batas_revisi`, `id_akun`, `tinggi_badan`, `berat_badan`, `created_at`) VALUES
(1, 'Budi Santoso', 'Jl. Magelang No. 45, Mlati, Sleman, DIY', '081234567891', 'budi.santoso@email.com', '1998-05-14', 'Islam', 'L', 'lulus', NULL, 7, 172, 68, '2024-01-10 00:30:00'),
(2, 'Agus Wijaya', 'Jl. Palagan Tentara Pelajar No. 12, Sleman, DIY', '081234567892', 'agus.wijaya@email.com', '1999-08-22', 'Islam', 'L', 'lulus', NULL, 8, 168, 65, '2024-01-10 01:15:00'),
(3, 'Siti Rahayu', 'Jl. Godean KM 5, Gamping, Sleman, DIY', '081234567893', 'siti.rahayu@email.com', '2000-03-17', 'Islam', 'P', 'lulus', NULL, 9, 158, 52, '2024-06-12 00:45:00'),
(4, 'Hendra Purnomo', 'Jl. Wates KM 8, Kulon Progo, DIY', '081234567894', 'hendra.purnomo@email.com', '1997-11-30', 'Islam', 'L', 'tidak_lulus', NULL, 10, 170, 72, '2024-06-12 01:30:00'),
(5, 'Eko Prasetyo', 'Jl. Bantul No. 33, Bantul, DIY', '081234567895', 'eko.prasetyo@email.com', '2001-07-08', 'Islam', 'L', 'lulus', NULL, 11, 175, 70, '2025-01-18 02:00:00'),
(6, 'Dewi Kusuma', 'Jl. Solo KM 10, Kalasan, Sleman, DIY', '081234567896', 'dewi.kusuma@email.com', '2002-12-25', 'Kristen', 'P', 'peserta', NULL, 12, 160, 55, '2026-03-20 01:00:00'),
(7, 'Rizki Firmansyah', 'Jl. Wonosari No. 17, Gunungkidul, DIY', '081234567897', 'rizki.firmansyah@email.com', '2000-09-03', 'Islam', 'L', 'peserta', NULL, 13, 178, 75, '2026-03-21 02:30:00');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `akun`
--
ALTER TABLE `akun`
  ADD PRIMARY KEY (`id_akun`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `dokumen_pendaftaran`
--
ALTER TABLE `dokumen_pendaftaran`
  ADD PRIMARY KEY (`id_dokumen`),
  ADD UNIQUE KEY `unique_dokumen` (`id_siswa`,`jenis`);

--
-- Indeks untuk tabel `evaluasi`
--
ALTER TABLE `evaluasi`
  ADD PRIMARY KEY (`id_evaluasi`),
  ADD UNIQUE KEY `unique_evaluasi` (`id_siswa`),
  ADD KEY `fk_evaluasi_periode` (`id_periode`);

--
-- Indeks untuk tabel `informasi_diklat`
--
ALTER TABLE `informasi_diklat`
  ADD PRIMARY KEY (`id_info`),
  ADD UNIQUE KEY `unique_info_periode` (`id_periode`);

--
-- Indeks untuk tabel `jadwal_diklat`
--
ALTER TABLE `jadwal_diklat`
  ADD PRIMARY KEY (`id_jadwal`);

--
-- Indeks untuk tabel `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id_laporan`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`);

--
-- Indeks untuk tabel `periode_diklat`
--
ALTER TABLE `periode_diklat`
  ADD PRIMARY KEY (`id_periode`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_peserta`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `akun`
--
ALTER TABLE `akun`
  MODIFY `id_akun` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `dokumen_pendaftaran`
--
ALTER TABLE `dokumen_pendaftaran`
  MODIFY `id_dokumen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `evaluasi`
--
ALTER TABLE `evaluasi`
  MODIFY `id_evaluasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `jadwal_diklat`
--
ALTER TABLE `jadwal_diklat`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `periode_diklat`
--
ALTER TABLE `periode_diklat`
  MODIFY `id_periode` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_peserta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
