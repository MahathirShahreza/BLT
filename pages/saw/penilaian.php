<?php
$pageTitle = 'Penilaian SAW';
require_once __DIR__ . '/../../includes/functions.php';
startSession();
requireRole(['atasan']);

$db = getDB();

/* ════════════════════════════════════════════
   SIMPAN / UPDATE PENILAIAN (AJAX POST)
════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simpan_penilaian') {
    header('Content-Type: application/json; charset=utf-8');
    $peserta_id = (int)($_POST['peserta_id'] ?? 0);
    $nilai_arr  = $_POST['nilai'] ?? []; // ['kriteria_id' => nilai]

    if (!$peserta_id || empty($nilai_arr)) {
        echo json_encode(['status'=>'error','message'=>'Data tidak lengkap']); exit;
    }

    $user = getCurrentUser();
    $errors = [];
    foreach ($nilai_arr as $kriteria_id => $nilai) {
        $kriteria_id = (int)$kriteria_id;
        $nilai = floatval($nilai);

        $stmt = $db->prepare("INSERT INTO saw_penilaian (peserta_id, kriteria_id, nilai, dinilai_oleh)
            VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE nilai=VALUES(nilai), dinilai_oleh=VALUES(dinilai_oleh), updated_at=NOW()");
        $stmt->bind_param("iidi", $peserta_id, $kriteria_id, $nilai, $user['id']);
        if (!$stmt->execute()) $errors[] = $stmt->error;
        $stmt->close();
    }

    if ($errors) {
        echo json_encode(['status'=>'error','message'=>implode('; ',$errors)]); exit;
    }

    logAktivitas('INPUT_PENILAIAN_SAW', "Peserta ID={$peserta_id}");
    echo json_encode(['status'=>'success','message'=>'Nilai berhasil disimpan']); exit;
}

/* ════════════════════════════════════════════
   GET SUB-KRITERIA PER KRITERIA (AJAX GET)
════════════════════════════════════════════ */
if (isset($_GET['get_sub_kriteria']) && isset($_GET['kriteria_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $kriteria_id = (int)$_GET['kriteria_id'];
    $res = $db->prepare("
        SELECT id, nilai_sub, deskripsi
        FROM saw_sub_kriteria
        WHERE kriteria_id = ?
        ORDER BY nilai_sub DESC
    ");
    $res->bind_param("i", $kriteria_id);
    $res->execute();
    $rows = $res->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($rows); exit;
}

/* ════════════════════════════════════════════
   GET NILAI EXISTING (AJAX GET)
════════════════════════════════════════════ */
if (isset($_GET['get_nilai']) && isset($_GET['peserta_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $peserta_id = (int)$_GET['peserta_id'];
    $res = $db->prepare("SELECT kriteria_id, nilai FROM saw_penilaian WHERE peserta_id=?");
    $res->bind_param("i", $peserta_id);
    $res->execute();
    $rows = $res->get_result()->fetch_all(MYSQLI_ASSOC);
    $data = [];
    foreach ($rows as $r) $data[$r['kriteria_id']] = $r['nilai'];
    echo json_encode($data); exit;
}

/* ════════════════════════════════════════════
   LOAD TABEL (AJAX)
════════════════════════════════════════════ */
if (($_GET['load'] ?? '') === 'tabel') {
    $search = trim($_GET['q'] ?? '');
    $filter_status = $_GET['status'] ?? '';

    $where = "WHERE p.status_verifikasi = 'disetujui' AND p.status_aktif = 'aktif'";
    $params = []; $types = '';

    if ($search) {
        $like = "%{$search}%";
        $where .= " AND (p.nama_lengkap LIKE ? OR p.nik LIKE ?)";
        $params[] = $like; $params[] = $like;
        $types .= 'ss';
    }
    if ($filter_status === 'sudah') {
        $where .= " AND (SELECT COUNT(*) FROM saw_penilaian sp WHERE sp.peserta_id=p.id) > 0";
    } elseif ($filter_status === 'belum') {
        $where .= " AND (SELECT COUNT(*) FROM saw_penilaian sp WHERE sp.peserta_id=p.id) = 0";
    }

    $sql = "SELECT p.id, p.nik, p.nama_lengkap, k.nama_kelurahan,
                   (SELECT COUNT(*) FROM saw_penilaian sp WHERE sp.peserta_id=p.id) AS jml_nilai,
                   (SELECT COUNT(*) FROM saw_kriteria) AS total_kriteria
            FROM peserta p
            LEFT JOIN kelurahan k ON p.kelurahan_id = k.id
            {$where}
            ORDER BY p.nama_lengkap ASC";

    if ($params) {
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $pesertaList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $pesertaList = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    if (empty($pesertaList)) {
        echo '<p class="text-muted text-center py-4">Tidak ada data peserta yang sudah diverifikasi.</p>';
        exit;
    }

    echo '<div class="table-responsive"><table class="table table-bordered table-hover align-middle">';
    echo '<thead class="table-dark"><tr><th>#</th><th>NIK</th><th>Nama Peserta</th><th>Kelurahan</th><th>Status Nilai</th><th>Aksi</th></tr></thead><tbody>';
    foreach ($pesertaList as $i => $p) {
        $jml   = (int)$p['jml_nilai'];
        $total = (int)$p['total_kriteria'];
        $badge = $jml >= $total && $total > 0
            ? "<span class='badge bg-success'>Sudah Dinilai ({$jml}/{$total})</span>"
            : ($jml > 0
                ? "<span class='badge bg-warning text-dark'>Sebagian ({$jml}/{$total})</span>"
                : "<span class='badge bg-secondary'>Belum Dinilai</span>");
        
        // Tentukan tombol berdasarkan status penilaian
        if ($jml > 0) {
            // Ada nilai yang sudah diisi - tampilkan tombol Edit
            $btnClass = 'btn-warning';
            $btnText = '<i class="bi bi-pencil-square"></i> Edit Nilai';
        } else {
            // Belum ada nilai - tampilkan tombol Input
            $btnClass = 'btn-primary';
            $btnText = '<i class="bi bi-plus-circle"></i> Input Nilai';
        }
        
        echo "<tr>
            <td>".($i+1)."</td>
            <td>{$p['nik']}</td>
            <td><strong>{$p['nama_lengkap']}</strong></td>
            <td>".($p['nama_kelurahan']??'-')."</td>
            <td>{$badge}</td>
            <td><button class='btn btn-sm {$btnClass}' onclick='bukaPenilaian({$p['id']},\"{$p['nama_lengkap']}\")'>
                {$btnText}
            </button></td>
        </tr>";
    }
    echo '</tbody></table></div>';
    exit;
}

/* ════════════════════════════════════════════
   HALAMAN HTML
════════════════════════════════════════════ */
$kriteria_list = $db->query("SELECT * FROM saw_kriteria ORDER BY urutan ASC")->fetch_all(MYSQLI_ASSOC);
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <h4><i class="bi bi-clipboard2-pulse"></i> Input Penilaian SAW</h4>
    <small class="text-muted">Masukkan nilai kriteria untuk setiap peserta BLT yang sudah diverifikasi</small>
</div>

<?php if (empty($kriteria_list)): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    Belum ada kriteria SAW. Minta Admin untuk mengatur kriteria terlebih dahulu di
    <a href="<?= BASE_URL ?>pages/saw/kriteria.php">Kelola Kriteria SAW</a>.
</div>
<?php else: ?>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Cari Peserta</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Nama atau NIK..." oninput="loadTabel()">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Filter Status Nilai</label>
                <select id="filterStatus" class="form-select" onchange="loadTabel()">
                    <option value="">Semua</option>
                    <option value="sudah">Sudah Dinilai</option>
                    <option value="belum">Belum Dinilai</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div id="tabelPenilaian"><p class="text-muted"><i class="bi bi-hourglass-split"></i> Memuat data...</p></div>
    </div>
</div>

<!-- MODAL INPUT NILAI -->
<div class="modal fade" id="modalPenilaian" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Input Nilai SAW</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <p class="text-muted text-center">Memuat...</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
const KRITERIA = <?= json_encode($kriteria_list) ?>;

function loadTabel() {
    const q = encodeURIComponent(document.getElementById('searchInput').value);
    const st = document.getElementById('filterStatus').value;
    document.getElementById('tabelPenilaian').innerHTML = '<p class="text-muted"><i class="bi bi-hourglass-split"></i> Memuat data...</p>';
    fetch(`penilaian.php?load=tabel&q=${q}&status=${st}`, {cache:'no-store',credentials:'include'})
        .then(r=>r.text())
        .then(html=>document.getElementById('tabelPenilaian').innerHTML=html)
        .catch(()=>document.getElementById('tabelPenilaian').innerHTML='<p class="text-danger">Gagal memuat</p>');
}

function bukaPenilaian(peserta_id, nama) {
    document.getElementById('modalBody').innerHTML='<p class="text-center text-muted"><i class="bi bi-hourglass-split"></i> Memuat nilai...</p>';
    new bootstrap.Modal(document.getElementById('modalPenilaian')).show();

    fetch(`penilaian.php?get_nilai=1&peserta_id=${peserta_id}`,{credentials:'include'})
        .then(r=>r.json()).then(existing=>{
            // Tentukan apakah input atau edit
            const isEdit = Object.keys(existing).length > 0;
            const modalTitle = isEdit ? 
                '<i class="bi bi-pencil-square"></i> Edit Nilai SAW' : 
                '<i class="bi bi-plus-circle"></i> Input Nilai SAW';
            
            document.querySelector('#modalPenilaian .modal-title').innerHTML = modalTitle;
            
            let rows = KRITERIA.map(k=>{
                let subKritOptions = '<option value="">-- Pilih --</option>';
                // Will be populated by JS after loading sub-kriteria
                return `
                    <tr>
                        <td><strong>${k.kode_kriteria}</strong><br><small class="text-muted">${k.nama_kriteria}</small></td>
                        <td><span class="badge ${k.jenis==='Benefit'?'bg-success':'bg-danger'}">${k.jenis}</span></td>
                        <td>Bobot: ${k.bobot}</td>
                        <td>
                            <select class="form-select form-select-sm nilai-select"
                                data-kriteria="${k.id}" 
                                onchange="updateNilaiDisplay(this)"
                                style="width:100%">
                                ${subKritOptions}
                            </select>
                            <small class="text-muted d-block mt-1 sub-krit-desc"></small>
                        </td>
                    </tr>
                    <tr class="table-light" id="subkrit-${k.id}" style="display:none">
                        <td colspan="4"><small id="subkrit-list-${k.id}"></small></td>
                    </tr>`;
            }).join('');

            document.getElementById('modalBody').innerHTML = `
                <h6 class="fw-bold mb-3"><i class="bi bi-person-fill"></i> ${nama}</h6>
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-secondary">
                        <tr><th>Kriteria</th><th>Jenis</th><th>Bobot</th><th>Nilai & Sub-Kriteria</th></tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle-fill"></i>
                    <strong>Panduan pengisian:</strong> Pilih nilai (1-5) sesuai dengan kondisi penerima berdasarkan sub-kriteria yang ditampilkan.
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary flex-fill" onclick="simpanPenilaian(${peserta_id})">
                        <i class="bi bi-save"></i> ${isEdit ? 'Perbarui Nilai' : 'Simpan Nilai'}
                    </button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>`;
            
            // Load sub-kriteria for each kriteria
            KRITERIA.forEach(k => {
                fetch(`penilaian.php?get_sub_kriteria=1&kriteria_id=${k.id}`,{credentials:'include'})
                    .then(r=>r.json()).then(subs=>{
                        let options = '<option value="">-- Pilih --</option>';
                        let subList = '<strong>Skala Penilaian:</strong><ul style="margin:5px 0; padding-left:20px;">';
                        subs.forEach(s=>{
                            options += `<option value="${s.nilai_sub}">${s.nilai_sub} - ${s.deskripsi}</option>`;
                            subList += `<li><strong>${s.nilai_sub}</strong>: ${s.deskripsi}</li>`;
                        });
                        subList += '</ul>';
                        
                        const select = document.querySelector(`select[data-kriteria="${k.id}"]`);
                        if(select) {
                            select.innerHTML = options;
                            if(existing[k.id]) {
                                select.value = existing[k.id];
                                // Tampilkan preview nilai yang sudah dipilih
                                const selectedOption = subs.find(s => s.nilai_sub == existing[k.id]);
                                if(selectedOption) {
                                    select.parentElement.querySelector('.sub-krit-desc').textContent = `✓ ${selectedOption.nilai_sub} - ${selectedOption.deskripsi}`;
                                }
                            }
                            document.getElementById(`subkrit-list-${k.id}`).innerHTML = subList;
                        }
                    });
            });
        });
}

function updateNilaiDisplay(selectElem) {
    const val = selectElem.value;
    const optionText = selectElem.options[selectElem.selectedIndex]?.text || '';
    const desc = val ? `✓ ${optionText}` : '';
    selectElem.parentElement.querySelector('.sub-krit-desc').textContent = desc;
}

function simpanPenilaian(peserta_id) {
    const fd = new FormData();
    fd.append('action','simpan_penilaian');
    fd.append('peserta_id', peserta_id);

    document.querySelectorAll('.nilai-select').forEach(sel=>{
        if(sel.value!=='') fd.append('nilai['+sel.dataset.kriteria+']', sel.value);
    });

    fetch('penilaian.php',{method:'POST',body:fd,credentials:'include'})
        .then(r=>r.json()).then(resp=>{
            if(resp.status==='success'){
                alert('✅ Nilai berhasil disimpan/diperbarui');
                bootstrap.Modal.getInstance(document.getElementById('modalPenilaian')).hide();
                loadTabel();
            } else alert('❌ '+resp.message);
        }).catch(()=>alert('Terjadi kesalahan jaringan'));
}

document.addEventListener('DOMContentLoaded', loadTabel);
</script>
<?php endif; ?>
