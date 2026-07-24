<?php
require_once '../../includes/functions.php';
startSession();
requireLogin();
requireRole(['admin', 'sekretaris']);
$currentUser = getCurrentUser();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$peserta = dbFetchOne("SELECT * FROM peserta WHERE id=?", [$id]);
if (!$peserta) { header('Location: index.php?error=Peserta+tidak+ditemukan'); exit; }

// Ambil daftar kelurahan
$kelurahanList = dbFetchAll("SELECT id, nama_kelurahan FROM kelurahan ORDER BY nama_kelurahan");

$errors = [];

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
        'kelurahan_id'          => !empty($_POST['kelurahan_id']) ? (int)$_POST['kelurahan_id'] : null,
        'kode_pos'              => trim($_POST['kode_pos'] ?? ''),
        'no_telepon'            => trim($_POST['no_telepon'] ?? ''),
        'no_kk'                 => trim($_POST['no_kk'] ?? ''),
        'nama_kepala_keluarga'  => trim($_POST['nama_kepala_keluarga'] ?? ''),
        'jumlah_anggota_keluarga' => (int)($_POST['jumlah_anggota_keluarga'] ?? 1),
        'penghasilan_bulanan'   => (float)str_replace(['.', ','], ['', '.'], $_POST['penghasilan_bulanan'] ?? '0'),
        'kategori_kemiskinan'   => $_POST['kategori_kemiskinan'] ?? '',
        'kondisi_rumah'         => $_POST['kondisi_rumah'] ?? '',
    ];

    if (strlen($data['nik']) !== 16) $errors[] = 'NIK harus 16 digit';
    if (empty($data['nama_lengkap'])) $errors[] = 'Nama lengkap wajib diisi';
    if (empty($data['alamat'])) $errors[] = 'Alamat wajib diisi';
    
    // Validasi kelurahan_id
    if ($data['kelurahan_id']) {
        $checkKel = dbFetchOne("SELECT id FROM kelurahan WHERE id=?", [$data['kelurahan_id']]);
        if (!$checkKel) $errors[] = 'Kelurahan yang dipilih tidak valid';
    }

    // Cek NIK duplikat (bukan diri sendiri)
    $existNik = dbFetchOne("SELECT id FROM peserta WHERE nik=? AND id != ?", [$data['nik'], $id]);
    if ($existNik) $errors[] = 'NIK sudah digunakan peserta lain';

    // Upload foto baru jika ada
    $fotoKtp     = $peserta['foto_ktp'];
    $fotoKk      = $peserta['foto_kk'];
    $fotoPeserta = $peserta['foto_peserta'];

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
        $stmt = $db->prepare("UPDATE peserta SET nik=?, nama_lengkap=?, tempat_lahir=?, tanggal_lahir=?, jenis_kelamin=?, agama=?, pendidikan=?, pekerjaan=?, status_kawin=?, alamat=?, rt=?, rw=?, kelurahan_id=?, kode_pos=?, no_telepon=?, no_kk=?, nama_kepala_keluarga=?, jumlah_anggota_keluarga=?, penghasilan_bulanan=?, kategori_kemiskinan=?, kondisi_rumah=?, foto_ktp=?, foto_kk=?, foto_peserta=? WHERE id=?");
        
        if (!$stmt) {
            $errors[] = 'Error preparing statement: ' . $db->error;
        } else {
            // Prepare variables for bind_param (must be variables, not values)
            // 25 params: nik(s), nama(s), tempat(s), tgl(s), jk(s), agama(s), pend(s), kerja(s), kawin(s), alamat(s), rt(s), rw(s), kel(i), kode(s), telp(s), kk(s), kepkk(s), jml(i), penghasilan(d), kategori(s), rumah(s), fktp(s), fkk(s), fpeserta(s), id(i)
            $tglLahir = $data['tanggal_lahir'] ?: null;
            $typesStr = 'ssssssssssssisssidssssssi';
            
            $stmt->bind_param($typesStr,
                $data['nik'], $data['nama_lengkap'], $data['tempat_lahir'], 
                $tglLahir, $data['jenis_kelamin'], $data['agama'], 
                $data['pendidikan'], $data['pekerjaan'], $data['status_kawin'], 
                $data['alamat'], $data['rt'], $data['rw'], $data['kelurahan_id'], 
                $data['kode_pos'], $data['no_telepon'], $data['no_kk'], 
                $data['nama_kepala_keluarga'], $data['jumlah_anggota_keluarga'], 
                $data['penghasilan_bulanan'], $data['kategori_kemiskinan'], $data['kondisi_rumah'], 
                $fotoKtp, $fotoKk, $fotoPeserta, $id);
            
            if (!$stmt->execute()) {
                $errors[] = 'Error updating data: ' . $stmt->error;
            } else {
                if ($stmt->affected_rows > 0) {
                    logAktivitas('Edit Peserta', 'Edit: ' . $data['nama_lengkap'] . ' (' . $data['nik'] . ')');
                    header('Location: detail.php?id=' . $id . '&success=' . urlencode('Data peserta berhasil diperbarui'));
                    exit;
                } else {
                    $errors[] = 'Tidak ada perubahan data atau data tidak ditemukan';
                }
            }
        }
    }
    // Pakai data POST untuk re-fill form
    $peserta = array_merge($peserta, $data);
}

