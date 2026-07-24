-- ============================================================
-- UPDATE MIGRATION SAW untuk BLT
-- Update kriteria, sub kriteria, dan data alternatif sesuai requirement
-- ============================================================

USE beelte;

-- ============================================================
-- 1. HAPUS DATA KRITERIA LAMA DAN INSERT DATA BARU
-- ============================================================
DELETE FROM saw_kriteria;

INSERT INTO saw_kriteria (kode_kriteria, nama_kriteria, bobot, jenis, keterangan, urutan, nilai_min, nilai_max, is_active) VALUES
('C1', 'Kehilangan Mata Pencaharian', 0.3000, 'Benefit', 'Semakin tinggi nilai (5=PHK total, 1=Tidak kehilangan)', 1, 1, 5, 1),
('C2', 'Anggota Keluarga Sakit/Kronis/Disabilitas', 0.2500, 'Benefit', 'Semakin tinggi nilai (5=≥2 anggota sakit, 1=Tidak ada)', 2, 1, 5, 1),
('C3', 'Status Penerimaan PKH', 0.2000, 'Cost', 'Cost: 5=Menerima PKH, 1=Tidak menerima PKH', 3, 1, 5, 1),
('C4', 'Lansia dalam Keluarga', 0.1500, 'Benefit', 'Semakin tinggi nilai (5=Lansia tunggal >70th, 1=Tidak ada)', 4, 1, 5, 1),
('C5', 'Status Kepala Keluarga', 0.1000, 'Benefit', 'Semakin tinggi nilai (5=Perempuan KK tanpa penghasilan, 1=KK laki-laki)', 5, 1, 5, 1);

-- ============================================================
-- 2. BUAT TABEL SUB-KRITERIA (jika belum ada)
-- ============================================================
CREATE TABLE IF NOT EXISTS saw_sub_kriteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kriteria_id INT NOT NULL,
    kode_sub VARCHAR(10) NOT NULL,
    deskripsi TEXT NOT NULL,
    nilai_sub INT NOT NULL DEFAULT 1 CHECK (nilai_sub >= 1 AND nilai_sub <= 5),
    urutan INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_kriteria_nilai (kriteria_id, nilai_sub),
    FOREIGN KEY (kriteria_id) REFERENCES saw_kriteria(id) ON DELETE CASCADE,
    KEY idx_kriteria_id (kriteria_id),
    KEY idx_nilai_sub (nilai_sub)
) ENGINE=InnoDB;

-- ============================================================
-- 3. INSERT DATA SUB-KRITERIA
-- ============================================================

-- Sub-kriteria C1: Kehilangan Mata Pencaharian
INSERT INTO saw_sub_kriteria (kriteria_id, kode_sub, deskripsi, nilai_sub, urutan) 
SELECT id, 'C1.5', 'Kehilangan pekerjaan total (PHK/usaha tutup)', 5, 1 FROM saw_kriteria WHERE kode_kriteria = 'C1'
UNION ALL
SELECT id, 'C1.4', 'Penghasilan menurun > 50%', 4, 2 FROM saw_kriteria WHERE kode_kriteria = 'C1'
UNION ALL
SELECT id, 'C1.3', 'Penghasilan menurun 25–50%', 3, 3 FROM saw_kriteria WHERE kode_kriteria = 'C1'
UNION ALL
SELECT id, 'C1.2', 'Penghasilan menurun < 25%', 2, 4 FROM saw_kriteria WHERE kode_kriteria = 'C1'
UNION ALL
SELECT id, 'C1.1', 'Tidak kehilangan mata pencaharian', 1, 5 FROM saw_kriteria WHERE kode_kriteria = 'C1';

-- Sub-kriteria C2: Anggota Keluarga Sakit/Kronis/Disabilitas
INSERT INTO saw_sub_kriteria (kriteria_id, kode_sub, deskripsi, nilai_sub, urutan) 
SELECT id, 'C2.5', '≥ 2 anggota sakit kronis/disabilitas', 5, 1 FROM saw_kriteria WHERE kode_kriteria = 'C2'
UNION ALL
SELECT id, 'C2.4', '1 anggota sakit kronis berat/disabilitas', 4, 2 FROM saw_kriteria WHERE kode_kriteria = 'C2'
UNION ALL
SELECT id, 'C2.3', '1 anggota sakit menahun sedang', 3, 3 FROM saw_kriteria WHERE kode_kriteria = 'C2'
UNION ALL
SELECT id, 'C2.2', 'Sering sakit ringan', 2, 4 FROM saw_kriteria WHERE kode_kriteria = 'C2'
UNION ALL
SELECT id, 'C2.1', 'Tidak ada anggota sakit/kronis', 1, 5 FROM saw_kriteria WHERE kode_kriteria = 'C2';

