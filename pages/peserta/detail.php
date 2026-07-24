<?php
require_once '../../includes/functions.php';
startSession();
requireLogin();
$currentUser = getCurrentUser();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$peserta = dbFetchOne("SELECT p.*, k.nama_kelurahan, k.kecamatan, u1.nama as ditambah_oleh_nama, u2.nama as verifikator_nama FROM peserta p LEFT JOIN kelurahan k ON p.kelurahan_id=k.id LEFT JOIN users u1 ON p.ditambahkan_oleh=u1.id LEFT JOIN users u2 ON p.diverifikasi_oleh=u2.id WHERE p.id=?", [$id]);
if (!$peserta) { header('Location: index.php?error=Peserta+tidak+ditemukan'); exit; }

$pageTitle = 'Detail Peserta';
require_once '../../includes/header.php';

$distribusiList = dbFetchAll("SELECT d.*, pb.nama_periode, pb.jumlah_bantuan FROM distribusi d JOIN periode_bantuan pb ON d.periode_id=pb.id WHERE d.peserta_id=? ORDER BY d.created_at DESC", [$id]);
$dokumenList = dbFetchAll("SELECT dok.*, u.nama as uploader FROM dokumen dok LEFT JOIN users u ON dok.diunggah_oleh=u.id WHERE dok.peserta_id=? ORDER BY dok.created_at DESC", [$id]);
?>

<div class="page-header">
    <h5><i class="bi bi-person-badge-fill me-2"></i>Detail Peserta</h5>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        <?php if (in_array($currentUser['role'], ['admin', 'atasan'])): ?>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php endif; ?>
        <a href="../laporan/cetak_kartu.php?id=<?= $id ?>" target="_blank" class="btn btn-outline-success btn-sm"><i class="bi bi-printer me-1"></i>Kartu Peserta</a>
    </div>
</div>

