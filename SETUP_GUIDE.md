# 🔧 Database Setup & Installation Guide
## SisBLT - Sistem Informasi Bantuan Langsung Tunai v2.0

---

## ⚡ QUICK START (5 MENIT)

### Step 1: Backup Data Lama (Jika Ada)
```bash
# Di terminal/command prompt
mysqldump -u root -p beelte > backup_beelte_old.sql
```

### Step 2: Drop Database Lama
```sql
-- Di phpMyAdmin atau MySQL CLI
DROP DATABASE IF EXISTS beelte;
```

### Step 3: Import Database Baru
```bash
# Option A: Via terminal
mysql -u root -p < database.sql
mysql -u root -p < saw_migration.sql

# Option B: Via phpMyAdmin
1. Buka phpMyAdmin
2. Klik Import
3. Pilih file database.sql
4. Klik Upload
5. Ulangi untuk saw_migration.sql
```

### Step 4: Verify Installation
```sql
-- Di phpMyAdmin atau MySQL CLI
USE beelte;

-- Check tables
SHOW TABLES;

-- Result harus ada 16 tables:
-- dokumen, kelurahan, log_aktivitas, periode_bantuan, peserta,
-- distribusi, users, saw_kriteria, saw_penilaian, saw_hasil,
-- saw_periode, saw_periode_kriteria
```

### Step 5: Test Login
1. Buka browser: `http://localhost/blt-dashboardddd/`
2. Login dengan:
   - **Username:** `admin` / `asisten` / `sekretaris`
   - **Password:** `123`

---

## 📋 DAFTAR PERBAIKAN YANG DILAKUKAN

### ✅ Structure Improvements
- [ ] Added 40+ indexes untuk query performance
- [ ] Added 25+ CHECK constraints untuk data validation
- [ ] Added UNIQUE constraints untuk cegah duplikasi
- [ ] Added missing Foreign Key relationships
- [ ] Optimized table structures

### ✅ New Tables
- [ ] `saw_periode` - Linking SAW dengan period bantuan
- [ ] `saw_periode_kriteria` - Many-to-many kriteria per periode

### ✅ New Columns
- [ ] `peserta.jumlah_anak_usia_sekolah` - Untuk SAW criteria C5
- [ ] `saw_kriteria.nilai_min, nilai_max` - Range validation
- [ ] `saw_kriteria.is_active` - Track active status

---

## 🔄 DATA MIGRATION (Jika Ada Data Lama)

### Jika Anda punya data di database lama:

```sql
-- 1. Import tabel users
INSERT INTO users (nama, username, password, role, email, telepon, status, created_at)
SELECT nama, username, password, role, email, telepon, status, created_at 
FROM old_database.users
WHERE username != 'admin';  -- Skip default admin

-- 2. Import kelurahan
INSERT INTO kelurahan (nama_kelurahan, kecamatan, kabupaten, provinsi)
SELECT DISTINCT nama_kelurahan, kecamatan, kabupaten, 'Sumatera Barat'
FROM old_database.peserta;

-- 3. Import peserta (HATI-HATI dengan foreign key)
INSERT INTO peserta (
  nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, 
  agama, pendidikan, pekerjaan, status_kawin, alamat, rt, rw, 
  kelurahan_id, kode_pos, no_telepon, no_kk, nama_kepala_keluarga,
  jumlah_anggota_keluarga, penghasilan_bulanan, kategori_kemiskinan,
  kondisi_rumah, status_verifikasi, status_aktif, created_at
)
SELECT 
  nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin,
  agama, pendidikan, pekerjaan, status_kawin, alamat, rt, rw,
  (SELECT id FROM kelurahan k WHERE k.nama_kelurahan = p.kelurahan_id LIMIT 1),
  kode_pos, no_telepon, no_kk, nama_kepala_keluarga,
  jumlah_anggota_keluarga, penghasilan_bulanan, kategori_kemiskinan,
  kondisi_rumah, status_verifikasi, status_aktif, created_at
FROM old_database.peserta p;
```

**⚠️ PERHATIAN:** 
- Selalu backup dulu sebelum migration
- Test di development terlebih dahulu
- Verify data setelah migration

---

## 🧪 TESTING CHECKLIST

After installation, verify everything works:

```sql
-- 1. Test data exists
SELECT COUNT(*) as user_count FROM users;
SELECT COUNT(*) as peserta_count FROM peserta;
SELECT COUNT(*) as periode_count FROM periode_bantuan;

-- 2. Test foreign keys
SELECT p.nama_lengkap, k.nama_kelurahan 
FROM peserta p 
LEFT JOIN kelurahan k ON p.kelurahan_id = k.id
LIMIT 5;

-- 3. Test indexes
EXPLAIN SELECT * FROM peserta WHERE nik = '1375010101850001';
-- Should show "Using index" or index_name in Extra

-- 4. Test constraints
-- Ini akan ERROR (constraint violation) - ini yang kita inginkan:
INSERT INTO peserta (nik, nama_lengkap, jenis_kelamin, alamat, jumlah_anggota_keluarga, status_aktif)
VALUES ('1234567890123456', 'Test', 'L', 'Jl Test', -1, 'aktif');
-- Error: Check constraint 'peserta_chk_1' is violated
```

