-- ============================================================
-- MIGRATION: Update Kelurahan Data to Pulau Tamang
-- ============================================================
-- Purpose: Replace Pariaman with Pulau Tamang, Mandailing Natal data
-- Date: 2026-03-30

-- Delete old Pariaman data
DELETE FROM kelurahan WHERE kabupaten = 'Pariaman';

-- Insert new Pulau Tamang data
INSERT INTO kelurahan (nama_kelurahan, kecamatan, kabupaten, provinsi) VALUES
('Pulau Tamang', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Bukit Raya', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Tanjung Lasa', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Aek Nabara', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Sungai Batang', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Tambak Rejo', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara');

-- Update reference field in database.sql for default region
UPDATE kelurahan SET kabupaten = 'Mandailing Natal', provinsi = 'Sumatera Utara' WHERE kabupaten = 'Pariaman';
