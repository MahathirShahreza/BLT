<?php
$pageTitle = 'Data Peserta BLT';
require_once '../../includes/header.php';

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$kelurahanFilter = $_GET['kelurahan'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * ROWS_PER_PAGE;

$where = ['p.status_aktif = ?'];
$params = ['aktif'];
$types = 's';

if ($search) {
    $where[] = '(p.nama_lengkap LIKE ? OR p.nik LIKE ? OR p.no_telepon LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
    $types .= 'sss';
}
if ($statusFilter) {
    $where[] = 'p.status_verifikasi = ?';
    $params[] = $statusFilter;
    $types .= 's';
}
if ($kelurahanFilter) {
    $where[] = 'p.kelurahan_id = ?';
    $params[] = $kelurahanFilter;
    $types .= 'i';
}

$whereStr = 'WHERE ' . implode(' AND ', $where);

$total = dbFetchOne("SELECT COUNT(*) as c FROM peserta p $whereStr", $params, $types)['c'];
$rows  = dbFetchAll("SELECT p.*, k.nama_kelurahan FROM peserta p LEFT JOIN kelurahan k ON p.kelurahan_id = k.id $whereStr ORDER BY CASE WHEN p.status_verifikasi='menunggu' THEN 0 WHEN p.status_verifikasi='disetujui' THEN 1 ELSE 2 END ASC, p.created_at DESC LIMIT $offset, " . ROWS_PER_PAGE, $params, $types);
$kelurahans = dbFetchAll("SELECT * FROM kelurahan ORDER BY nama_kelurahan");

$canAdd = in_array($currentUser['role'], ['admin', 'atasan']);
$baseUrl = "?q=" . urlencode($search) . "&status=$statusFilter&kelurahan=$kelurahanFilter";
?>

<div class="page-header">
    <h5><i class="bi bi-people-fill me-2"></i>Data Peserta BLT</h5>
    <?php if ($canAdd): ?>
    <a href="tambah.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Peserta</a>
    <?php endif; ?>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" name="q" value="<?= sanitize($search) ?>" placeholder="Cari nama, NIK, telepon...">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="status">
                    <option value="">— Semua Status —</option>
                    <option value="menunggu" <?= $statusFilter==='menunggu'?'selected':'' ?>>Menunggu</option>
                    <option value="disetujui" <?= $statusFilter==='disetujui'?'selected':'' ?>>Disetujui</option>
                    <option value="ditolak" <?= $statusFilter==='ditolak'?'selected':'' ?>>Ditolak</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="kelurahan">
                    <option value="">— Semua Kelurahan —</option>
                    <?php foreach ($kelurahans as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $kelurahanFilter==$k['id']?'selected':'' ?>><?= sanitize($k['nama_kelurahan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-search"></i> Cari</button>
                <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <span>Total: <strong><?= number_format($total) ?></strong> peserta</span>
        <?php if ($currentUser['role'] === 'admin'): ?>
        <a href="../laporan/cetak_peserta.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-sm btn-outline-success"><i class="bi bi-printer me-1"></i>Cetak</a>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th>Nama Lengkap</th>
                    <th>NIK</th>
                    <th>Kelurahan</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $i => $p): ?>
            <tr>
                <td><?= $offset + $i + 1 ?></td>
                <td>
                    <div class="fw-semibold"><?= sanitize($p['nama_lengkap']) ?></div>
                    <small class="text-muted"><?= $p['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></small>
                </td>
                <td><small><?= sanitize($p['nik']) ?></small></td>
                <td><?= sanitize($p['nama_kelurahan'] ?? '-') ?></td>
                <td><span class="badge bg-secondary"><?= sanitize($p['kategori_kemiskinan'] ?? '-') ?></span></td>
                <td><?= badgeStatus($p['status_verifikasi']) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="detail.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Detail"><i class="bi bi-eye"></i></a>
                        <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'sekretaris'): ?>
                        <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($currentUser['role'] === 'atasan'): ?>
                        <a href="ubah_status.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-info" title="Ubah Status"><i class="bi bi-checkbox-circle"></i></a>
                        <?php endif; ?>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="hapus.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus" data-confirm="Yakin hapus peserta ini?"><i class="bi bi-trash"></i></a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data peserta</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total > ROWS_PER_PAGE): ?>
    <div class="card-footer d-flex justify-content-end"><?= pagination($total, $page, $baseUrl) ?></div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
