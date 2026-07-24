# SisBLT — Sistem Informasi Pembagian Bantuan Langsung Tunai
## Pemerintah Kota Pariaman, Sumatera Barat

---

## 📦 ISI PAKET

```
blt-dashboard/
├── index.php                   ← Entry point (redirect otomatis)
├── login.php                   ← Halaman login
├── logout.php                  ← Proses logout
├── dashboard.php               ← Dashboard utama
├── config.php                  ← Konfigurasi sistem & database
├── database.sql                ← Script SQL (buat dulu sebelum jalan)
│
├── includes/
│   ├── functions.php           ← Fungsi utama (DB, auth, helper)
│   ├── header.php              ← Template header + sidebar
│   └── footer.php              ← Template footer + script
│
├── assets/
│   └── css/style.css           ← Stylesheet utama
│
├── pages/
│   ├── peserta/
│   │   ├── index.php           ← Daftar peserta (semua role)
│   │   ├── tambah.php          ← Form tambah peserta (admin/sekretaris)
│   │   ├── edit.php            ← Form edit peserta (admin/sekretaris)
│   │   ├── detail.php          ← Detail peserta
│   │   └── hapus.php           ← Hapus (soft-delete, admin only)
│   │
│   ├── verifikasi/
│   │   └── index.php           ← Verifikasi ACC/tolak (admin/asisten)
│   │
│   ├── distribusi/
│   │   └── index.php           ← Pencairan BLT per periode (admin)
│   │
│   ├── periode/
│   │   └── index.php           ← Manajemen periode bantuan (admin)
│   │
│   ├── dokumen/
│   │   └── index.php           ← Upload & manajemen dokumen
│   │
│   ├── laporan/
│   │   ├── index.php           ← Statistik & grafik
│   │   ├── cetak_kartu.php     ← Cetak kartu peserta
│   │   ├── cetak_rekapitulasi.php ← Cetak laporan rekapitulasi
│   │   └── cetak_distribusi.php   ← Cetak berita acara distribusi
│   │
│   ├── users/
│   │   └── index.php           ← Manajemen user (admin)
│   │
│   ├── kelurahan/
│   │   └── index.php           ← Data kelurahan/desa (admin)
│   │
│   ├── log/
│   │   └── index.php           ← Log aktivitas sistem (admin)
│   │
│   ├── profil.php              ← Profil user
│   └── ubah_password.php       ← Ganti password
│
└── uploads/                    ← Folder upload (auto dibuat)
    ├── ktp/
    ├── kk/
    ├── peserta/
    ├── dokumen/
    └── bukti/
```

---

## ⚙️ LANGKAH INSTALASI

### 1. Persyaratan
- PHP 7.4+ (disarankan PHP 8.0+)
- MySQL 5.7+ / MariaDB 10.3+
- Web Server: Apache/Nginx dengan mod_rewrite
- Ekstensi PHP: mysqli, fileinfo, mbstring

### 2. Setup Database
```sql
-- Buka phpMyAdmin atau jalankan di MySQL CLI:
SOURCE /path/to/blt-dashboard/database.sql;
```

### 3. Konfigurasi
Buka file **`config.php`** dan sesuaikan:
```php
define('DB_HOST', 'localhost');    // Host database
define('DB_USER', 'root');         // Username database
define('DB_PASS', '');             // Password database
define('DB_NAME', 'db_blt');       // Nama database

define('BASE_URL', 'http://localhost/blt-dashboard/');  // URL sistem
define('APP_INSTANSI', 'Pemerintah Kota Pariaman');    // Nama instansi
```

### 4. Permission Folder Upload
```bash
chmod -R 775 uploads/
```
Atau di Windows: pastikan folder `uploads/` bisa ditulis.

### 5. Buka di Browser
```
http://localhost/blt-dashboard/
```

---

## 🔐 AKUN DEFAULT

| Username   | Password   | Role       | Hak Akses |
|------------|------------|------------|-----------|
| admin      | password   | Admin      | Semua fitur |
| asisten    | password   | Asisten    | Verifikasi peserta, lihat data |
| sekretaris | password   | Sekretaris | Tambah/edit peserta, upload dokumen |

> ⚠️ **WAJIB** ganti password setelah login pertama!

---

## 🎯 FITUR LENGKAP

### 👤 Manajemen Peserta
- ✅ Tambah peserta baru (Sekretaris/Admin)
- ✅ Edit data peserta
- ✅ Hapus peserta (soft-delete)
- ✅ Detail lengkap peserta
- ✅ Upload foto KTP, KK, foto peserta
- ✅ Filter berdasarkan status, kelurahan, pencarian

### ✔️ Verifikasi Peserta
- ✅ Antrian peserta menunggu verifikasi
- ✅ ACC (Setujui) oleh Asisten/Admin
- ✅ Tolak dengan catatan alasan
- ✅ Revisi status verifikasi
- ✅ Badge hitungan per status

### 💰 Distribusi BLT
- ✅ Otomatis buat daftar distribusi dari peserta disetujui
- ✅ Catat pencairan per peserta (tunai/transfer/cek pos)
- ✅ Upload bukti pembayaran
- ✅ Progress bar per periode
- ✅ Filter status pembayaran

### 📅 Periode Bantuan
- ✅ Buat periode baru (triwulan/semester/tahunan)
- ✅ Aktifkan/selesaikan periode
- ✅ Statistik per periode (progress, nominal)
- ✅ Hanya 1 periode aktif sekaligus

### 📁 Dokumen
- ✅ Upload dokumen per peserta atau umum
- ✅ Berbagai format: PDF, JPG, PNG, DOC, DOCX
- ✅ Lihat & download dokumen
- ✅ Hapus dokumen (Admin/Sekretaris)

### 📊 Laporan & Statistik
- ✅ Dashboard statistik dengan Chart.js (Donut, Bar)
- ✅ Grafik peserta per kelurahan
- ✅ Grafik gender, kategori kemiskinan
- ✅ Rekapitulasi per periode
- ✅ Cetak Kartu Peserta (print-ready)
- ✅ Cetak Rekapitulasi (dengan kop surat + TTD)
- ✅ Cetak Berita Acara Distribusi

### 👥 Manajemen User
- ✅ Tambah user baru (Admin, Asisten, Sekretaris)
- ✅ Aktifkan/nonaktifkan user
- ✅ Reset password user
- ✅ Profil & ubah password sendiri

### 🔒 Keamanan
- ✅ Session dengan timeout otomatis (1 jam)
- ✅ Password di-hash dengan bcrypt
- ✅ Role-based access control (RBAC)
- ✅ Sanitasi input (XSS prevention)
- ✅ Prepared statements (SQL injection prevention)
- ✅ Log aktivitas seluruh user

---

## 📞 SUPPORT
Sistem ini dibuat khusus untuk kebutuhan pendataan dan distribusi BLT.
Untuk pengembangan lebih lanjut, silakan hubungi pengembang sistem.
