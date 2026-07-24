-- ============================================================
-- MIGRATION: Add Document Verification Features
-- ============================================================
-- Purpose: Enable admin upload & atasan verification workflow
-- Date: 2026-03-30

-- Add verification columns to dokumen table
ALTER TABLE dokumen 
ADD COLUMN status_verifikasi ENUM('pending', 'terverifikasi', 'ditolak') DEFAULT 'pending' AFTER diunggah_oleh,
ADD COLUMN diverifikasi_oleh INT DEFAULT NULL AFTER status_verifikasi,
ADD COLUMN tanggal_verifikasi TIMESTAMP NULL DEFAULT NULL AFTER diverifikasi_oleh,
ADD COLUMN catatan_verifikasi TEXT DEFAULT NULL AFTER tanggal_verifikasi,
ADD FOREIGN KEY (diverifikasi_oleh) REFERENCES users(id) ON DELETE SET NULL,
ADD KEY idx_status_verifikasi (status_verifikasi),
ADD KEY idx_diverifikasi_oleh (diverifikasi_oleh);
