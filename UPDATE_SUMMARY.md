# UPDATE SISTEM SAW - RINGKASAN PERUBAHAN

## ✅ Status: Selesai Implementasi

Tanggal: 28 Maret 2026
Versioning: v2.5 (SAW Enhancement Edition)

---

## 📦 File-File yang Diubah

### 1. **Database Files**
- ✅ `database.sql` - Update ENUM role: `asisten` → `atasan`
- ✅ `update_saw_migration.sql` (BARU) - Migration untuk kriteria & sub-kriteria baru
- ✅ `update_peserta.sql` (BARU) - Update data alternatif/penerima
- ✅ `saw_migration.sql` - Update comment penilaian

### 2. **File PHP Utama**
- ✅ `pages/saw/sub_kriteria.php` (BARU) - Interface kelola sub-kriteria
- ✅ `pages/saw/penilaian.php` - Update untuk menampilkan sub-kriteria saat input
- ✅ `pages/saw/kriteria.php` - Sudah support (tidak perlu ubah)
- ✅ `pages/saw/perhitungan.php` - Sudah support perhitungan SAW (no change needed)
- ✅ `pages/saw/hasil.php` - Update role menjadi atasan
- ✅ `pages/verifikasi/index.php` - Update role menjadi atasan

### 3. **File Konfigurasi & Auth**
- ✅ `includes/header.php` - Update role checks & add menu sub-kriteria
- ✅ `includes/functions.php` - No change (sudah fleksibel)
- ✅ `pages/users/index.php` - Update role options & stat card

### 4. **File Setup**
- ✅ `reset_user.php` - Update user default ke atasan
- ✅ `setup_user.php` - Update user default ke atasan
- ✅ `check_db.php` - Update dokumentasi username

### 5. **Dokumentasi**
- ✅ `SETUP_UPDATE_SAW.md` (BARU) - Panduan lengkap update & implementasi

---

## 🎯 Fitur-Fitur Baru / Update

### Kriteria Penilaian (5 buah):
1. **C1** - Kehilangan Mata Pencaharian (30%) - Benefit
2. **C2** - Anggota Keluarga Sakit/Kronis/Disabilitas (25%) - Benefit  
3. **C3** - Status Penerimaan PKH (20%) - Cost
4. **C4** - Lansia dalam Keluarga (15%) - Benefit
5. **C5** - Status Kepala Keluarga (10%) - Benefit

**Total Bobot: 1.00** ✓

### Sub-Kriteria (Data Pre-filled):
Setiap kriteria memiliki 2-5 sub-kriteria dengan nilai skala 1-5:
- Total: **18 sub-kriteria** sudah terisi otomatis

### Data Penerima (10 Alternatif):
- A01 - Misdewita
- A02 - Sahnun
- A03 - Yudirsyah
- A04 - Samsiar Syam
- A05 - Roslaini
- A06 - Rismawati
- A07 - Rorsmani
- A08 - Rosdiana
- A09 - Ani Marsiti
- A10 - Derita Nur

### Menu Baru:
- **Admin**: "Sub Kriteria SAW" → Kelola deskripsi sub-kriteria
- **Atasan**: "Input Penilaian SAW" → Input nilai dengan dropdown sub-kriteria
- **Semua role**: View "Hasil & Rekomendasi" dengan ranking

### Role Update:
- `asisten` → `atasan` (di database ENUM)
- User lama otomatis updated saat migration

---

## 🚀 Implementasi / Cara Install

### Quick Start:
1. **Backup database** (PENTING!)
2. Buka phpMyAdmin atau MySQL CLI
3. Jalankan `update_saw_migration.sql` (buat kriteria & sub-kriteria)
4. Jalankan `update_peserta.sql` (buat data penerima)
5. Refresh halaman aplikasi

### Verifikasi:
- Cek menu pada akun admin - harus ada "Sub Kriteria SAW"
- Cek "Kriteria SAW" - seharusnya 5 kriteria baru
- Cek "Sub Kriteria SAW" - seharusnya filled dengan deskripsi
- Login sebagai atasan - bisa keluar input penilaian

### Lengkap:
Baca file `SETUP_UPDATE_SAW.md` untuk panduan detail

---

## 🎮 Workflow Penggunaan

### Untuk Admin:
1. Setup kriteria di "Kriteria SAW" 
2. Review/edit sub-kriteria di "Sub Kriteria SAW"
3. Melihat hasil di "Hasil & Rekomendasi"

### Untuk Atasan (Penilai):
1. Pindah ke "Input Penilaian SAW"
2. Pilih peserta
3. Untuk setiap kriteria, pilih nilai 1-5 sesuai deskripsi sub-kriteria
4. Simpan
5. View perhitungan di "Perhitungan SAW"

### Untuk Sekretaris:
1. View "Hasil & Rekomendasi" untuk laporan akhir

---

## 📊 Sistem Perhitungan SAW (Unchanged)

Rumus tetap sama dan sudah correct:

**Normalisasi:**
- Benefit: `R(ij) = X(ij) / max(X(j))`
- Cost: `R(ij) = min(X(j)) / X(ij)`

**Preferensi:**
- `V(i) = Σ (w(j) × R(ij))`

**Ranking:**
- Sort V descending
- Top sesuai kuota = Direkomendasikan

---

## ⚠️ Catatan Penting

1. ⚠️ **Data penilaian lama akan dihapus** karena kriteria berubah
2. ✓ **Sub-kriteria otomatis terisi** saat migration
3. ✓ **Backward compatible** - tidak ada breaking changes
4. ✓ **Semua file PHP sudah updated** - tidak perlu edit manual
5. ✓ **Role update otomatis** - asisten jadi atasan

---

## 📁 Struktur Database Baru

### Tabel Baru:
- `saw_sub_kriteria` - Deskripsi & nilai sub-kriteria

### Tabel Existing (Modified):
- `users.role` - Enum updated
- `saw_kriteria` - Data replaced
- `peserta` - Data replaced (10 penerima)
- `saw_penilaian` - Cleared (perlu diisi ulang)
- `saw_hasil` - Cleared

---

## 🔄 Rollback / Reset

Jika ada masalah:

**Opsi 1: Restore dari Backup**
```sql
source backup_database_anda.sql;
```

**Opsi 2: Manual Reset User Role**
```sql
ALTER TABLE users MODIFY role ENUM('admin', 'asisten', 'sekretaris');
UPDATE users SET role = 'asisten' WHERE rolle = 'atasan';
```

---

## ✨ Testing Checklist

- [ ] Database migration berhasil
- [ ] Admin bisa akses menu "Kriteria SAW"
- [ ] Admin bisa akses menu "Sub Kriteria SAW"
- [ ] Sub-kriteria sudah terisi 18 buah
- [ ] 5 kriteria baru dengan bobot total 1.00
- [ ] 10 data penerima sudah ada
- [ ] Atasan bisa input penilaian dengan dropdown
- [ ] Perhitungan SAW menghasilkan ranking
- [ ] Hasil & Rekomendasi menampilkan top 10

---

## 📞 Support & Troubleshooting

Lihat file: `SETUP_UPDATE_SAW.md` untuk troubleshooting lengkap

---

**Last Updated**: 28 Maret 2026
**Version**: 2.5
**Status**: ✅ Ready for Production
