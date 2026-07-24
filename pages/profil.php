<?php
require_once '../includes/functions.php';
startSession();
requireLogin();
$currentUser = getCurrentUser();

$user = dbFetchOne("SELECT * FROM users WHERE id=?", [$currentUser['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $errors  = [];

    if (empty($nama)) $errors[] = 'Nama wajib diisi';

    if (empty($errors)) {
        dbQuery("UPDATE users SET nama=?, email=?, telepon=? WHERE id=?",
            [$nama, $email, $telepon, $currentUser['id']]);
        $_SESSION['user_nama'] = $nama;
        logAktivitas('Update Profil', 'Memperbarui data profil');
        header('Location: profil.php?success=' . urlencode('Profil berhasil diperbarui'));
        exit;
    }
}

$pageTitle = 'Profil Saya';
require_once '../includes/header.php';
?>
?>

<div class="page-header">
    <h5><i class="bi bi-person-circle me-2"></i>Profil Saya</h5>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul></div>
<?php endif; ?>

<div class="row g-3 justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-fill"></i> Informasi Akun</div>
            <div class="card-body">
                <!-- Avatar -->
                <div class="text-center mb-4">
                    <div style="width:80px;height:80px;background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:20px;display:inline-grid;place-items:center;font-size:32px;font-weight:700;color:#fff;">
                        <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-primary fs-6"><?= getRoleLabel($user['role']) ?></span>
                    </div>
                </div>

                <form method="POST">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama" value="<?= sanitize($user['nama']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= sanitize($user['username']) ?>" disabled>
                            <small class="text-muted">Username tidak dapat diubah</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?= getRoleLabel($user['role']) ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= sanitize($user['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" name="telepon" value="<?= sanitize($user['telepon'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Login Terakhir</label>
                            <input type="text" class="form-control" value="<?= $user['last_login'] ? formatTanggal($user['last_login'], true) : 'Belum pernah' ?>" disabled>
                        </div>
                    </div>
                    <hr class="section-divider">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="ubah_password.php" class="btn btn-outline-warning"><i class="bi bi-key me-1"></i>Ubah Password</a>
                        <button type="submit" name="update_profil" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
