-- ============================================================
-- DATABASE: Sistem Pembagian Bantuan Langsung Tunai (BLT)
-- Versi: 2.0 (Perbaikan struktur & optimasi)
-- Created: 2025
-- ============================================================

CREATE DATABASE IF NOT EXISTS beelte CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE beelte;

-- ============================================================
-- TABEL USERS (Admin, Atasan, Sekretaris)
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'atasan', 'sekretaris') NOT NULL DEFAULT 'sekretaris',
    email VARCHAR(100),
    telepon VARCHAR(20),
    foto VARCHAR(255) DEFAULT NULL,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_username (username),
    KEY idx_role (role),
    KEY idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL KELURAHAN / DESA
-- ============================================================
CREATE TABLE kelurahan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelurahan VARCHAR(100) NOT NULL,
    kecamatan VARCHAR(100) NOT NULL,
    kabupaten VARCHAR(100) NOT NULL DEFAULT 'Pariaman',
    provinsi VARCHAR(100) NOT NULL DEFAULT 'Sumatera Barat',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_nama_kelurahan (nama_kelurahan),
    KEY idx_kecamatan (kecamatan),
    UNIQUE KEY unique_kelurahan (nama_kelurahan, kecamatan, kabupaten)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL PESERTA / PENERIMA BLT
-- ============================================================
CREATE TABLE peserta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(16) NOT NULL UNIQUE,
    nama_lengkap VARCHAR(150) NOT NULL,
    tempat_lahir VARCHAR(100),
    tanggal_lahir DATE,
    jenis_kelamin ENUM('L', 'P') NOT NULL,
    agama VARCHAR(30),
    pendidikan VARCHAR(50),
    pekerjaan VARCHAR(100),
    status_kawin ENUM('Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati') DEFAULT 'Belum Kawin',
    alamat TEXT NOT NULL,
    rt VARCHAR(5),
    rw VARCHAR(5),
    kelurahan_id INT,
    kode_pos VARCHAR(10),
    no_telepon VARCHAR(20),
    no_kk VARCHAR(16),
    nama_kepala_keluarga VARCHAR(150),
    jumlah_anggota_keluarga INT DEFAULT 1 CHECK (jumlah_anggota_keluarga > 0),
    jumlah_anak_usia_sekolah INT DEFAULT 0 CHECK (jumlah_anak_usia_sekolah >= 0),
    penghasilan_bulanan DECIMAL(15,2) DEFAULT 0 CHECK (penghasilan_bulanan >= 0),
    kategori_kemiskinan ENUM('Sangat Miskin', 'Miskin', 'Hampir Miskin') DEFAULT 'Miskin',
    kondisi_rumah ENUM('Tidak Layak', 'Kurang Layak', 'Layak') DEFAULT 'Kurang Layak',
    foto_ktp VARCHAR(255) DEFAULT NULL,
    foto_kk VARCHAR(255) DEFAULT NULL,
    foto_peserta VARCHAR(255) DEFAULT NULL,
    status_verifikasi ENUM('menunggu', 'disetujui', 'ditolak') DEFAULT 'menunggu',
    catatan_verifikasi TEXT DEFAULT NULL,
    diverifikasi_oleh INT DEFAULT NULL,
    tanggal_verifikasi DATETIME DEFAULT NULL,
    status_aktif ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    ditambahkan_oleh INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kelurahan_id) REFERENCES kelurahan(id) ON DELETE SET NULL,
    FOREIGN KEY (diverifikasi_oleh) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (ditambahkan_oleh) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_nik (nik),
    KEY idx_nama_lengkap (nama_lengkap),
    KEY idx_kelurahan_id (kelurahan_id),
    KEY idx_status_verifikasi (status_verifikasi),
    KEY idx_status_aktif (status_aktif),
    KEY idx_created_at (created_at),
    KEY idx_kategori_kemiskinan (kategori_kemiskinan)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL PERIODE BANTUAN
-- ============================================================
CREATE TABLE periode_bantuan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_periode VARCHAR(100) NOT NULL,
    tahun YEAR NOT NULL,
    bulan_mulai TINYINT NOT NULL CHECK (bulan_mulai >= 1 AND bulan_mulai <= 12),
    bulan_selesai TINYINT NOT NULL CHECK (bulan_selesai >= 1 AND bulan_selesai <= 12),
    jumlah_bantuan DECIMAL(15,2) NOT NULL CHECK (jumlah_bantuan > 0),
    total_anggaran DECIMAL(20,2) DEFAULT 0 CHECK (total_anggaran >= 0),
    keterangan TEXT,
    status ENUM('draft', 'aktif', 'selesai') DEFAULT 'draft',
    dibuat_oleh INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dibuat_oleh) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_tahun (tahun),
    KEY idx_status (status),
    KEY idx_bulan_mulai_selesai (bulan_mulai, bulan_selesai),
    UNIQUE KEY unique_periode (nama_periode, tahun)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL DISTRIBUSI BLT
-- ============================================================
CREATE TABLE distribusi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT NOT NULL,
    periode_id INT NOT NULL,
    tanggal_distribusi DATE DEFAULT NULL,
    jumlah_diterima DECIMAL(15,2) NOT NULL CHECK (jumlah_diterima > 0),
    metode_pembayaran ENUM('tunai', 'transfer', 'cek pos') DEFAULT 'tunai',
    no_rekening VARCHAR(50) DEFAULT NULL,
    nama_bank VARCHAR(50) DEFAULT NULL,
    status_pembayaran ENUM('menunggu', 'sudah_bayar', 'gagal') DEFAULT 'menunggu',
    bukti_pembayaran VARCHAR(255) DEFAULT NULL,
    tanda_tangan VARCHAR(255) DEFAULT NULL,
    catatan TEXT,
    diproses_oleh INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_peserta_periode (peserta_id, periode_id),
    FOREIGN KEY (peserta_id) REFERENCES peserta(id) ON DELETE CASCADE,
    FOREIGN KEY (periode_id) REFERENCES periode_bantuan(id) ON DELETE CASCADE,
    FOREIGN KEY (diproses_oleh) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_peserta_id (peserta_id),
    KEY idx_periode_id (periode_id),
    KEY idx_status_pembayaran (status_pembayaran),
    KEY idx_tanggal_distribusi (tanggal_distribusi),
    KEY idx_metode_pembayaran (metode_pembayaran)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL DOKUMEN
