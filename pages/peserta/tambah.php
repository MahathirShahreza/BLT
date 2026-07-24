<?php
require_once '../../includes/functions.php';
startSession();
requireLogin();
requireRole(['admin', 'atasan']);
$currentUser = getCurrentUser();

$errors = [];
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nik'                   => trim($_POST['nik'] ?? ''),
        'nama_lengkap'          => trim($_POST['nama_lengkap'] ?? ''),
        'tempat_lahir'          => trim($_POST['tempat_lahir'] ?? ''),
        'tanggal_lahir'         => $_POST['tanggal_lahir'] ?? '',
        'jenis_kelamin'         => $_POST['jenis_kelamin'] ?? '',
        'agama'                 => $_POST['agama'] ?? '',
        'pendidikan'            => $_POST['pendidikan'] ?? '',
        'pekerjaan'             => trim($_POST['pekerjaan'] ?? ''),
        'status_kawin'          => $_POST['status_kawin'] ?? '',
        'alamat'                => trim($_POST['alamat'] ?? ''),
        'rt'                    => trim($_POST['rt'] ?? ''),
        'rw'                    => trim($_POST['rw'] ?? ''),
        'kelurahan_id'          => trim($_POST['kelurahan_id'] ?? ''),
        'kode_pos'              => trim($_POST['kode_pos'] ?? ''),
        'no_telepon'            => trim($_POST['no_telepon'] ?? ''),
        'no_kk'                 => trim($_POST['no_kk'] ?? ''),
        'nama_kepala_keluarga'  => trim($_POST['nama_kepala_keluarga'] ?? ''),
        'jumlah_anggota_keluarga' => (int)($_POST['jumlah_anggota_keluarga'] ?? 1),
        'penghasilan_bulanan'   => (float)str_replace(['.', ','], ['', '.'], $_POST['penghasilan_bulanan'] ?? '0'),
        'kategori_kemiskinan'   => $_POST['kategori_kemiskinan'] ?? '',
        'kondisi_rumah'         => $_POST['kondisi_rumah'] ?? '',
    ];

    // Validasi
    if (strlen($data['nik']) !== 16) $errors[] = 'NIK harus 16 digit';
    if (empty($data['nama_lengkap'])) $errors[] = 'Nama lengkap wajib diisi';
    if (empty($data['alamat'])) $errors[] = 'Alamat wajib diisi';
    if (empty($data['kelurahan_id'])) $errors[] = 'Kelurahan wajib diisi';
    $existing = dbFetchOne("SELECT id FROM peserta WHERE nik = ?", [$data['nik']]);
    if ($existing) $errors[] = 'NIK sudah terdaftar dalam sistem';

    // Upload foto
    $fotoKtp = $fotoKk = $fotoPeserta = null;
    if (!empty($_FILES['foto_ktp']['name'])) {
        $r = uploadFile('foto_ktp', 'ktp', ALLOWED_IMG_TYPES);
        if ($r['success']) $fotoKtp = $r['filename']; else $errors[] = 'Foto KTP: ' . $r['message'];
    }
    if (!empty($_FILES['foto_kk']['name'])) {
        $r = uploadFile('foto_kk', 'kk', ALLOWED_IMG_TYPES);
        if ($r['success']) $fotoKk = $r['filename']; else $errors[] = 'Foto KK: ' . $r['message'];
    }
    if (!empty($_FILES['foto_peserta']['name'])) {
        $r = uploadFile('foto_peserta', 'peserta', ALLOWED_IMG_TYPES);
        if ($r['success']) $fotoPeserta = $r['filename']; else $errors[] = 'Foto Peserta: ' . $r['message'];
    }

    if (empty($errors)) {
        $db = getDB();
        
        // Prepare variables
        $tglLahir = $data['tanggal_lahir'] ?: null;
        $kelurahId = (int)$data['kelurahan_id'] ?: null;
        $jmlAnggota = (int)$data['jumlah_anggota_keluarga'];
        $penghasilan = (float)$data['penghasilan_bulanan'];
        $userId = (int)$currentUser['id'];
        
        // Escape and prepare values
        $nik = $db->real_escape_string($data['nik']);
        $nama = $db->real_escape_string($data['nama_lengkap']);
        $tempat = $db->real_escape_string($data['tempat_lahir']);
        $jk = $db->real_escape_string($data['jenis_kelamin']);
        $agama = $db->real_escape_string($data['agama']);
        $pend = $db->real_escape_string($data['pendidikan']);
        $kerja = $db->real_escape_string($data['pekerjaan']);
        $kawin = $db->real_escape_string($data['status_kawin']);
        $alamat = $db->real_escape_string($data['alamat']);
        $rt = $db->real_escape_string($data['rt']);
        $rw = $db->real_escape_string($data['rw']);
        $kodpos = $db->real_escape_string($data['kode_pos']);
        $telp = $db->real_escape_string($data['no_telepon']);
        $kk = $db->real_escape_string($data['no_kk']);
        $kepkk = $db->real_escape_string($data['nama_kepala_keluarga']);
        $kategori = $db->real_escape_string($data['kategori_kemiskinan']);
        $rumah = $db->real_escape_string($data['kondisi_rumah']);
        
        $sql = "INSERT INTO peserta (nik, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, agama, pendidikan, pekerjaan, status_kawin, alamat, rt, rw, kelurahan_id, kode_pos, no_telepon, no_kk, nama_kepala_keluarga, jumlah_anggota_keluarga, penghasilan_bulanan, kategori_kemiskinan, kondisi_rumah, foto_ktp, foto_kk, foto_peserta, ditambahkan_oleh) VALUES ('$nik', '$nama', '$tempat', ".($tglLahir ? "'$tglLahir'" : "NULL").", '$jk', '$agama', '$pend', '$kerja', '$kawin', '$alamat', '$rt', '$rw', ".$kelurahId.", '$kodpos', '$telp', '$kk', '$kepkk', $jmlAnggota, $penghasilan, '$kategori', '$rumah', ".($fotoKtp ? "'$fotoKtp'" : "NULL").", ".($fotoKk ? "'$fotoKk'" : "NULL").", ".($fotoPeserta ? "'$fotoPeserta'" : "NULL").", $userId)";
        
        if (!$db->query($sql)) {
            $errors[] = 'Error inserting data: ' . $db->error;
        } else {
            $id = $db->insert_id;
            if ($id > 0) {
                logAktivitas('Tambah Peserta', 'Menambahkan peserta: ' . $data['nama_lengkap'] . ' (' . $data['nik'] . ')');
                header('Location: index.php?success=' . urlencode('Peserta berhasil ditambahkan'));
                exit;
            } else {
                $errors[] = 'Gagal menyimpan data peserta';
            }
        }
    }
}

