-- ============================================================
-- UPDATE PASSWORD AKUN DUMMY → Plain Text
-- Jalankan di phpMyAdmin → tab SQL → klik Go
-- ============================================================
-- Password semua akun dummy diubah menjadi: Gemilang1
-- Format: huruf besar + huruf kecil + angka (sesuai sistem baru)
-- ============================================================

UPDATE akun SET password = 'Gemilang1' WHERE username IN (
    'siswa01',
    'admin01',
    'publikasi01',
    'polda01',
    'kepala01',
    'ceo01'
);

-- Verifikasi:
SELECT id_akun, username, role, password FROM akun;