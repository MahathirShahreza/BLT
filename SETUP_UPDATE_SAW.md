# PANDUAN UPDATE SISTEM SAW - BLT DASHBOARD

## 📋 Perubahan Utama

Sistem penilaian SAW telah diperbarui dengan fitur berikut:

### 1. **Update Kriteria Penilaian** (5 Kriteria Baru)
- **C1**: Kehilangan Mata Pencaharian (30%) - Benefit
- **C2**: Anggota Keluarga Sakit/Kronis/Disabilitas (25%) - Benefit
- **C3**: Status Penerimaan PKH (20%) - Cost
- **C4**: Lansia dalam Keluarga (15%) - Benefit
- **C5**: Status Kepala Keluarga (10%) - Benefit

### 2. **Fitur Sub-Kriteria**
Setiap kriteria memiliki sub-kriteria dengan nilai 1-5:
- Nilai 5: Paling layak/berat
- Nilai 1: Paling tidak layak/ringan

### 3. **Data Alternatif (10 Penerima)**
```
A01 - Misdewita
A02 - Sahnun
A03 - Yudirsyah
A04 - Samsiar Syam
A05 - Roslaini
A06 - Rismawati
A07 - Rorsmani
A08 - Rosdiana
A09 - Ani Marsiti
A10 - Derita Nur
```

### 4. **Perubahan Role**
- **Asisten** → **Atasan** (Username tetap bisa diganti, atau gunakan `atasan`)
- User dengan role `atasan` yang dapat melakukan input penilaian

---

## 🔧 Langkah-Langkah Update

### Step 1: Backup Database (PENTING!)
```bash
# Menggunakan mysqldump
mysqldump -u root -p beelte > beelte_backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Jalankan Migration File

#### **Opsi A: Menggunakan phpMyAdmin**
1. Buka phpMyAdmin: http://localhost/phpmyadmin/
2. Pilih database `beelte`
3. Klik tab "SQL"
4. Copy-paste isi file `update_saw_migration.sql` (atau `update_peserta.sql`)
5. Klik "GO"

#### **Opsi B: Menggunakan MySQL Command Line**
```bash
# Buka MySQL CLI
mysql -u root -p

# Di dalam MySQL CLI:
USE beelte;

# Copy-paste isi dari update_saw_migration.sql
# (atau jalankan file langsung)
source c:/xampp/htdocs/blt-dashboardddd/update_saw_migration.sql;
```

#### **Opsi C: Menggunakan File**.sql Langsung**
```bash
mysql -u root -p beelte < update_saw_migration.sql
```

### Step 3: Jalankan Update Peserta
```bash
mysql -u root -p beelte < update_peserta.sql
```

### Step 4: Verifikasi Update

Cek di phpMyAdmin atau MySQL:

**Cek Kriteria Baru:**
```sql
SELECT kode_kriteria, nama_kriteria, bobot, jenis FROM saw_kriteria ORDER BY urutan;
```
Hasil yang diharapkan: 5 kriteria dengan total bobot = 1.0000

**Cek Sub-Kriteria:**
```sql
SELECT k.kode_kriteria, s.nilai_sub, s.deskripsi 
FROM saw_sub_kriteria s
JOIN saw_kriteria k ON s.kriteria_id = k.id
ORDER BY k.urutan, s.nilai_sub DESC;
```

**Cek Data Peserta:**
```sql
SELECT id, nama_lengkap, pekerjaan FROM peserta ORDER BY id;
```
Hasil yang diharapkan: 10 peserta baru

**Cek Role Users:**
```sql
SELECT id, nama, username, role FROM users;
```
Hasil yang diharapkan: Role `asisten` berubah menjadi `atasan`

---

## 👥 Akses Pengguna Setelah Update

| Username | Role    | Fungsi |
|----------|---------|--------|
| admin    | admin   | Kelola kriteria, sub-kriteria, user, melihat hasil |
| atasan   | atasan  | Input penilaian, melihat perhitungan |
| sekretaris | sekretaris | Melihat laporan & hasil |

### Akses Menu:
- **Admin** → Lihat tab "Kriteria SAW" dan "Sub Kriteria SAW" di menu
- **Atasan** → Bisa akses "Input Penilaian SAW" untuk menilai peserta
- **Sekretaris** → Bisa lihat "Hasil & Rekomendasi"

---

## 📝 Menggunakan Sistem SAW

### 1. Admin Setup Kriteria & Sub-Kriteria
1. Pergi ke menu **Kriteria SAW** untuk melihat/edit kriteria
2. Pergi ke menu **Sub Kriteria SAW** untuk setup deskripsi sub-kriteria
3. Setiap sub-kriteria sudah pre-fill dengan data default

### 2. Atasan Melakukan Penilaian
1. Login dengan role **atasan**
2. Pergi ke menu **Input Penilaian SAW**
3. Pilih peserta yang akan dinilai
4. Pilih nilai 1-5 untuk setiap kriteria berdasarkan sub-kriteria yang ditampilkan
5. Klik "Simpan Nilai"

### 3. Melihat Hasil Perhitungan
1. Pergi ke menu **Perhitungan SAW** (untuk admin/atasan)
2. Klik tombol "Refresh Nilai Akhir" untuk hitung ulang
3. Lihat **Hasil & Rekomendasi** untuk melihat peringkat penerima

---

## 🔄 Rollback (Jika Ada Masalah)

Jika terjadi kesalahan, bisa restore dari backup:

```bash
mysql -u root -p beelte < beelte_backup_YYYYMMDD_HHMMSS.sql
```

---

## ⚠️ Catatan Penting

1. **Data Penilaian Lama Dihapus**: Karena kriteria berubah, semua data penilaian lama akan dihapus saat migrasi
2. **Sub-Kriteria Pre-filled**: Data sub-kriteria sudah terisi otomatis saat migrasi
3. **Username bisa diubah**: Role `asisten` → `atasan`, tapi username tetap `asisten` atau bisa diganti
4. **Backup terlebih dahulu**: Selalu backup database sebelum melakukan perubahan struktur

---

## 📞 Troubleshooting

### Masalah: "Role atasan tidak ada di dropdown"
**Solusi**: Refresh halaman atau hapus cache browser (Ctrl+F5)

### Masalah: "Sub-kriteria tidak muncul saat input nilai"
**Solusi**: Pastikan sub_kriteria.php ada di folder `pages/saw/` dan refresh halaman

### Masalah: "Nilai akhir tidak berubah setelah input penilaian"
**Solusi**: Klik tombol "Refresh Nilai Akhir" di menu Perhitungan SAW

---

## 📞 Kontak Support

Jika ada pertanyaan atau masalah, silakan hubungi administrator sistem.

---

**Dokumentasi Updated**: 2026-03-28
**Versi Sistem**: 2.5 (SAW Update Edition)
