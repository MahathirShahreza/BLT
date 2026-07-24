-- ============================================================
-- MIGRATION: Update BLT Database from Pariaman to Pulau Tamang
-- ============================================================
-- Purpose: Update existing database untuk Pulau Tamang, Mandailing Natal
-- Date: 2026-03-30
-- WARNING: Jalankan dengan hati-hati, backup database terlebih dahulu!

-- Step 1: Hapus peserta lama yang masih terkait Pariaman
-- (Opsional - uncomment jika ingin reset data peserta)
-- DELETE FROM peserta WHERE kelurahan_id IN (SELECT id FROM kelurahan WHERE kabupaten = 'Pariaman');

-- Step 2: Hapus kelurahan Pariaman lama
DELETE FROM kelurahan WHERE kabupaten = 'Pariaman';

-- Step 3: Insert kelurahan Pulau Tamang yang baru
INSERT INTO kelurahan (nama_kelurahan, kecamatan, kabupaten, provinsi) VALUES
('Pulau Tamang', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Bukit Raya', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Tanjung Lasa', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Aek Nabara', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Sungai Batang', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Tambak Rejo', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara');

-- Step 4: Update peserta yang zonanya NULL ke kelurahan pertama (Pulau Tamang)
UPDATE peserta SET kelurahan_id = 1 WHERE kelurahan_id IS NULL OR kelurahan_id NOT IN (SELECT id FROM kelurahan);

-- Step 5: Verifikasi hasil
SELECT 'Kelurahan yang ada sekarang:' as info;
SELECT id, nama_kelurahan, kecamatan, kabupaten FROM kelurahan ORDER BY id;

SELECT 'Peserta per kelurahan:' as info;
SELECT k.nama_kelurahan, COUNT(p.id) as jumlah 
FROM peserta p 
LEFT JOIN kelurahan k ON p.kelurahan_id = k.id 
GROUP BY k.id, k.nama_kelurahan;