---

## 🚨 TROUBLESHOOTING

### Problem 1: "Table doesn't exist"
```
Error: Table 'beelte.peserta' doesn't exist
```
**Solution:**
```bash
# Re-import database.sql
mysql -u root -p beelte < database.sql
```

### Problem 2: "Foreign key constraint fails"
```
Error: Cannot add or update a child row: a foreign key constraint fails
```
**Solution:** Pastikan parent record ada dulu
```sql
-- Contoh: sebelum insert peserta, kelurahan harus ada dulu
SELECT COUNT(*) FROM kelurahan WHERE id = 1;  -- Harus return >= 1
```

### Problem 3: "Duplicate entry"
```
Error: Duplicate entry '1375010101850001' for key 'nik'
```
**Solution:** NIK sudah ada, gunakan NIK yang berbeda

### Problem 4: Query lambat
```sql
-- Check apakah query pakai index
EXPLAIN SELECT * FROM peserta WHERE kelurahan_id = 1;

-- Rebuild index jika diperlukan
REPAIR TABLE peserta;
OPTIMIZE TABLE peserta;
```

---

## 📊 DATABASE STRUCTURE OVERVIEW

```
beelte/
├── Authentication
│   └── users (admin, asisten, sekretaris)
│
├── Master Data
│   ├── kelurahan (villages/subdistricts)
│   └── peserta (beneficiaries)
│
├── Distribution Management
│   ├── periode_bantuan (aid periods)
│   └── distribusi (distribution records)
│
├── Document Management
│   └── dokumen (documents per peserta)
│
├── Scoring & Ranking (SAW Algorithm)
│   ├── saw_kriteria (criteria definitions)
│   ├── saw_penilaian (scores per peserta per criteria)
│   ├── saw_hasil (final SAW results)
│   ├── saw_periode (SAW period linking)
│   └── saw_periode_kriteria (criteria per period - many-to-many)
│
└── Audit & Logging
    └── log_aktivitas (system activity logs)
```

---

## 🔐 SECURITY NOTES

### Passwords
- Default password: `123` (bcrypt hashed)
- **CHANGE passwords setelah pertama kali login!**
- Password hashing: bcrypt ($2y$ format)

### Database Access
- User: `root`
- Password: (default XAMPP = kosong)
- **Ganti password database sebelum production!**

### File Permissions
```bash
# Set upload folder permission
chmod 755 uploads/
chmod 755 uploads/dokumen
chmod 755 uploads/foto
```

---

## 📈 PERFORMANCE OPTIMIZATION

### Enable Query Logging (untuk troubleshooting)
```sql
SET GLOBAL general_log = 'ON';
SET GLOBAL log_output = 'TABLE';
SELECT * FROM mysql.general_log ORDER BY event_time DESC LIMIT 10;
```

### Enable Slow Query Log
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;  -- Log query lebih dari 1 detik
```

### Monitor Table Sizes
```sql
SELECT 
  TABLE_NAME,
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'beelte'
ORDER BY size_mb DESC;
```

---

## 🔄 REGULAR MAINTENANCE

### Weekly
```sql
-- Optimize tables
OPTIMIZE TABLE peserta;
OPTIMIZE TABLE distribusi;
OPTIMIZE TABLE log_aktivitas;
```

### Monthly
```sql
-- Analyze table statistics untuk optimizer
ANALYZE TABLE peserta;
ANALYZE TABLE distribusi;
ANALYZE TABLE periode_bantuan;
```

### Quarterly
```sql
-- Backup database
mysqldump -u root -p beelte > backup_$(date +%Y%m%d).sql
```

---

## ✨ NEW FEATURES ENABLED

Dengan database improvements ini, sistem sekarang bisa:

1. ✅ **Fast Search** - Index pada kolom NIK, nama, kelurahan
2. ✅ **Flexible SAW** - Bobot bisa berbeda per periode
3. ✅ **Data Validation** - Check constraints di database layer
4. ✅ **Audit Trail** - Comprehensive logging dengan index
5. ✅ **Better Reports** - Proper indexing untuk agregasi queries
6. ✅ **No Duplicates** - Unique constraints mencegah data duplikasi
7. ✅ **Data Integrity** - Foreign keys ensure consistency

---

## 📞 SUPPORT

Jika ada error atau pertanyaan:

1. Check di bagian **TROUBLESHOOTING** di atas
2. Lihat file `DATABASE_IMPROVEMENTS.md` untuk detail technical
3. Run `SHOW WARNINGS;` setelah query yang error

---

**Last Updated:** March 2025  
**Database Version:** 2.0  
**Compatible:** MySQL 5.7+, MariaDB 10.3+, PHP 7.4+
