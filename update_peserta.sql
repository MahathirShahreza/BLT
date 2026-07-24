-- ============================================================
-- UPDATE DATA PESERTA (ALTERNATIF) SESUAI KEBUTUHAN BLT
-- Data ini akan digunakan untuk penilaian SAW
-- ============================================================

USE beelte;

-- Hapus peserta lama
DELETE FROM peserta;
DELETE FROM distribusi;

-- Insert peserta baru sesuai list yang diberikan
INSERT INTO peserta (nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, pendidikan, pekerjaan, alamat, rt, rw, kelurahan_id, no_telepon, status_verifikasi, ditambahkan_oleh) VALUES
('1375010101000001', 'Misdewita', 'Pariaman', '1990-05-15', 'P', 'Islam', 'SMA', 'Ibu Rumah Tangga', 'Jl. Merdeka No. 1', '001', '001', 1, '081234567801', 'disetujui', 1),
('1375010202000002', 'Sahnun', 'Pariaman', '1985-08-20', 'L', 'Islam', 'SMP', 'Buruh Tani', 'Jl. Pahlawan No. 2', '002', '001', 1, '081234567802', 'disetujui', 1),
('1375010303000003', 'Yudirsyah', 'Pariaman', '1988-03-10', 'L', 'Islam', 'SD', 'Nelayan', 'Jl. Sudirman No. 3', '003', '002', 2, '081234567803', 'disetujui', 1),
('1375010404000004', 'Samsiar Syam', 'Pariaman', '1992-11-25', 'P', 'Islam', 'SMA', 'Ibu Rumah Tangga', 'Jl. Diponegoro No. 4', '001', '002', 2, '081234567804', 'disetujui', 1),
('1375010505000005', 'Roslaini', 'Pariaman', '1987-06-30', 'P', 'Islam', 'SMP', 'Pedagang Kecil', 'Jl. A. Yani No. 5', '002', '003', 3, '081234567805', 'disetujui', 1),
('1375010606000006', 'Rismawati', 'Pariaman', '1991-09-12', 'P', 'Islam', 'SMA', 'Ibu Rumah Tangga', 'Jl. Gatot Subroto No. 6', '003', '003', 3, '081234567806', 'disetujui', 1),
('1375010707000007', 'Rorsmani', 'Pariaman', '1986-02-17', 'L', 'Islam', 'SMP', 'Buruh Harian', 'Jl. Kartini No. 7', '001', '004', 4, '081234567807', 'disetujui', 1),
('1375010808000008', 'Rosdiana', 'Pariaman', '1989-12-28', 'P', 'Islam', 'SD', 'Ibu Rumah Tangga', 'Jl. Basuki No. 8', '002', '004', 4, '081234567808', 'disetujui', 1),
('1375010909000009', 'Ani Marsiti', 'Pariaman', '1984-04-05', 'P', 'Islam', 'SMA', 'Wiraswasta', 'Jl. Jenderal No. 9', '003', '005', 5, '081234567809', 'disetujui', 1),
('1375011010000010', 'Derita Nur', 'Pariaman', '1993-07-22', 'P', 'Islam', 'SMP', 'Pedagang', 'Jl. Moh. Hatta No. 10', '001', '005', 5, '081234567810', 'disetujui', 1);

-- Clear data penilaian lama karena kriteria berubah
DELETE FROM saw_penilaian;
DELETE FROM saw_hasil;

-- Verify data inserted
SELECT 'Peserta/Alternatif Data:' as Info;
SELECT 
    id, 
    'A' + CAST(LPAD(id,2,'0') AS CHAR) as kode_alternatif,
    nama_lengkap,
    pendidikan,
    pekerjaan,
    status_verifikasi
FROM peserta
ORDER BY id;