-- Sub-kriteria C3: Status Penerimaan PKH
INSERT INTO saw_sub_kriteria (kriteria_id, kode_sub, deskripsi, nilai_sub, urutan) 
SELECT id, 'C3.5', 'Menerima PKH', 5, 1 FROM saw_kriteria WHERE kode_kriteria = 'C3'
UNION ALL
SELECT id, 'C3.1', 'Tidak menerima PKH', 1, 2 FROM saw_kriteria WHERE kode_kriteria = 'C3';

-- Sub-kriteria C4: Lansia dalam Keluarga
INSERT INTO saw_sub_kriteria (kriteria_id, kode_sub, deskripsi, nilai_sub, urutan) 
SELECT id, 'C4.5', 'Lansia tunggal > 70 tahun', 5, 1 FROM saw_kriteria WHERE kode_kriteria = 'C4'
UNION ALL
SELECT id, 'C4.4', 'Lansia tunggal 60–70 tahun', 4, 2 FROM saw_kriteria WHERE kode_kriteria = 'C4'
UNION ALL
SELECT id, 'C4.3', 'Lansia tinggal dengan keluarga tidak produktif', 3, 3 FROM saw_kriteria WHERE kode_kriteria = 'C4'
UNION ALL
SELECT id, 'C4.2', 'Lansia tinggal dengan keluarga produktif', 2, 4 FROM saw_kriteria WHERE kode_kriteria = 'C4'
UNION ALL
SELECT id, 'C4.1', 'Tidak ada lansia', 1, 5 FROM saw_kriteria WHERE kode_kriteria = 'C4';

-- Sub-kriteria C5: Status Kepala Keluarga
INSERT INTO saw_sub_kriteria (kriteria_id, kode_sub, deskripsi, nilai_sub, urutan) 
SELECT id, 'C5.5', 'Perempuan kepala keluarga tanpa penghasilan tetap', 5, 1 FROM saw_kriteria WHERE kode_kriteria = 'C5'
UNION ALL
SELECT id, 'C5.4', 'Perempuan kepala keluarga penghasilan tidak tetap', 4, 2 FROM saw_kriteria WHERE kode_kriteria = 'C5'
UNION ALL
SELECT id, 'C5.3', 'Perempuan kepala keluarga penghasilan tetap rendah', 3, 3 FROM saw_kriteria WHERE kode_kriteria = 'C5'
UNION ALL
SELECT id, 'C5.2', 'Perempuan kepala keluarga penghasilan cukup', 2, 4 FROM saw_kriteria WHERE kode_kriteria = 'C5'
UNION ALL
SELECT id, 'C5.1', 'Kepala keluarga laki-laki', 1, 5 FROM saw_kriteria WHERE kode_kriteria = 'C5';

-- ============================================================
-- 4. GANTI ROLE ASISTEN MENJADI ATASAN
-- ============================================================
ALTER TABLE users MODIFY role ENUM('admin', 'atasan', 'sekretaris') NOT NULL DEFAULT 'sekretaris';

UPDATE users SET role = 'atasan' WHERE role = 'asisten';

UPDATE users SET 
    nama = 'Atasan Desa',
    username = 'atasan',
    email = 'atasan@blt.go.id'
WHERE role = 'atasan' AND username = 'asisten';

-- ============================================================
-- 5. UPDATE DATA ALTERNATIF/PESERTA BARU
-- ============================================================
DELETE FROM peserta;

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

-- ============================================================
-- 6. CLEAR DATA PENILAIAN LAMA (karena kriteria berubah)
-- ============================================================
DELETE FROM saw_penilaian;
DELETE FROM saw_hasil;
DELETE FROM saw_periode;
DELETE FROM saw_periode_kriteria;

-- ============================================================
-- Verifikasi: Check total bobot kriteria
-- ============================================================
SELECT 
    COUNT(*) as total_kriteria,
    ROUND(SUM(bobot),4) as total_bobot
FROM saw_kriteria
WHERE is_active = 1;

-- Hasil: total_kriteria = 5, total_bobot = 1.0000
