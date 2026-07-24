<?php
$pageTitle = 'Perhitungan SAW';
require_once __DIR__ . '/../../includes/functions.php';
startSession();
requireRole(['admin', 'atasan']);

$db = getDB();

/* ─── Handle Refresh / Recalculate ─── */
$flashMsg = '';
if (isset($_GET['refresh']) && $_GET['refresh'] === '1') {
    $db->query("TRUNCATE TABLE saw_hasil");
    header("Location: perhitungan.php?recalc=1", true, 303);
    exit;
}
if (isset($_GET['recalc'])) {
    $flashMsg = '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Data berhasil direset dan dihitung ulang.</div>';
}

/* ════════════════════════════════════════════
   1. AMBIL KRITERIA
════════════════════════════════════════════ */
$kriteria = [];
$resKriteria = $db->query("SELECT * FROM saw_kriteria ORDER BY urutan ASC");
while ($r = $resKriteria->fetch_assoc()) {
    $kriteria[$r['id']] = [
        'kode'  => $r['kode_kriteria'],
        'nama'  => $r['nama_kriteria'],
        'bobot' => (float)$r['bobot'],
        'jenis' => strtolower(trim($r['jenis'])),
    ];
}

/* ════════════════════════════════════════════
   2. AMBIL PESERTA (yang sudah disetujui)
════════════════════════════════════════════ */
$peserta = [];
$resPeserta = $db->query("
    SELECT p.id, p.nik, p.nama_lengkap, k.nama_kelurahan
    FROM peserta p
    LEFT JOIN kelurahan k ON p.kelurahan_id = k.id
    WHERE p.status_verifikasi = 'disetujui' AND p.status_aktif = 'aktif'
    ORDER BY p.nama_lengkap ASC
");
while ($r = $resPeserta->fetch_assoc()) {
    $peserta[$r['id']] = [
        'nik'          => $r['nik'],
        'nama'         => $r['nama_lengkap'],
        'kelurahan'    => $r['nama_kelurahan'] ?? '-',
        'nilai'        => [],
    ];
}

/* ════════════════════════════════════════════
   3. AMBIL NILAI PENILAIAN
════════════════════════════════════════════ */
$resNilai = $db->query("
    SELECT sp.peserta_id, sp.kriteria_id, sp.nilai
    FROM saw_penilaian sp
    INNER JOIN peserta p ON sp.peserta_id = p.id
    WHERE p.status_verifikasi = 'disetujui' AND p.status_aktif = 'aktif'
");
while ($r = $resNilai->fetch_assoc()) {
    if (isset($peserta[$r['peserta_id']])) {
        $peserta[$r['peserta_id']]['nilai'][$r['kriteria_id']] = (float)$r['nilai'];
    }
}

/* ════════════════════════════════════════════
   4. MATRIKS KEPUTUSAN (X)
════════════════════════════════════════════ */
$X = [];
foreach ($peserta as $pid => $p) {
    foreach ($kriteria as $kid => $k) {
        $X[$pid][$kid] = $p['nilai'][$kid] ?? 0;
    }
}

/* ════════════════════════════════════════════
   5. NORMALISASI (R)
   Benefit: Xij / max(Xj)
   Cost:    min(Xj) / Xij
════════════════════════════════════════════ */
$R = [];
foreach ($kriteria as $kid => $k) {
    $colVals = [];
    foreach ($X as $pid => $vals) {
        $colVals[] = $vals[$kid] ?? 0;
    }

    if ($k['jenis'] === 'benefit') {
        $maxVal = max($colVals) ?: 1;
        foreach ($X as $pid => $vals) {
            $R[$pid][$kid] = $maxVal > 0 ? round(($vals[$kid] ?? 0) / $maxVal, 4) : 0;
        }
    } else {
        $nonzero = array_filter($colVals);
        $minVal  = !empty($nonzero) ? min($nonzero) : 1;
        foreach ($X as $pid => $vals) {
            $v = $vals[$kid] ?? 0;
            $R[$pid][$kid] = $v > 0 ? round($minVal / $v, 4) : 0;
        }
    }
}

/* ════════════════════════════════════════════
   6. PEMBOBOTAN (Y = R × bobot)
════════════════════════════════════════════ */
$Y = [];
foreach ($R as $pid => $vals) {
    foreach ($kriteria as $kid => $k) {
        $Y[$pid][$kid] = round(($vals[$kid] ?? 0) * $k['bobot'], 6);
    }
}

/* ════════════════════════════════════════════
   7. NILAI AKHIR (V = sum Y)
════════════════════════════════════════════ */
$V = [];
foreach ($Y as $pid => $vals) {
    $V[$pid] = array_sum($vals);
}

/* ════════════════════════════════════════════
   8. RANKING & SIMPAN KE saw_hasil
════════════════════════════════════════════
   Status Rekomendasi:
   - 0.80 – 1.00: Direkomendasikan
   - 0.60 – 0.79: Cadangan
   - 0.00 – 0.59: Tidak Direkomendasikan
════════════════════════════════════════════ */
arsort($V);

$db->query("TRUNCATE TABLE saw_hasil");
$userId = getCurrentUser()['id'];
$rank = 1;

foreach ($V as $pid => $nilai) {
    // Tentukan status berdasarkan range nilai preferensi
    if ($nilai >= 0.80) {
        $status = 'Direkomendasikan';
    } elseif ($nilai >= 0.60 && $nilai < 0.80) {
        $status = 'Cadangan';
    } else {
        $status = 'Tidak Direkomendasikan';
    }
    
    $db->query("INSERT INTO saw_hasil (peserta_id, nilai_preferensi, peringkat, status_rekomendasi, dihitung_oleh) 
                VALUES ($pid, $nilai, $rank, '$status', $userId)");
    $rank++;
}

/* Gunakan V yang sudah sorted untuk render */
$V_ordered = $V;

/* ════════════════════════════════════════════
   HELPER RENDER TABEL MATRIKS
════════════════════════════════════════════ */
function renderMatriks($title, $data, $pesertaArr, $kriteriaArr, $decimals = 4) {
    echo "<div class='card shadow-sm mb-4'>
        <div class='card-header bg-dark text-white fw-bold'>{$title}</div>
        <div class='card-body p-0'>
        <div class='table-responsive'>
        <table class='table table-sm table-bordered align-middle mb-0'>";

    echo "<thead class='table-secondary'><tr><th>NIK</th><th>Nama Peserta</th>";
    foreach ($kriteriaArr as $kid => $k) {
        echo "<th title='{$k['nama']}'>{$k['kode']}</th>";
    }
    echo "</tr></thead><tbody>";

    foreach ($pesertaArr as $pid => $p) {
        if (!isset($data[$pid])) continue;
        echo "<tr><td>{$p['nik']}</td><td>{$p['nama']}</td>";
        foreach ($kriteriaArr as $kid => $k) {
            $val = isset($data[$pid][$kid]) ? number_format($data[$pid][$kid], $decimals, '.', '') : '0';
            echo "<td>{$val}</td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table></div></div></div>";
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <h4><i class="bi bi-calculator"></i> Data Perhitungan SAW</h4>
    <small class="text-muted">Transparansi proses perhitungan Simple Additive Weighting</small>
</div>

<?= $flashMsg ?>

<?php if (empty($kriteria)): ?>
<div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill"></i> Belum ada kriteria SAW. Silakan atur di menu Kriteria SAW.</div>
<?php elseif (empty($peserta)): ?>
<div class="alert alert-info"><i class="bi bi-info-circle-fill"></i> Belum ada peserta yang sudah diverifikasi.</div>
<?php else: ?>

<!-- Info bobot -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-2">Bobot Kriteria</h6>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($kriteria as $kid => $k): ?>
            <span class="badge <?= $k['jenis']==='benefit' ? 'bg-success' : 'bg-danger' ?> fs-6 px-3 py-2">
                <?= $k['kode'] ?>: <?= $k['nama'] ?> (<?= $k['bobot'] ?>) - <?= ucfirst($k['jenis']) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php
        $totalBobot = array_sum(array_column($kriteria, 'bobot'));
        if (abs($totalBobot - 1.0) > 0.001):
        ?>
        <div class="alert alert-warning mt-2 mb-0">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Total bobot = <?= number_format($totalBobot, 4) ?> — <strong>harus = 1.00</strong>. Hasil perhitungan mungkin tidak akurat.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Refresh button -->
<?php if (in_array(getCurrentUser()['role'], ['admin','atasan'])): ?>
<div class="d-flex justify-content-end mb-3">
    <a href="perhitungan.php?refresh=1"
       onclick="return confirm('Reset dan hitung ulang semua nilai?')"
       class="btn btn-warning">
        <i class="bi bi-arrow-clockwise"></i> Hitung Ulang
    </a>
</div>
<?php endif; ?>

<!-- Matriks Keputusan -->
<?php renderMatriks('1. Matriks Keputusan (X) — Nilai Asli', $X, $peserta, $kriteria, 0); ?>

<!-- Matriks Normalisasi -->
<?php renderMatriks('2. Matriks Normalisasi (R)', $R, $peserta, $kriteria, 4); ?>

<!-- Matriks Pembobotan -->
<?php renderMatriks('3. Matriks Pembobotan (Y = R × Bobot)', $Y, $peserta, $kriteria, 6); ?>

<!-- Nilai Akhir -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white fw-bold">4. Nilai Akhir (V) & Peringkat</div>
    <div class="card-body">
        <div class="alert alert-info small mb-3">
            <i class="bi bi-info-circle-fill"></i>
            <strong>Kriteria Status:</strong>
            <ul class="mb-0 mt-2">
                <li><strong>0.80 – 1.00:</strong> Direkomendasikan</li>
                <li><strong>0.60 – 0.79:</strong> Cadangan</li>
                <li><strong>0.00 – 0.59:</strong> Tidak Direkomendasikan</li>
            </ul>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Peringkat</th>
                        <th>NIK</th>
                        <th>Nama Peserta</th>
                        <th>Kelurahan</th>
                        <th>Nilai V</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $rank = 1;
                foreach ($V_ordered as $pid => $v):
                    if (!isset($peserta[$pid])) continue;
                    $p = $peserta[$pid];
                    
                    // Status berdasarkan range nilai preferensi baru
                    if ($v >= 0.80) {
                        $status = '<span class="badge bg-success">Direkomendasikan</span>';
                        $rowClass = 'table-success';
                    } elseif ($v >= 0.60 && $v < 0.80) {
                        $status = '<span class="badge bg-warning">Cadangan</span>';
                        $rowClass = 'table-warning';
                    } else {
                        $status = '<span class="badge bg-secondary">Tidak Direkomendasikan</span>';
                        $rowClass = '';
                    }
                ?>
                <tr class="<?= $rowClass ?>">
                    <td class="text-center fw-bold"><?= $rank ?></td>
                    <td><?= $p['nik'] ?></td>
                    <td><strong><?= htmlspecialchars($p['nama']) ?></strong></td>
                    <td><?= $p['kelurahan'] ?></td>
                    <td class="text-center fw-bold"><?= number_format($v, 6, '.', '') ?></td>
                    <td><?= $status ?></td>
                </tr>
                <?php $rank++; endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
