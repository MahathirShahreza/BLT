<?php
$pageTitle = 'Sub Kriteria SAW';
require_once __DIR__ . '/../../includes/functions.php';
startSession();
requireRole(['admin']);

$db = getDB();

/* ─── helper JSON ─── */
function sendJson($arr, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

/* ════════════════════════════════════════════
   HAPUS SUB-KRITERIA
════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id = (int)$_POST['hapus_id'];
    $stmt = $db->prepare("DELETE FROM saw_sub_kriteria WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute() ? sendJson(['status'=>'success','message'=>'Sub kriteria berhasil dihapus']) : sendJson(['status'=>'error','message'=>$stmt->error], 500);
}

/* ════════════════════════════════════════════
   UPDATE / EDIT SUB-KRITERIA
════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = (int)$_POST['edit_id'];
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $nilai_sub = (int)($_POST['nilai_sub'] ?? 1);

    if (!$deskripsi || $nilai_sub < 1 || $nilai_sub > 5) {
        sendJson(['status'=>'error','message'=>'Lengkapi semua field dengan benar'], 400);
    }

    $stmt = $db->prepare("UPDATE saw_sub_kriteria SET deskripsi=?, nilai_sub=? WHERE id=?");
    $stmt->bind_param("sii", $deskripsi, $nilai_sub, $id);
    $stmt->execute() ? sendJson(['status'=>'success','message'=>'Sub kriteria berhasil diupdate']) : sendJson(['status'=>'error','message'=>$stmt->error], 500);
}

/* ════════════════════════════════════════════
   SIMPAN SUB-KRITERIA BARU
════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'simpan') {
    $kriteria_id = (int)($_POST['kriteria_id'] ?? 0);
    $kode_sub   = strtoupper(trim($_POST['kode_sub'] ?? ''));
    $deskripsi  = trim($_POST['deskripsi'] ?? '');
    $nilai_sub  = (int)($_POST['nilai_sub'] ?? 1);

    if (!$kriteria_id || !$deskripsi || $nilai_sub < 1 || $nilai_sub > 5) {
        sendJson(['status'=>'error','message'=>'Lengkapi semua field wajib'], 400);
    }

    // cek duplikat nilai_sub untuk kriteria_id yang sama
    $cek = $db->prepare("SELECT id FROM saw_sub_kriteria WHERE kriteria_id=? AND nilai_sub=?");
    $cek->bind_param("ii", $kriteria_id, $nilai_sub);
    $cek->execute(); $cek->store_result();
    if ($cek->num_rows > 0) { $cek->close(); sendJson(['status'=>'error','message'=>'Nilai sudah ada untuk kriteria ini!'], 400); }
    $cek->close();

    // urutan = max+1 untuk kriteria ini
    $maxUrut = $db->prepare("SELECT COALESCE(MAX(urutan),0)+1 AS u FROM saw_sub_kriteria WHERE kriteria_id=?");
    $maxUrut->bind_param("i", $kriteria_id);
    $maxUrut->execute();
    $urut = $maxUrut->get_result()->fetch_assoc()['u'];
    $maxUrut->close();

    $stmt = $db->prepare("INSERT INTO saw_sub_kriteria (kriteria_id, kode_sub, deskripsi, nilai_sub, urutan) VALUES (?,?,?,?,?)");
    $stmt->bind_param("issii", $kriteria_id, $kode_sub, $deskripsi, $nilai_sub, $urut);
    $stmt->execute() ? sendJson(['status'=>'success','message'=>'Sub kriteria berhasil disimpan']) : sendJson(['status'=>'error','message'=>$stmt->error], 500);
}

/* ════════════════════════════════════════════
   LOAD SUB-KRITERIA PER KRITERIA (AJAX)
════════════════════════════════════════════ */
if (($_GET['load'] ?? '') === 'sub_kriteria') {
    $kriteria_id = (int)$_GET['kriteria_id'];
    $subs = $db->prepare("
        SELECT id, kode_sub, deskripsi, nilai_sub, urutan, is_active
        FROM saw_sub_kriteria
        WHERE kriteria_id = ?
        ORDER BY urutan ASC
    ");
    $subs->bind_param("i", $kriteria_id);
    $subs->execute();
    $rows = $subs->get_result()->fetch_all(MYSQLI_ASSOC);
    $subs->close();

    if (empty($rows)) {
        echo '<p class="text-muted text-center py-4">Belum ada sub kriteria untuk kriteria ini.</p>';
        exit;
    }

    echo '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
    echo '<thead class="table-secondary"><tr><th>#</th><th>Kode</th><th>Deskripsi</th><th>Nilai</th><th>Urutan</th><th class="text-center">Aksi</th></tr></thead><tbody>';
    foreach ($rows as $i => $s) {
        $badgeAktif = $s['is_active'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>';
        echo "<tr>
            <td>".($i+1)."</td>
            <td><strong>{$s['kode_sub']}</strong></td>
            <td>{$s['deskripsi']}</td>
            <td><span class='badge bg-info'>{$s['nilai_sub']}</span></td>
            <td>{$s['urutan']}</td>
            <td>
                <div class='btn-group btn-group-sm' role='group'>
                    <button type='button' class='btn btn-warning' onclick='editSubKriteria({$s['id']},\"{$s['deskripsi']}\",{$s['nilai_sub']})' title='Edit'><i class='bi bi-pencil-square'></i> Edit</button>
                    <button type='button' class='btn btn-danger' onclick='hapusSubKriteria({$s['id']})' title='Hapus'><i class='bi bi-trash'></i> Hapus</button>
                </div>
            </td>
        </tr>";
    }
    echo '</tbody></table></div>';
    exit;
}

/* ════════════════════════════════════════════
   HALAMAN HTML
════════════════════════════════════════════ */
$kriteria_list = $db->query("SELECT * FROM saw_kriteria WHERE is_active=1 ORDER BY urutan ASC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-list-check"></i> Sub Kriteria SAW</h4>
    <small class="text-muted">Kelola deskripsi dan nilai untuk setiap sub kriteria penilaian</small>
</div>

<?php if (empty($kriteria_list)): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill"></i> Belum ada kriteria. Silakan tambah kriteria di <a href="kriteria.php">Kelola Kriteria</a> terlebih dahulu.
</div>
<?php else: ?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Daftar Sub Kriteria</h5>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary" onclick="bukaTambahSubKriteria()">
                    <i class="bi bi-plus-circle"></i> Tambah Sub Kriteria
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Pilih Kriteria</label>
                <select id="selectKriteria" class="form-select" onchange="loadSubKriteria()">
                    <option value="">-- Pilih Kriteria --</option>
                    <?php foreach ($kriteria_list as $k): ?>
                    <option value="<?= $k['id'] ?>"><?= $k['kode_kriteria'] ?> - <?= $k['nama_kriteria'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-3">
    <div class="card-body">
        <div id="daftarSubKriteria">
            <p class="text-muted text-center py-4">Pilih kriteria untuk melihat sub kriterianya</p>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- MODAL TAMBAH SUB-KRITERIA -->
<div class="modal fade" id="modalTambahSub" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Sub Kriteria Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTambahSub" onsubmit="simpanSubKriteria(event)">
                <div class="modal-body gap-3">
                    <div>
                        <label class="form-label fw-semibold">Pilih Kriteria</label>
                        <select id="inputKriteria" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <?php foreach ($kriteria_list as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= $k['kode_kriteria'] ?> - <?= $k['nama_kriteria'] ?> (Bobot: <?= number_format($k['bobot'], 4) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Kode Sub Kriteria (Opsional)</label>
                        <input type="text" id="inputKodeSub" class="form-control" placeholder="Contoh: C1.1">
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Deskripsi *</label>
                        <textarea id="inputDeskripsi" class="form-control" rows="3" placeholder="Deskripsi kondisi/indikator" required></textarea>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Nilai (1-5) *</label>
                        <select id="inputNilaiSub" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <option value="5">5 - Sangat Layak / Paling Berat</option>
                            <option value="4">4 - Layak / Berat</option>
                            <option value="3">3 - Cukup Layak / Sedang</option>
                            <option value="2">2 - Kurang Layak / Ringan</option>
                            <option value="1">1 - Tidak Layak / Paling Ringan</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT SUB-KRITERIA -->
<div class="modal fade" id="modalEditSub" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Sub Kriteria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditSub" onsubmit="updateSubKriteria(event)">
                <div class="modal-body gap-3">
                    <input type="hidden" id="editSubId">
                    <div>
                        <label class="form-label fw-semibold">Deskripsi *</label>
                        <textarea id="editDeskripsi" class="form-control" rows="3" required></textarea>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Nilai (1-5) *</label>
                        <select id="editNilaiSub" class="form-select" required>
                            <option value="5">5 - Sangat Layak / Paling Berat</option>
                            <option value="4">4 - Layak / Berat</option>
                            <option value="3">3 - Cukup Layak / Sedang</option>
                            <option value="2">2 - Kurang Layak / Ringan</option>
                            <option value="1">1 - Tidak Layak / Paling Ringan</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
function bukaTambahSubKriteria() {
    document.getElementById('formTambahSub').reset();
    new bootstrap.Modal(document.getElementById('modalTambahSub')).show();
}

function loadSubKriteria() {
    const kriteria_id = document.getElementById('selectKriteria').value;
    if (!kriteria_id) {
        document.getElementById('daftarSubKriteria').innerHTML = '<p class="text-muted text-center py-4">Pilih kriteria untuk melihat sub kriterianya</p>';
        return;
    }
    document.getElementById('daftarSubKriteria').innerHTML = '<p class="text-muted"><i class="bi bi-hourglass-split"></i> Memuat...</p>';
    fetch(`sub_kriteria.php?load=sub_kriteria&kriteria_id=${kriteria_id}`,{credentials:'include'})
        .then(r=>r.text())
        .then(html=>document.getElementById('daftarSubKriteria').innerHTML=html)
        .catch(()=>document.getElementById('daftarSubKriteria').innerHTML='<p class="text-danger">Gagal memuat</p>');
}

function simpanSubKriteria(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action', 'simpan');
    fd.append('kriteria_id', document.getElementById('inputKriteria').value);
    fd.append('kode_sub', document.getElementById('inputKodeSub').value);
    fd.append('deskripsi', document.getElementById('inputDeskripsi').value);
    fd.append('nilai_sub', document.getElementById('inputNilaiSub').value);

    fetch('sub_kriteria.php',{method:'POST',body:fd,credentials:'include'})
        .then(r=>r.json()).then(resp=>{
            if(resp.status==='success'){
                alert('✅ '+resp.message);
                bootstrap.Modal.getInstance(document.getElementById('modalTambahSub')).hide();
                loadSubKriteria();
            } else alert('❌ '+resp.message);
        }).catch(()=>alert('Terjadi kesalahan'));
}

function editSubKriteria(id, deskripsi, nilai) {
    document.getElementById('editSubId').value = id;
    document.getElementById('editDeskripsi').value = deskripsi;
    document.getElementById('editNilaiSub').value = nilai;
    new bootstrap.Modal(document.getElementById('modalEditSub')).show();
}

function updateSubKriteria(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('edit_id', document.getElementById('editSubId').value);
    fd.append('deskripsi', document.getElementById('editDeskripsi').value);
    fd.append('nilai_sub', document.getElementById('editNilaiSub').value);

    fetch('sub_kriteria.php',{method:'POST',body:fd,credentials:'include'})
        .then(r=>r.json()).then(resp=>{
            if(resp.status==='success'){
                alert('✅ '+resp.message);
                bootstrap.Modal.getInstance(document.getElementById('modalEditSub')).hide();
                loadSubKriteria();
            } else alert('❌ '+resp.message);
        }).catch(()=>alert('Terjadi kesalahan'));
}

function hapusSubKriteria(id) {
    if(!confirm('Hapus sub kriteria ini?')) return;
    const fd = new FormData();
    fd.append('hapus_id', id);
    fetch('sub_kriteria.php',{method:'POST',body:fd,credentials:'include'})
        .then(r=>r.json()).then(resp=>{
            if(resp.status==='success'){
                alert('✅ '+resp.message);
                loadSubKriteria();
            } else alert('❌ '+resp.message);
        }).catch(()=>alert('Terjadi kesalahan'));
}
</script>
