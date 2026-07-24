<?php
require_once '../../includes/functions.php';
startSession();
requireLogin();
requireRole(['admin']);
$currentUser = getCurrentUser();

// Tambah user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    if ($aksi === 'tambah') {
        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $telepon  = trim($_POST['telepon'] ?? '');
        $role     = $_POST['role'] ?? 'admin';
        $password = $_POST['password'] ?? '';

        $errors = [];
        if (empty($nama)) $errors[] = 'Nama wajib diisi';
        if (empty($username)) $errors[] = 'Username wajib diisi';
        if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter';
        $exist = dbFetchOne("SELECT id FROM users WHERE username=?", [$username]);
        if ($exist) $errors[] = 'Username sudah digunakan';

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            dbInsert("INSERT INTO users (nama, username, password, role, email, telepon) VALUES (?,?,?,?,?,?)", [$nama, $username, $hashed, $role, $email, $telepon]);
            logAktivitas('Tambah User', "User baru: $username ($role)");
            header('Location: index.php?success=' . urlencode('User berhasil ditambahkan')); exit;
        }
        $_SESSION['user_errors'] = $errors;
    }

    if ($aksi === 'toggle_status') {
        $uid = (int)$_POST['user_id'];
        if ($uid !== $currentUser['id']) {
            $u = dbFetchOne("SELECT status FROM users WHERE id=?", [$uid]);
            $newStatus = $u['status'] === 'aktif' ? 'nonaktif' : 'aktif';
            dbQuery("UPDATE users SET status=? WHERE id=?", [$newStatus, $uid]);
            logAktivitas('Toggle Status User', "User ID $uid -> $newStatus");
        }
        header('Location: index.php?success=' . urlencode('Status user diperbarui')); exit;
    }

    if ($aksi === 'reset_password') {
        $uid = (int)$_POST['user_id'];
        $newPass = trim($_POST['new_password'] ?? '');
        if (strlen($newPass) >= 6) {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            dbQuery("UPDATE users SET password=? WHERE id=?", [$hashed, $uid]);
            logAktivitas('Reset Password', "Reset password user ID: $uid");
            header('Location: index.php?success=' . urlencode('Password berhasil direset')); exit;
        }
    }

    if ($aksi === 'hapus') {
        $uid = (int)$_POST['user_id'];
        if ($uid !== $currentUser['id']) {
            dbQuery("DELETE FROM users WHERE id=?", [$uid]);
            logAktivitas('Hapus User', "User ID $uid dihapus");
            header('Location: index.php?success=' . urlencode('User berhasil dihapus')); exit;
        } else {
            header('Location: index.php?error=' . urlencode('Tidak dapat menghapus user sendiri')); exit;
        }
    }
}

$pageTitle = 'Manajemen User';
require_once '../../includes/header.php';

$errors = $_SESSION['user_errors'] ?? []; unset($_SESSION['user_errors']);
$users  = dbFetchAll("SELECT u.*, (SELECT COUNT(*) FROM log_aktivitas l WHERE l.user_id=u.id) as total_log FROM users u ORDER BY u.role, u.nama");
?>
    <div class="card-header"><i class="bi bi-people"></i> Daftar User Sistem</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>No</th><th>Nama / Username</th><th>Role</th><th>Email</th><th>Telepon</th><th>Login Terakhir</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($users as $i => $u): ?>
            <?php $roleColor = ['admin'=>'primary','atasan'=>'success'][$u['role']] ?? 'secondary'; ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:34px;height:34px;background:linear-gradient(135deg,var(--primary),var(--primary-light));border-radius:8px;display:grid;place-items:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0">
                            <?= strtoupper(substr($u['nama'],0,1)) ?>
                        </div>
                        <div>
                            <div class="fw-semibold"><?= sanitize($u['nama']) ?></div>
                            <small class="text-muted">@<?= sanitize($u['username']) ?></small>
                        </div>
                    </div>
                </td>
                <td><span class="badge bg-<?= $roleColor ?>"><?= getRoleLabel($u['role']) ?></span></td>
                <td><?= sanitize($u['email'] ?? '-') ?></td>
                <td><?= sanitize($u['telepon'] ?? '-') ?></td>
                <td><small><?= $u['last_login'] ? formatTanggal($u['last_login'], true) : 'Belum pernah' ?></small></td>
                <td><?= badgeStatus($u['status']) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <?php if ($u['id'] !== $currentUser['id']): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="aksi" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button class="btn btn-sm <?= $u['status']==='aktif'?'btn-outline-warning':'btn-outline-success' ?>" title="<?= $u['status']==='aktif'?'Nonaktifkan':'Aktifkan' ?>">
                                <i class="bi bi-<?= $u['status']==='aktif'?'person-dash':'person-check' ?>"></i>
                            </button>
                        </form>
                        <button class="btn btn-sm btn-outline-secondary" onclick="showReset(<?= $u['id'] ?>, '<?= addslashes(sanitize($u['nama'])) ?>')" title="Reset Password">
                            <i class="bi bi-key"></i>
                        </button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus user <?= addslashes(sanitize($u['nama'])) ?>? Data tidak dapat dikembalikan.');">
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Hapus User">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted small">(Anda)</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="tambahModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="aksi" value="tambah">
                <div class="modal-header"><h5 class="modal-title">Tambah User Baru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label">Nama Lengkap *</label><input type="text" class="form-control" name="nama" required></div>
                        <div class="col-md-6"><label class="form-label">Username *</label><input type="text" class="form-control" name="username" required></div>
                        <div class="col-md-6">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="role">
                                <option value="admin">Sekretaris</option>
                                <option value="atasan">Kepala Desa</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
                        <div class="col-md-6"><label class="form-label">Telepon</label><input type="text" class="form-control" name="telepon"></div>
                        <div class="col-12"><label class="form-label">Password * (min. 6 karakter)</label><input type="password" class="form-control" name="password" required minlength="6"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="aksi" value="reset_password">
                <input type="hidden" name="user_id" id="resetUserId">
                <div class="modal-header"><h5 class="modal-title">Reset Password</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Reset password untuk: <strong id="resetNama"></strong></p>
                    <label class="form-label">Password Baru (min. 6 karakter)</label>
                    <input type="password" class="form-control" name="new_password" required minlength="6">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $extraJs = <<<JS
<script>
function showReset(id, nama) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetNama').textContent = nama;
    new bootstrap.Modal(document.getElementById('resetModal')).show();
}
</script>
JS;
require_once '../../includes/footer.php'; ?>
