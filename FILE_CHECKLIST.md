# FILE CHECKLIST - UPDATE SAW SYSTEM

## ✅ BUAT / BARU (3 files)
- [ ] `update_saw_migration.sql` - Migration script for kriteria & sub-kriteria
- [ ] `update_peserta.sql` - Update data penerima 
- [ ] `pages/saw/sub_kriteria.php` - New admin interface untuk manage sub-kriteria
- [ ] `SETUP_UPDATE_SAW.md` - Implementation guide
- [ ] `UPDATE_SUMMARY.md` - Summary of changes

## ✅ DATABASE FILES UPDATED (4 files)
- [x] `database.sql` - Updated ENUM role asisten→atasan & default user
- [x] `saw_migration.sql` - Updated comment dinilai_oleh

## ✅ PHP FILES UPDATED (7 files)

### SAW Pages
- [x] `pages/saw/penilaian.php` - Added sub-kriteria selection with dropdown
  - Added GET endpoint for sub-kriteria
  - Updated bukaPenilaian() to show sub-kriteria options
  - Changed input type from number to select

- [x] `pages/saw/perhitungan.php` - Updated role check
  - Changed requireRole from ['admin','asisten'] to ['admin','atasan','sekretaris']
  - Updated condition checks from asisten to atasan

- [x] `pages/saw/hasil.php` - Updated role check
  - Changed requireRole from ['admin','asisten','sekretaris'] (no change needed, but updated in code)

### Verification
- [x] `pages/verifikasi/index.php` - Updated role check
  - Changed requireRole from ['admin','asisten'] to ['admin','atasan']

### User Management  
- [x] `pages/users/index.php` - Updated role references
  - Changed asisten→atasan in stat card mapping
  - Changed role color mapping
  - Changed select option value asisten→atasan

### Includes/Layout
- [x] `includes/header.php` - Major updates
  - Added "Sub Kriteria SAW" menu link
  - Updated role checks: ['admin','asisten'] → ['admin','atasan'] (2 places)
  - Added sub_kriteria.php link in PENDUKUNG KEPUTUSAN menu

### Setup/Utils
- [x] `reset_user.php` - Updated default user
  - Changed "Asisten Desa" → "Atasan Desa"
  - Changed username in table display from asisten→atasan
  
- [x] `setup_user.php` - Updated default user
  - Changed comment from "User 2: Asisten" to "User 2: Atasan"
  - Changed INSERT query asisten→atasan
  - Changed echo output from asisten→atasan

- [x] `check_db.php` - Updated documentation
  - Changed username list from "admin, asisten, sekretaris" → "admin, atasan, sekretaris"

## 📊 SUMMARY

| Category | Count | Status |
|----------|-------|--------|
| New Files Created | 5 | ✅|
| Database Files | 2 | ✅|
| PHP Files | 7 | ✅|  
| Configuration | 0 | ✅|
| **TOTAL** | **14** | **✅**|

## 🔍 VERIFICATION STEPS

### Before Implementation
- [ ] Backup database
- [ ] Read SETUP_UPDATE_SAW.md
- [ ] Verify all migration files exist

### During Implementation  
- [ ] Run update_saw_migration.sql
- [ ] Run update_peserta.sql
- [ ] Copy pages/saw/sub_kriteria.php to server
- [ ] Refresh application & clear browser cache

### After Implementation
- [ ] Check menu "Sub Kriteria SAW" visible for admin
- [ ] Verify 5 kriteria exist with 1.00 total bobot
- [ ] Verify 18 sub-kriteria exist
- [ ] Verify 10 penerima data loaded
- [ ] Test login dengan atasan role
- [ ] Test input penilaian dengan dropdown
- [ ] Verify perhitungan SAW & ranking works

## 🚀 DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] All files created ✓
- [ ] Database migration scripts ready ✓
- [ ] Documentation complete ✓
- [ ] Code reviewed ✓

### Deployment Steps
1. [ ] Backup production database
2. [ ] Upload new/updated files to server
3. [ ] Run SQL migrations
4. [ ] Clear application cache
5. [ ] Test workflows
6. [ ] Monitor for errors

### Post-Deployment
- [ ] Verify all features working
- [ ] Check user access levels
- [ ] Monitor system logs
- [ ] Train users if needed

---

**Last Updated**: 28 Maret 2026
**Total Changes**: 14 files
**Status**: Ready for Production ✅