$pageTitle = 'Edit Peserta';
require_once '../../includes/header.php';
?>

<div class="page-header">
    <h5><i class="bi bi-pencil-square me-2"></i>Edit Peserta</h5>
    <a href="detail.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Terdapat kesalahan:</strong>
    <ul class="mb-0 mt-2"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-person-badge-fill"></i> Data Pribadi</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">NIK <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nik" value="<?= sanitize($peserta['nik']) ?>" maxlength="16" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. KK</label>
                    <input type="text" class="form-control" name="no_kk" value="<?= sanitize($peserta['no_kk'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nama Kepala Keluarga</label>
                    <input type="text" class="form-control" name="nama_kepala_keluarga" value="<?= sanitize($peserta['nama_kepala_keluarga'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nama_lengkap" value="<?= sanitize($peserta['nama_lengkap']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis Kelamin</label>
                    <select class="form-select" name="jenis_kelamin">
                        <option value="L" <?= $peserta['jenis_kelamin']==='L'?'selected':'' ?>>Laki-laki</option>
                        <option value="P" <?= $peserta['jenis_kelamin']==='P'?'selected':'' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Agama</label>
                    <select class="form-select" name="agama">
                        <?php foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag): ?>
                        <option value="<?= $ag ?>" <?= $peserta['agama']===$ag?'selected':'' ?>><?= $ag ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tempat Lahir</label>
                    <input type="text" class="form-control" name="tempat_lahir" value="<?= sanitize($peserta['tempat_lahir'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" class="form-control" name="tanggal_lahir" value="<?= sanitize($peserta['tanggal_lahir'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status Kawin</label>
                    <select class="form-select" name="status_kawin">
                        <?php foreach (['Belum Kawin','Kawin','Cerai Hidup','Cerai Mati'] as $sk): ?>
                        <option value="<?= $sk ?>" <?= $peserta['status_kawin']===$sk?'selected':'' ?>><?= $sk ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pendidikan</label>
                    <select class="form-select" name="pendidikan">
                        <?php foreach (['Tidak Sekolah','SD/Sederajat','SMP/Sederajat','SMA/Sederajat','D3','S1','S2','S3'] as $pd): ?>
                        <option value="<?= $pd ?>" <?= $peserta['pendidikan']===$pd?'selected':'' ?>><?= $pd ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pekerjaan</label>
                    <input type="text" class="form-control" name="pekerjaan" value="<?= sanitize($peserta['pekerjaan'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" class="form-control" name="no_telepon" value="<?= sanitize($peserta['no_telepon'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-geo-alt-fill"></i> Alamat</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Alamat Lengkap <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="alamat" rows="2" required><?= sanitize($peserta['alamat']) ?></textarea>
                </div>
                <div class="col-md-2"><label class="form-label">RT</label><input type="text" class="form-control" name="rt" value="<?= sanitize($peserta['rt'] ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label">RW</label><input type="text" class="form-control" name="rw" value="<?= sanitize($peserta['rw'] ?? '') ?>"></div>
                <div class="col-md-8">
                    <label class="form-label">Kelurahan/Kampung/Jorong <span class="text-danger">*</span></label>
                    <select class="form-select" name="kelurahan_id" required>
                        <option value="">-- Pilih Kelurahan --</option>
                        <?php foreach ($kelurahanList as $kel): ?>
                        <option value="<?= $kel['id'] ?>" <?= $peserta['kelurahan_id']==$kel['id']?'selected':'' ?>><?= sanitize($kel['nama_kelurahan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-cash-stack"></i> Data Ekonomi</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Jml Anggota KK</label><input type="number" class="form-control" name="jumlah_anggota_keluarga" value="<?= $peserta['jumlah_anggota_keluarga'] ?>" min="1"></div>
                <div class="col-md-3"><label class="form-label">Penghasilan/Bulan (Rp)</label><input type="text" class="form-control" name="penghasilan_bulanan" value="<?= $peserta['penghasilan_bulanan'] ?>"></div>
                <div class="col-md-3">
                    <label class="form-label">Kategori Kemiskinan</label>
                    <select class="form-select" name="kategori_kemiskinan">
                        <?php foreach (['Sangat Miskin','Miskin','Hampir Miskin'] as $km): ?>
                        <option value="<?= $km ?>" <?= $peserta['kategori_kemiskinan']===$km?'selected':'' ?>><?= $km ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kondisi Rumah</label>
                    <select class="form-select" name="kondisi_rumah">
                        <?php foreach (['Tidak Layak','Kurang Layak','Layak'] as $kr): ?>
                        <option value="<?= $kr ?>" <?= $peserta['kondisi_rumah']===$kr?'selected':'' ?>><?= $kr ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="detail.php?id=<?= $id ?>" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-warning"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
    </div>
</form>

<?php require_once '../../includes/footer.php'; ?>