-- ============================================================
CREATE TABLE dokumen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT,
    judul VARCHAR(200) NOT NULL,
    jenis_dokumen ENUM('KTP', 'KK', 'Surat Keterangan', 'Foto Rumah', 'SKTM', 'Lainnya') DEFAULT 'Lainnya',
    nama_file VARCHAR(255) NOT NULL,
    ukuran_file INT CHECK (ukuran_file > 0),
    tipe_file VARCHAR(50),
    keterangan TEXT,
    diunggah_oleh INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id) ON DELETE CASCADE,
    FOREIGN KEY (diunggah_oleh) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_peserta_id (peserta_id),
    KEY idx_jenis_dokumen (jenis_dokumen),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB;

-- ============================================================
-- TABEL LOG AKTIVITAS
-- ============================================================
CREATE TABLE log_aktivitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    aksi VARCHAR(100) NOT NULL,
    keterangan TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    KEY idx_user_id (user_id),
    KEY idx_aksi (aksi),
    KEY idx_created_at (created_at),
    KEY idx_user_date (user_id, created_at)
) ENGINE=InnoDB;

-- ============================================================
-- DATA AWAL
-- ============================================================

-- Users default (password: 123)
INSERT INTO users (nama, username, password, role, email, telepon) VALUES
('Administrator', 'admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/KFm', 'admin', 'admin@blt.go.id', '081234567890'),
('Kepala Desa', 'atasan', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/KFm', 'atasan', 'atasan@blt.go.id', '081234567891'),
('Sekretaris Desa', 'sekretaris', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/KFm', 'sekretaris', 'sekretaris@blt.go.id', '081234567892');

-- Kelurahan / Desa
INSERT INTO kelurahan (nama_kelurahan, kecamatan, kabupaten, provinsi) VALUES
('Pulau Tamang', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Bukit Raya', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Tanjung Lasa', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Aek Nabara', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Sungai Batang', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara'),
('Tambak Rejo', 'Mandailing Natal', 'Mandailing Natal', 'Sumatera Utara');

-- Periode Bantuan
INSERT INTO periode_bantuan (nama_periode, tahun, bulan_mulai, bulan_selesai, jumlah_bantuan, status, dibuat_oleh) VALUES
('BLT Triwulan I 2025', 2025, 1, 3, 900000.00, 'selesai', 1),
('BLT Triwulan II 2025', 2025, 4, 6, 900000.00, 'aktif', 1);

-- Sample Peserta
INSERT INTO peserta (nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, alamat, rt, rw, kelurahan_id, no_telepon, no_kk, nama_kepala_keluarga, jumlah_anggota_keluarga, penghasilan_bulanan, kategori_kemiskinan, status_verifikasi, ditambahkan_oleh) VALUES
('1275010101850001', 'Ahmad Yusuf', 'Pulau Tamang', '1985-01-01', 'L', 'Islam', 'Jl. Merdeka No. 10', '001', '001', 1, '081122334401', '1275011234560001', 'Ahmad Yusuf', 4, 800000, 'Miskin', 'disetujui', 2),
('1275010202870002', 'Siti Rahma', 'Pulau Tamang', '1987-02-02', 'P', 'Islam', 'Jl. Pahlawan No. 5', '002', '001', 1, '081122334402', '1275011234560002', 'Budi Santoso', 3, 600000, 'Sangat Miskin', 'disetujui', 2),
('1275010303900003', 'Budi Hartono', 'Mandailing Natal', '1990-03-03', 'L', 'Islam', 'Jl. Sudirman No. 22', '001', '002', 2, '081122334403', '1275011234560003', 'Budi Hartono', 5, 750000, 'Miskin', 'menunggu', 3),
('1275010404920004', 'Dewi Lestari', 'Bukit Raya', '1992-04-04', 'P', 'Islam', 'Jl. Diponegoro No. 8', '003', '002', 2, '081122334404', '1275011234560004', 'Andi Susanto', 2, 500000, 'Sangat Miskin', 'disetujui', 2),
('1275010505880005', 'Eko Prasetyo', 'Tanjung Lasa', '1988-05-05', 'L', 'Islam', 'Jl. A. Yani No. 15', '001', '003', 3, '081122334405', '1275011234560005', 'Eko Prasetyo', 6, 1000000, 'Hampir Miskin', 'ditolak', 2);

-- Sample Distribusi
INSERT INTO distribusi (peserta_id, periode_id, tanggal_distribusi, jumlah_diterima, metode_pembayaran, status_pembayaran, diproses_oleh) VALUES
(1, 1, '2025-01-15', 900000, 'tunai', 'sudah_bayar', 1),
(2, 1, '2025-01-15', 900000, 'tunai', 'sudah_bayar', 1),
(4, 1, '2025-01-16', 900000, 'tunai', 'sudah_bayar', 1),
(1, 2, '2025-04-10', 900000, 'tunai', 'sudah_bayar', 1),
(2, 2, NULL, 900000, 'tunai', 'menunggu', NULL);