$pageTitle = 'Tambah Peserta BLT';
$kelurahans = dbFetchAll("SELECT * FROM kelurahan ORDER BY nama_kelurahan");
require_once '../../includes/header.php';
?>

<div class="page-header">
    <h5><i class="bi bi-person-plus-fill me-2"></i>Tambah Peserta BLT</h5>
    <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Terdapat kesalahan:</strong>
    <ul class="mb-0 mt-2"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <!-- DATA PRIBADI -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person-badge-fill"></i> Data Pribadi</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">NIK <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nik" value="<?= sanitize($data['nik'] ?? '') ?>" maxlength="16" pattern="\d{16}" required placeholder="16 digit NIK">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nomor KK</label>
                    <input type="text" class="form-control" name="no_kk" value="<?= sanitize($data['no_kk'] ?? '') ?>" maxlength="16">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nama Kepala Keluarga</label>
                    <input type="text" class="form-control" name="nama_kepala_keluarga" value="<?= sanitize($data['nama_kepala_keluarga'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nama_lengkap" value="<?= sanitize($data['nama_lengkap'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis Kelamin</label>
                    <select class="form-select" name="jenis_kelamin">
                        <option value="L" <?= ($data['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= ($data['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Agama</label>
                    <select class="form-select" name="agama">
                        <?php foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag): ?>
                        <option value="<?= $ag ?>" <?= ($data['agama'] ?? '') === $ag ? 'selected' : '' ?>><?= $ag ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tempat Lahir</label>
                    <input type="text" class="form-control" name="tempat_lahir" value="<?= sanitize($data['tempat_lahir'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" class="form-control" name="tanggal_lahir" value="<?= sanitize($data['tanggal_lahir'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status Kawin</label>
                    <select class="form-select" name="status_kawin">
                        <?php foreach (['Belum Kawin','Kawin','Cerai Hidup','Cerai Mati'] as $sk): ?>
                        <option value="<?= $sk ?>" <?= ($data['status_kawin'] ?? '') === $sk ? 'selected' : '' ?>><?= $sk ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pendidikan</label>
                    <select class="form-select" name="pendidikan">
                        <?php foreach (['Tidak Sekolah','SD/Sederajat','SMP/Sederajat','SMA/Sederajat','D3','S1','S2','S3'] as $pd): ?>
                        <option value="<?= $pd ?>" <?= ($data['pendidikan'] ?? '') === $pd ? 'selected' : '' ?>><?= $pd ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pekerjaan</label>
                    <input type="text" class="form-control" name="pekerjaan" value="<?= sanitize($data['pekerjaan'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" class="form-control" name="no_telepon" value="<?= sanitize($data['no_telepon'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ALAMAT -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-geo-alt-fill"></i> Alamat</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="alamat" rows="2" required><?= sanitize($data['alamat'] ?? '') ?></textarea>
                </div>
                <div class="col-md-2">
                    <label class="form-label">RT</label>
                    <input type="text" class="form-control" name="rt" value="<?= sanitize($data['rt'] ?? '') ?>" maxlength="5">
                </div>
                <div class="col-md-2">
                    <label class="form-label">RW</label>
                    <input type="text" class="form-control" name="rw" value="<?= sanitize($data['rw'] ?? '') ?>" maxlength="5">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Kelurahan/Kampung/Jorong <span class="text-danger">*</span></label>
                    <select class="form-select" name="kelurahan_id" required>
                        <option value="">— Pilih Kelurahan —</option>
                        <?php foreach ($kelurahans as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= ($data['kelurahan_id'] ?? '') == $k['id'] ? 'selected' : '' ?>><?= sanitize($k['nama_kelurahan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" class="form-control" name="kode_pos" value="<?= sanitize($data['kode_pos'] ?? '') ?>" maxlength="10">
                </div>
            </div>
        </div>
    </div>

    <!-- DATA EKONOMI -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-cash-stack"></i> Data Ekonomi & Sosial</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Jml Anggota Keluarga</label>
                    <input type="number" class="form-control" name="jumlah_anggota_keluarga" value="<?= sanitize($data['jumlah_anggota_keluarga'] ?? '1') ?>" min="1" max="20">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Penghasilan/Bulan (Rp)</label>
                    <input type="text" class="form-control" name="penghasilan_bulanan" value="<?= sanitize($data['penghasilan_bulanan'] ?? '0') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kategori Kemiskinan</label>
                    <select class="form-select" name="kategori_kemiskinan">
                        <?php foreach (['Sangat Miskin','Miskin','Hampir Miskin'] as $km): ?>
                        <option value="<?= $km ?>" <?= ($data['kategori_kemiskinan'] ?? '') === $km ? 'selected' : '' ?>><?= $km ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kondisi Rumah</label>
                    <select class="form-select" name="kondisi_rumah">
                        <?php foreach (['Tidak Layak','Kurang Layak','Layak'] as $kr): ?>
                        <option value="<?= $kr ?>" <?= ($data['kondisi_rumah'] ?? '') === $kr ? 'selected' : '' ?>><?= $kr ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- UPLOAD FOTO -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-camera-fill"></i> Upload Foto Dokumen</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Foto KTP</label>
                    <input type="file" class="form-control" name="foto_ktp" accept="image/*">
                    <small class="text-muted">JPG/PNG, maks 5MB</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Foto Kartu Keluarga</label>
                    <input type="file" class="form-control" name="foto_kk" accept="image/*">
                    <small class="text-muted">JPG/PNG, maks 5MB</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Foto Peserta</label>
                    <input type="file" class="form-control" name="foto_peserta" accept="image/*">
                    <small class="text-muted">JPG/PNG, maks 5MB</small>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="index.php" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Peserta</button>
    </div>
</form>

<?php require_once '../../includes/footer.php'; ?>