<div class="row g-3">
    <!-- Info Pribadi -->
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person-badge-fill"></i> Data Pribadi</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" style="width:130px">NIK</td><td class="fw-semibold"><?= sanitize($peserta['nik']) ?></td></tr>
                            <tr><td class="text-muted">No. KK</td><td><?= sanitize($peserta['no_kk'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Nama Lengkap</td><td class="fw-bold"><?= sanitize($peserta['nama_lengkap']) ?></td></tr>
                            <tr><td class="text-muted">Jenis Kelamin</td><td><?= $peserta['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td></tr>
                            <tr><td class="text-muted">Tempat/Tgl Lahir</td><td><?= sanitize($peserta['tempat_lahir']) ?>, <?= formatTanggal($peserta['tanggal_lahir']) ?></td></tr>
                            <tr><td class="text-muted">Agama</td><td><?= sanitize($peserta['agama']) ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr><td class="text-muted" style="width:130px">Pendidikan</td><td><?= sanitize($peserta['pendidikan'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Pekerjaan</td><td><?= sanitize($peserta['pekerjaan'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Status Kawin</td><td><?= sanitize($peserta['status_kawin']) ?></td></tr>
                            <tr><td class="text-muted">No. Telepon</td><td><?= sanitize($peserta['no_telepon'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Kep. Keluarga</td><td><?= sanitize($peserta['nama_kepala_keluarga'] ?? '-') ?></td></tr>
                            <tr><td class="text-muted">Jml Anggota KK</td><td><?= $peserta['jumlah_anggota_keluarga'] ?> orang</td></tr>
                        </table>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted small">Alamat</label>
                        <div class="p-2 rounded" style="background:#f5f7fa">
                            <?= sanitize($peserta['alamat']) ?> RT <?= sanitize($peserta['rt']) ?>/RW <?= sanitize($peserta['rw']) ?>,
                            <?= sanitize($peserta['nama_kelurahan'] ?? '-') ?>, <?= sanitize($peserta['kecamatan'] ?? '') ?><?= $peserta['kode_pos'] ? ', ' . sanitize($peserta['kode_pos']) : '' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Ekonomi -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-cash-stack"></i> Data Ekonomi</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4 text-center p-3 rounded" style="background:#f5f7fa">
                        <div class="text-muted small">Penghasilan/Bulan</div>
                        <div class="fw-bold" style="color:var(--primary)"><?= formatRupiah($peserta['penghasilan_bulanan']) ?></div>
                    </div>
                    <div class="col-md-4 text-center p-3 rounded" style="background:#f5f7fa">
                        <div class="text-muted small">Kategori</div>
                        <div class="fw-bold"><?= sanitize($peserta['kategori_kemiskinan']) ?></div>
                    </div>
                    <div class="col-md-4 text-center p-3 rounded" style="background:#f5f7fa">
                        <div class="text-muted small">Kondisi Rumah</div>
                        <div class="fw-bold"><?= sanitize($peserta['kondisi_rumah']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Status Verifikasi -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clipboard2-check"></i> Status Verifikasi</div>
            <div class="card-body text-center">
                <div class="mb-3" style="font-size:40px">
                    <?php if ($peserta['status_verifikasi'] === 'disetujui'): ?><i class="bi bi-check-circle-fill text-success"></i>
                    <?php elseif ($peserta['status_verifikasi'] === 'ditolak'): ?><i class="bi bi-x-circle-fill text-danger"></i>
                    <?php else: ?><i class="bi bi-hourglass-split text-warning"></i><?php endif; ?>
                </div>
                <?= badgeStatus($peserta['status_verifikasi']) ?>
                <?php if ($peserta['verifikator_nama']): ?>
                <div class="mt-2 text-muted small">
                    Oleh: <?= sanitize($peserta['verifikator_nama']) ?><br>
                    <?= formatTanggal($peserta['tanggal_verifikasi'], true) ?>
                </div>
                <?php endif; ?>
                <?php if ($peserta['catatan_verifikasi']): ?>
                <div class="mt-2 p-2 rounded text-start" style="background:#f5f7fa;font-size:12px">
                    <strong>Catatan:</strong> <?= sanitize($peserta['catatan_verifikasi']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Foto Peserta -->
        <?php if ($peserta['foto_peserta'] || $peserta['foto_ktp'] || $peserta['foto_kk']): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-camera"></i> Foto Dokumen</div>
            <div class="card-body">
                <?php if ($peserta['foto_peserta']): ?>
                <div class="mb-2">
                    <small class="text-muted d-block mb-1">Foto Peserta</small>
                    <a href="<?= UPLOAD_URL . $peserta['foto_peserta'] ?>" target="_blank">
                        <img src="<?= UPLOAD_URL . $peserta['foto_peserta'] ?>" alt="Foto" class="photo-preview">
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($peserta['foto_ktp']): ?>
                <div class="mb-2">
                    <small class="text-muted d-block mb-1">Foto KTP</small>
                    <a href="<?= UPLOAD_URL . $peserta['foto_ktp'] ?>" target="_blank">
                        <img src="<?= UPLOAD_URL . $peserta['foto_ktp'] ?>" alt="KTP" class="photo-preview">
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($peserta['foto_kk']): ?>
                <div>
                    <small class="text-muted d-block mb-1">Foto KK</small>
                    <a href="<?= UPLOAD_URL . $peserta['foto_kk'] ?>" target="_blank">
                        <img src="<?= UPLOAD_URL . $peserta['foto_kk'] ?>" alt="KK" class="photo-preview">
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mt-3">
            <div class="card-body">
                <div class="text-muted small">
                    <div>Ditambahkan oleh: <strong><?= sanitize($peserta['ditambah_oleh_nama'] ?? '-') ?></strong></div>
                    <div>Tanggal daftar: <strong><?= formatTanggal($peserta['created_at'], true) ?></strong></div>
                    <div>Terakhir diperbarui: <strong><?= formatTanggal($peserta['updated_at'], true) ?></strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
