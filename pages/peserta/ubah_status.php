<?php
require_once '../../includes/functions.php';
startSession();
requireLogin();
requireRole(['admin', 'atasan']);
$currentUser = getCurrentUser();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$peserta = dbFetchOne("SELECT * FROM peserta WHERE id=?", [$id]);
if (!$peserta) { header('Location: index.php?error=Peserta+tidak+ditemukan'); exit; }

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status_verifikasi'] ?? '';
    $catatan = trim($_POST['catatan_verifikasi'] ?? '');
    
    if (!in_array($status, ['menunggu', 'disetujui', 'ditolak'])) {
        $errors[] = 'Status yang dipilih tidak valid';
    }
    
    if (empty($errors)) {
        $db = getDB();
        $sql = "UPDATE peserta SET status_verifikasi='$status', catatan_verifikasi='".addslashes($catatan)."', diverifikasi_oleh=".$currentUser['id'].", tanggal_verifikasi=NOW() WHERE id=$id";
        
        if ($db->query($sql)) {
            logAktivitas('Ubah Status Peserta', 'Mengubah status peserta ' . $peserta['nama_lengkap'] . ' menjadi ' . $status);
            header('Location: index.php?success=' . urlencode('Status peserta berhasil diubah'));
            exit;
        } else {
            $errors[] = 'Error: ' . $db->error;
        }
    }
}

$pageTitle = 'Ubah Status Verifikasi - ' . $peserta['nama_lengkap'];
require_once '../../includes/header.php';
?>

<div class="page-header">
    <h5><i class="bi bi-checkbox-circle me-2"></i>Ubah Status Verifikasi</h5>
    <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Terdapat kesalahan:</strong>
    <ul class="mb-0 mt-2"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Data Peserta</h6>
                <table class="table table-sm mb-0">
                    <tr>
                        <td><strong>Nama:</strong></td>
                        <td><?= sanitize($peserta['nama_lengkap']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>NIK:</strong></td>
                        <td><?= sanitize($peserta['nik']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status Saat Ini:</strong></td>
                        <td><?= badgeStatus($peserta['status_verifikasi']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Kategori:</strong></td>
                        <td><?= sanitize($peserta['kategori_kemiskinan'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Status Verifikasi <span class="text-danger">*</span></label>
                    <select class="form-select" name="status_verifikasi" required>
                        <option value="">— Pilih Status —</option>
                        <option value="menunggu" <?= $peserta['status_verifikasi'] === 'menunggu' ? 'selected' : '' ?>>Menunggu Verifikasi</option>
                        <option value="disetujui" <?= $peserta['status_verifikasi'] === 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="ditolak" <?= $peserta['status_verifikasi'] === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-12">
                    <label class="form-label">Catatan/Keterangan</label>
                    <textarea class="form-control" name="catatan_verifikasi" rows="3" placeholder="Masukkan catatan (opsional)"><?= sanitize($peserta['catatan_verifikasi'] ?? '') ?></textarea>
                    <small class="text-muted">Contoh: Data sudah diverifikasi, dll</small>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Status</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
