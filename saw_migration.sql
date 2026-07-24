-- ============================================================
-- MIGRASI SAW (Simple Additive Weighting) untuk Sistem BLT
-- Versi: 2.0 (Improves structure & optimization)
-- Jalankan file ini SETELAH database.sql
-- ============================================================

USE beelte;

-- ============================================================
-- TABEL KRITERIA SAW
-- ============================================================
CREATE TABLE IF NOT EXISTS saw_kriteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_kriteria VARCHAR(10) NOT NULL UNIQUE,  -- C1, C2, C3, ...
    nama_kriteria VARCHAR(150) NOT NULL,
    bobot DECIMAL(5,4) NOT NULL DEFAULT 0.0000, -- total semua bobot harus = 1
    jenis ENUM('Benefit','Cost') NOT NULL DEFAULT 'Benefit',
    keterangan TEXT,
    urutan INT DEFAULT 0,
    nilai_min DECIMAL(10,2) DEFAULT 0,
    nilai_max DECIMAL(10,2) DEFAULT 100,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_kode_kriteria (kode_kriteria),
    KEY idx_jenis (jenis),
    KEY idx_urutan (urutan),
    KEY idx_is_active (is_active)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL PENILAIAN SAW (nilai per peserta per kriteria)
-- ============================================================
CREATE TABLE IF NOT EXISTS saw_penilaian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT NOT NULL,
    kriteria_id INT NOT NULL,
    nilai DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (nilai >= 0),
    dinilai_oleh INT,   -- user atasan yang menginput
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_peserta_kriteria (peserta_id, kriteria_id),
    FOREIGN KEY (peserta_id) REFERENCES peserta(id) ON DELETE CASCADE,
    FOREIGN KEY (kriteria_id) REFERENCES saw_kriteria(id) ON DELETE CASCADE,
    FOREIGN KEY (dinilai_oleh) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_peserta_id (peserta_id),
    KEY idx_kriteria_id (kriteria_id),
    KEY idx_dinilai_oleh (dinilai_oleh),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL HASIL SAW (disimpan agar transparan & bisa direkap)
-- ============================================================
CREATE TABLE IF NOT EXISTS saw_hasil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT NOT NULL,
    nilai_preferensi DECIMAL(10,6) NOT NULL DEFAULT 0 CHECK (nilai_preferensi >= 0 AND nilai_preferensi <= 1),
    peringkat INT DEFAULT 0 CHECK (peringkat >= 0),
    status_rekomendasi ENUM('Direkomendasikan','Tidak Direkomendasikan') DEFAULT 'Tidak Direkomendasikan',
    dihitung_oleh INT,
    catatan_hasil TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_peserta_hasil (peserta_id),
    FOREIGN KEY (peserta_id) REFERENCES peserta(id) ON DELETE CASCADE,
    FOREIGN KEY (dihitung_oleh) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_peringkat (peringkat),
    KEY idx_status_rekomendasi (status_rekomendasi),
    KEY idx_nilai_preferensi (nilai_preferensi),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL PERIODE SAW (Linking kriteria dengan periode bantuan)
-- ============================================================
CREATE TABLE IF NOT EXISTS saw_periode (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periode_id INT NOT NULL,
    total_anggaran_saw DECIMAL(20,2) DEFAULT 0 CHECK (total_anggaran_saw >= 0),
    jumlah_penerima_rekomendasi INT DEFAULT 0 CHECK (jumlah_penerima_rekomendasi >= 0),
    status_kalkulasi ENUM('draft', 'proses', 'selesai') DEFAULT 'draft',
    dihitung_oleh INT,
    tanggal_kalkulasi DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_periode (periode_id),
    FOREIGN KEY (periode_id) REFERENCES periode_bantuan(id) ON DELETE CASCADE,
    FOREIGN KEY (dihitung_oleh) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_status_kalkulasi (status_kalkulasi)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL KRITERIA PER PERIODE (Many-to-Many)
-- ============================================================
CREATE TABLE IF NOT EXISTS saw_periode_kriteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periode_id INT NOT NULL,
    kriteria_id INT NOT NULL,
    bobot_periode DECIMAL(5,4) NOT NULL,
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_periode_kriteria (periode_id, kriteria_id),
    FOREIGN KEY (periode_id) REFERENCES periode_bantuan(id) ON DELETE CASCADE,
    FOREIGN KEY (kriteria_id) REFERENCES saw_kriteria(id) ON DELETE CASCADE,
    KEY idx_periode_id (periode_id),
    KEY idx_kriteria_id (kriteria_id)
) ENGINE=InnoDB;

-- ============================================================
-- DATA AWAL KRITERIA SAW (bisa diubah lewat halaman admin)
-- ============================================================
INSERT INTO saw_kriteria (kode_kriteria, nama_kriteria, bobot, jenis, keterangan, urutan, nilai_min, nilai_max) VALUES
('C1', 'Penghasilan Bulanan',        0.3000, 'Cost',    'Semakin rendah penghasilan semakin layak (Cost)', 1, 0, 5000000),
('C2', 'Jumlah Anggota Keluarga',    0.2000, 'Benefit', 'Semakin banyak anggota keluarga semakin layak (Benefit)', 2, 1, 10),
('C3', 'Kondisi Rumah',              0.2000, 'Cost',    '1=Layak, 2=Kurang Layak, 3=Tidak Layak (Cost)', 3, 1, 3),
('C4', 'Kategori Kemiskinan',        0.2000, 'Benefit', '1=Hampir Miskin, 2=Miskin, 3=Sangat Miskin (Benefit)', 4, 1, 3),
('C5', 'Tanggungan Pendidikan Anak', 0.1000, 'Benefit', 'Jumlah anak usia sekolah (Benefit)', 5, 0, 8)
ON DUPLICATE KEY UPDATE nama_kriteria=VALUES(nama_kriteria);
