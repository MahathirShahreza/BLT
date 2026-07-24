<?php
$pageTitle = 'Kriteria SAW';
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

/* ─── cek total bobot (kecuali id yg sedang diedit) ─── */
function cekTotalBobot($db, $bobot_baru, $id_edit = null) {
    if ($id_edit) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(bobot),0) AS total FROM saw_kriteria WHERE id != ?");
        $stmt->bind_param("i", $id_edit);
    } else {
        $stmt = $db->prepare("SELECT COALESCE(SUM(bobot),0) AS total FROM saw_kriteria");
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return floatval($row['total']) + floatval($bobot_baru);
}

/* ════════════════════════════════════════════
   HAPUS
════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id = (int)$_POST['hapus_id'];
    // Cek apakah sudah ada penilaian yang memakai kriteria ini
    $cekPakai = $db->prepare("SELECT COUNT(*) AS c FROM saw_penilaian WHERE kriteria_id=?");
    $cekPakai->bind_param("i", $id);
    $cekPakai->execute();
    $jmlPakai = $cekPakai->get_result()->fetch_assoc()['c'];
    $cekPakai->close();
    if ($jmlPakai > 0) {
        sendJson(['status'=>'error','message'=>'Kriteria sudah digunakan dalam penilaian, tidak dapat dihapus.'], 400);
    }
    $stmt = $db->prepare("DELETE FROM saw_kriteria WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute() ? sendJson(['status'=>'success','message'=>'Kriteria berhasil dihapus']) : sendJson(['status'=>'error','message'=>$stmt->error], 500);
}

/* ════════════════════════════════════════════
   SIMPAN BARU
════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'simpan') {
    $kode  = strtoupper(trim($_POST['kode_kriteria'] ?? ''));
    $nama  = trim($_POST['nama_kriteria'] ?? '');
    $bobot = floatval($_POST['bobot'] ?? 0);
    $jenis = trim($_POST['jenis'] ?? '');
    $ket   = trim($_POST['keterangan'] ?? '');

    if (!$kode || !$nama || !$jenis) sendJson(['status'=>'error','message'=>'Lengkapi semua field wajib'], 400);

    // cek duplikat kode
    $cek = $db->prepare("SELECT id FROM saw_kriteria WHERE kode_kriteria=?");
    $cek->bind_param("s", $kode);
    $cek->execute(); $cek->store_result();
    if ($cek->num_rows > 0) { $cek->close(); sendJson(['status'=>'error','message'=>'Kode kriteria sudah ada!'], 400); }
    $cek->close();

    $total = cekTotalBobot($db, $bobot);
    if (round($total, 4) > 1.0001) sendJson(['status'=>'error','message'=>'Total bobot melebihi 1.00 (sekarang '.number_format($total,4).')'], 400);

    // urutan = max+1
    $maxUrut = $db->query("SELECT COALESCE(MAX(urutan),0)+1 AS u FROM saw_kriteria")->fetch_assoc()['u'];

    $stmt = $db->prepare("INSERT INTO saw_kriteria (kode_kriteria,nama_kriteria,bobot,jenis,keterangan,urutan) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssdsis", $kode, $nama, $bobot, $jenis, $ket, $maxUrut);
    $stmt->execute() ? sendJson(['status'=>'success','message'=>'Kriteria berhasil disimpan']) : sendJson(['status'=>'error','message'=>$stmt->error], 500);
}

/* ════════════════════════════════════════════
   UPDATE
════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id    = (int)$_POST['id'];
    $nama  = trim($_POST['nama_kriteria'] ?? '');
    $bobot = floatval($_POST['bobot'] ?? 0);
    $jenis = trim($_POST['jenis'] ?? '');
    $ket   = trim($_POST['keterangan'] ?? '');

    if (!$nama || !$jenis) sendJson(['status'=>'error','message'=>'Lengkapi semua field wajib'], 400);

    $total = cekTotalBobot($db, $bobot, $id);
    if (round($total, 4) > 1.0001) sendJson(['status'=>'error','message'=>'Total bobot melebihi 1.00 (sekarang '.number_format($total,4).')'], 400);

    $stmt = $db->prepare("UPDATE saw_kriteria SET nama_kriteria=?,bobot=?,jenis=?,keterangan=? WHERE id=?");
    $stmt->bind_param("sdssi", $nama, $bobot, $jenis, $ket, $id);
    $stmt->execute() ? sendJson(['status'=>'success','message'=>'Kriteria berhasil diupdate']) : sendJson(['status'=>'error','message'=>$stmt->error], 500);
}

/* ════════════════════════════════════════════
   BULK UPDATE BOBOT
════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_update_bobot') {
    $updates = $_POST['updates'] ?? [];
    if (empty($updates)) sendJson(['status'=>'error','message'=>'Tidak ada data'], 400);

    $total = array_sum(array_map('floatval', $updates));
    if (abs($total - 1.0) > 0.001) sendJson(['status'=>'error','message'=>'Total bobot harus = 1.00 (sekarang '.number_format($total,4).')'], 400);

    $failed = [];
    foreach ($updates as $id => $bobot) {
        $bobot = floatval($bobot);
        $stmt = $db->prepare("UPDATE saw_kriteria SET bobot=? WHERE id=?");
        $stmt->bind_param("di", $bobot, $id);
        if (!$stmt->execute()) $failed[] = $id;
        $stmt->close();
    }
    empty($failed) ? sendJson(['status'=>'success','message'=>'Semua bobot berhasil diperbarui']) : sendJson(['status'=>'error','message'=>'Gagal update id: '.implode(', ',$failed)], 500);
}

/* ════════════════════════════════════════════
   GET EDIT ROW (AJAX)
════════════════════════════════════════════ */
if (isset($_GET['edit_id'])) {
    $id = (int)$_GET['edit_id'];
    $stmt = $db->prepare("SELECT * FROM saw_kriteria WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($row ?: []);
    exit;
}

/* ════════════════════════════════════════════
   GET ALL (AJAX)
════════════════════════════════════════════ */
if (($_GET['get_all'] ?? '') === 'json') {
    $res = $db->query("SELECT * FROM saw_kriteria ORDER BY urutan ASC");
    $data = [];
    while ($r = $res->fetch_assoc()) $data[] = $r;
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/* ════════════════════════════════════════════
   LOAD TABEL (AJAX)
════════════════════════════════════════════ */
if (($_GET['load'] ?? '') === 'tabel') {
    $totalBobot = (float)$db->query("SELECT COALESCE(SUM(bobot),0) AS t FROM saw_kriteria")->fetch_assoc()['t'];
    $warnBobot  = (abs($totalBobot - 1.0) > 0.001) ? "<div class='alert alert-warning mb-2'><i class='bi bi-exclamation-triangle-fill'></i> Total bobot saat ini = <strong>".number_format($totalBobot,4)."</strong>. Pastikan total = 1.00 agar perhitungan SAW valid.</div>" : "";

    $res = $db->query("SELECT * FROM saw_kriteria ORDER BY urutan ASC");
    $rows = '';
    $no = 1;
    while ($r = $res->fetch_assoc()) {
        $badgeJenis = $r['jenis'] === 'Benefit'
            ? '<span class="badge bg-success">Benefit</span>'
            : '<span class="badge bg-danger">Cost</span>';
        $rows .= "<tr>
            <td>{$no}</td>
            <td><strong>{$r['kode_kriteria']}</strong></td>
            <td>{$r['nama_kriteria']}</td>
            <td>{$r['bobot']}</td>
            <td>{$badgeJenis}</td>
            <td>".($r['keterangan'] ?: '-')."</td>
            <td>
                <div class='btn-group btn-group-sm' role='group'>
                    <button type='button' class='btn btn-warning' onclick='editKriteria({$r['id']})' title='Edit'><i class='bi bi-pencil-square'></i> Edit</button>
                    <button type='button' class='btn btn-danger' onclick='hapusKriteria({$r['id']})' title='Hapus'><i class='bi bi-trash'></i> Hapus</button>
                </div>
            </td>
        </tr>";
        $no++;
    }
    echo $warnBobot;
    echo "<table class='table table-bordered table-hover align-middle'>
        <thead class='table-dark'>
            <tr><th>#</th><th>Kode</th><th>Nama Kriteria</th><th>Bobot</th><th>Jenis</th><th>Keterangan</th><th class='text-center'>Aksi</th></tr>
        </thead>
        <tbody>{$rows}</tbody>
    </table>";
    exit;
}

/* ════════════════════════════════════════════
   HALAMAN HTML
════════════════════════════════════════════ */
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <h4><i class="bi bi-list-check"></i> Kelola Kriteria SAW</h4>
    <small class="text-muted">Atur kriteria dan bobot untuk perhitungan Decision Support System</small>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="mb-3 d-flex gap-2">
            <button class="btn btn-primary" onclick="tampilkanForm('simpan')">
                <i class="bi bi-plus-circle"></i> Tambah Kriteria
            </button>
            <button class="btn btn-dark" onclick="editMultipleBobot()">
                <i class="bi bi-sliders"></i> Edit Semua Bobot
            </button>
        </div>
        <div id="konten-tengah">
            <p class="text-muted"><i class="bi bi-hourglass-split"></i> Memuat data...</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
const BASE = '<?= BASE_URL ?>';

function loadTabel() {
    document.getElementById('konten-tengah').innerHTML = '<p class="text-muted"><i class="bi bi-hourglass-split"></i> Memuat data...</p>';
    fetch('kriteria.php?load=tabel', {cache:'no-store',credentials:'include'})
        .then(r => r.text())
        .then(html => document.getElementById('konten-tengah').innerHTML = html)
        .catch(() => document.getElementById('konten-tengah').innerHTML = '<p class="text-danger">Gagal memuat data</p>');
}

function tampilkanForm(mode, data = {}) {
    document.getElementById('konten-tengah').innerHTML = `
        <div class="card border-0 shadow-sm" style="max-width:520px">
            <div class="card-body">
                <h6 class="fw-bold mb-3">${mode==='simpan'?'Tambah':'Edit'} Kriteria SAW</h6>
                <form id="formKriteria">
                    <input type="hidden" name="action" value="${mode}">
                    <input type="hidden" name="id" value="${data.id||''}">
                    ${mode==='simpan' ? `
                    <div class="mb-3">
                        <label class="form-label">Kode Kriteria <span class="text-danger">*</span></label>
                        <input type="text" name="kode_kriteria" class="form-control" placeholder="C1, C2, ..." value="${data.kode_kriteria||''}" required>
                    </div>` : `<input type="hidden" name="kode_kriteria" value="${data.kode_kriteria||''}">`}
                    <div class="mb-3">
                        <label class="form-label">Nama Kriteria <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kriteria" class="form-control" value="${data.nama_kriteria||''}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bobot (0 – 1) <span class="text-danger">*</span></label>
                        <input type="number" step="0.0001" min="0" max="1" id="bobotInput" name="bobot" class="form-control" value="${data.bobot||''}" required>
                        <div id="bobotMsg" class="form-text"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jenis <span class="text-danger">*</span></label>
                        <select name="jenis" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <option value="Benefit" ${data.jenis==='Benefit'?'selected':''}>Benefit (semakin besar semakin baik)</option>
                            <option value="Cost" ${data.jenis==='Cost'?'selected':''}>Cost (semakin kecil semakin baik)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" value="${data.keterangan||''}">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" id="btnSimpan" class="btn btn-primary">Simpan</button>
                        <button type="button" class="btn btn-secondary" onclick="loadTabel()">Batal</button>
                    </div>
                </form>
            </div>
        </div>`;

    const bobotInput = document.getElementById('bobotInput');
    const bobotMsg   = document.getElementById('bobotMsg');
    const btnSimpan  = document.getElementById('btnSimpan');

    bobotInput.addEventListener('input', function() {
        const bobot = parseFloat(this.value)||0;
        const fd = new FormData();
        fd.append('action','cek_bobot'); fd.append('bobot', bobot);
        if (mode==='update') fd.append('id_edit', data.id||'');
        // hitung real-time dari get_all
        fetch('kriteria.php?get_all=json',{credentials:'include'})
            .then(r=>r.json()).then(rows=>{
                let total = rows.reduce((s,r)=>{
                    if(mode==='update' && r.id==data.id) return s;
                    return s + parseFloat(r.bobot);
                },0) + bobot;
                total = Math.round(total*10000)/10000;
                const valid = Math.abs(total-1.0)<=0.001;
                bobotMsg.innerHTML = valid
                    ? `<span class="text-success">✔ Total bobot valid = ${total.toFixed(4)}</span>`
                    : `<span class="text-danger">✖ Total bobot = ${total.toFixed(4)} (harus = 1.00)</span>`;
                btnSimpan.disabled = !valid;
            });
    });

    document.getElementById('formKriteria').onsubmit = function(e) {
        e.preventDefault();
        fetch('kriteria.php', {method:'POST', body:new FormData(this), credentials:'include'})
            .then(r=>r.json()).then(resp=>{
                if(resp.status==='success') { alert('✅ '+resp.message); loadTabel(); }
                else alert('❌ '+resp.message);
            }).catch(()=>alert('Terjadi kesalahan jaringan'));
    };
}

function editKriteria(id) {
    fetch('kriteria.php?edit_id='+id,{credentials:'include'})
        .then(r=>r.json()).then(data=>tampilkanForm('update',data))
        .catch(()=>alert('Gagal mengambil data'));
}

function hapusKriteria(id) {
    if(!confirm('Yakin ingin menghapus kriteria ini?')) return;
    const fd = new FormData(); fd.append('hapus_id',id);
    fetch('kriteria.php',{method:'POST',body:fd,credentials:'include'})
        .then(r=>r.json()).then(resp=>{
            if(resp.status==='success') { alert('✅ '+resp.message); loadTabel(); }
            else alert('❌ '+resp.message);
        });
}

function editMultipleBobot() {
    document.getElementById('konten-tengah').innerHTML = '<p class="text-muted">Memuat data...</p>';
    fetch('kriteria.php?get_all=json',{credentials:'include'})
        .then(r=>r.json()).then(data=>{
            if(!data.length){document.getElementById('konten-tengah').innerHTML='<p class="text-danger">Belum ada kriteria</p>';return;}
            let rows = data.map((r,i)=>`
                <tr style="background:${i%2?'#f9f9f9':'white'}">
                    <td><strong>${r.kode_kriteria}</strong></td>
                    <td>${r.nama_kriteria}</td>
                    <td><input type="number" step="0.0001" min="0" max="1" class="form-control form-control-sm bobot-input"
                        data-id="${r.id}" value="${r.bobot}" style="width:100px"></td>
                </tr>`).join('');

            document.getElementById('konten-tengah').innerHTML = `
                <div class="card border-0 shadow-sm" style="max-width:680px">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1"><i class="bi bi-sliders"></i> Edit Semua Bobot Sekaligus</h6>
                        <p class="text-muted small mb-3">Total bobot harus = 1.00</p>
                        <table class="table table-bordered align-middle">
                            <thead class="table-dark"><tr><th>Kode</th><th>Nama Kriteria</th><th>Bobot</th></tr></thead>
                            <tbody>${rows}</tbody>
                        </table>
                        <div id="totalMsg" class="alert alert-secondary text-center fw-bold mb-3">
                            Total Bobot: <span id="totalValue">-</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary flex-fill" id="btnSimpanMulti" onclick="simpanMultipleBobot()" disabled>
                                <i class="bi bi-save"></i> Simpan Semua Bobot
                            </button>
                            <button class="btn btn-secondary" onclick="loadTabel()">Batal</button>
                        </div>
                    </div>
                </div>`;

            document.querySelectorAll('.bobot-input').forEach(inp=>inp.addEventListener('input', hitungTotalBobot));
            hitungTotalBobot();
        });
}

function hitungTotalBobot() {
    let total = 0;
    document.querySelectorAll('.bobot-input').forEach(inp=>total+=parseFloat(inp.value)||0);
    total = Math.round(total*10000)/10000;
    const valid = Math.abs(total-1.0)<=0.001;
    const el = document.getElementById('totalValue');
    const btn = document.getElementById('btnSimpanMulti');
    if(el){ el.textContent=total.toFixed(4); el.style.color=valid?'green':'red'; }
    if(btn){ btn.disabled=!valid; btn.style.opacity=valid?'1':'0.5'; }
}

function simpanMultipleBobot() {
    const updates={};
    document.querySelectorAll('.bobot-input').forEach(inp=>{updates[inp.dataset.id]=parseFloat(inp.value)||0;});
    const total = Object.values(updates).reduce((a,b)=>a+b,0);
    if(Math.abs(total-1.0)>0.001){alert('Total bobot harus = 1.00');return;}
    const fd = new FormData(); fd.append('action','bulk_update_bobot');
    Object.keys(updates).forEach(id=>fd.append('updates['+id+']', updates[id]));
    fetch('kriteria.php',{method:'POST',body:fd,credentials:'include'})
        .then(r=>r.json()).then(resp=>{
            if(resp.status==='success'){alert('✅ '+resp.message);loadTabel();}
            else alert('❌ '+resp.message);
        });
}

document.addEventListener('DOMContentLoaded', loadTabel);
</script>
