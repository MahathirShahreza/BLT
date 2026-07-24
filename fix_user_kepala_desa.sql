-- ============================================================
-- UPDATE USER ASISTEN DESA MENJADI KEPALA DESA
-- ============================================================
-- Jalankan ini di phpMyAdmin untuk update user yang sudah ada

-- Update user dengan username 'atasan' menjadi 'Kepala Desa'
UPDATE users 
SET nama = 'Kepala Desa'
WHERE username = 'atasan' AND role = 'atasan';

-- Verifikasi perubahan
SELECT id, nama, username, role, email FROM users WHERE role = 'atasan';

