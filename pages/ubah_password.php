<?php
require_once '../includes/functions.php';
startSession();
requireLogin();
$currentUser = getCurrentUser();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordLama = $_POST['password_lama'] ?? '';
    $passwordBaru = $_POST['password_baru'] ?? '';
    $konfirmasi   = $_POST['konfirmasi'] ?? '';

    $user = dbFetchOne("SELECT password FROM users WHERE id=?", [$currentUser['id']]);

    if (!password_verify($passwordLama, $user['password'])) {
        $errors[] = 'Password lama tidak sesuai';
    }
    if (strlen($passwordBaru) < 6) {
        $errors[] = 'Password baru minimal 6 karakter';
    }
    if ($passwordBaru !== $konfirmasi) {
        $errors[] = 'Konfirmasi password tidak cocok';
    }

    if (empty($errors)) {
        $hashed = password_hash($passwordBaru, PASSWORD_DEFAULT);
        dbQuery("UPDATE users SET password=? WHERE id=?", [$hashed, $currentUser['id']]);
        logAktivitas('Ubah Password', 'Password berhasil diubah');
        $success = true;
    }
}

$pageTitle = 'Ubah Password';
require_once '../includes/header.php';
?>
}
?>

<div class="page-header">
    <h5><i class="bi bi-key-fill me-2"></i>Ubah Password</h5>
    <a href="profil.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali ke Profil</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-shield-lock-fill"></i> Ganti Password Akun</div>
            <div class="card-body">
                <?php if ($success): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>Password berhasil diubah!</div>
                <?php endif; ?>
                <?php if ($errors): ?>
                <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Password Lama <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password_lama" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password_baru" required minlength="6">
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="konfirmasi" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-lock-fill me-1"></i>Ubah Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
