<?php
$pageTitle = 'Hasil SAW';
require_once __DIR__ . '/../../includes/functions.php';
startSession();
requireRole(['admin', 'atasan']);

$db = getDB();

/* ════════════════════════════════════════════
   GET DETAIL NILAI KRITERIA (AJAX)
════════════════════════════════════════════ */
if (isset($_GET['detail_nilai']) && isset($_GET['peserta_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    $peserta_id = (int)$_GET['peserta_id'];
    
    $details = $db->prepare("
        SELECT 
            sk.kode_kriteria, sk.nama_kriteria, sk.bobot, sk.jenis,
            sp.nilai,
            ssub.deskripsi AS sub_kriteria_deskripsi,
            ssub.nilai_sub
        FROM saw_penilaian sp
        JOIN saw_kriteria sk ON sp.kriteria_id = sk.id
        LEFT JOIN saw_sub_kriteria ssub ON sk.id = ssub.kriteria_id AND sp.nilai = ssub.nilai_sub
        WHERE sp.peserta_id = ?
        ORDER BY sk.urutan ASC
    ");
    $details->bind_param("i", $peserta_id);
    $details->execute();
    $rows = $details->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($rows); 
    exit;
}

/* ─── CETAK REKAP (action=cetak) ─── */
if (isset($_GET['cetak'])) {
    $hasil = $db->query("
        SELECT sh.peringkat, p.nik, p.nama_lengkap, p.alamat,
               k.nama_kelurahan, sh.nilai_preferensi, sh.status_rekomendasi,
               u.nama AS dihitung_oleh, sh.updated_at
        FROM saw_hasil sh
        JOIN peserta p ON sh.peserta_id = p.id
        LEFT JOIN kelurahan k ON p.kelurahan_id = k.id
        LEFT JOIN users u ON sh.dihitung_oleh = u.id
        ORDER BY sh.peringkat ASC
    ")->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: text/html; charset=utf-8');
    ?><!DOCTYPE html>
    <html><head>
    <meta charset="UTF-8"><title>Rekap Hasil SAW</title>
    <style>
        body{font-family:Arial;font-size:12px;margin:20px;}
        h2,h4{text-align:center;margin:4px 0;}
        table{width:100%;border-collapse:collapse;margin-top:12px;}
        th,td{border:1px solid #333;padding:6px;text-align:left;}
        th{background:#2c3e50;color:#fff;}
        tr:nth-child(even){background:#f9f9f9;}
        .rec{background:#d4edda;}
        @media print{.no-print{display:none}}
    </style>
    </head><body>
    <h2><?= APP_INSTANSI ?></h2>
    <h4>Rekap Hasil Perhitungan SAW – Penerima BLT</h4>
    <p style="text-align:center;margin:4px 0">Dicetak: <?= date('d-m-Y H:i') ?></p>
    <table>
        <thead><tr><th>#</th><th>Peringkat</th><th>NIK</th><th>Nama</th><th>Kelurahan</th><th>Nilai V</th><th>Status</th></tr></thead>
        <tbody>
        <?php 
        $no = 1;
        foreach ($hasil as $r): 
            // Tentukan status berdasarkan nilai preferensi
            if ($r['nilai_preferensi'] >= 0.80) {
                $status = 'Direkomendasikan';
                $recClass = 'rec';
            } elseif ($r['nilai_preferensi'] >= 0.60 && $r['nilai_preferensi'] < 0.80) {
                $status = 'Cadangan';
                $recClass = '';
            } else {
                $status = 'Tidak Direkomendasikan';
                $recClass = '';
            }
        ?>
        <tr class="<?= $recClass ?>">
            <td><?= $no++ ?></td>
            <td><?= $r['peringkat'] ?: '-' ?></td>
            <td><?= $r['nik'] ?></td>
            <td><?= htmlspecialchars($r['nama_lengkap']) ?></td>
            <td><?= $r['nama_kelurahan']??'-' ?></td>
            <td><?= number_format($r['nilai_preferensi'],2,',','.') ?></td>
            <td><?= $status ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p style="margin-top:20px;font-size:11px;">
        * Peringkat ditentukan berdasarkan metode Simple Additive Weighting (SAW).<br>
        * Kriteria status: 0,80 – 1,00 = Direkomendasikan | 0,60 – 0,79 = Cadangan | 0,00 – 0,59 = Tidak Direkomendasikan<br>
        * Baris hijau = Direkomendasikan.
    </p>
    <div class="no-print" style="margin-top:20px;text-align:center;">
        <button onclick="window.print()" style="padding:8px 20px;background:#2c3e50;color:#fff;border:none;border-radius:5px;cursor:pointer;">🖨 Cetak</button>
        <button onclick="window.close()" style="padding:8px 20px;margin-left:8px;border:1px solid #ccc;border-radius:5px;cursor:pointer;">Tutup</button>
    </div>
    </body></html>
    <?php exit;
}

/* ─── Ambil data hasil dengan detail kriteria ─── */
$hasil = $db->query("
    SELECT sh.id, sh.peringkat, sh.nilai_preferensi, sh.status_rekomendasi, sh.updated_at,
           p.id AS peserta_id, p.nik, p.nama_lengkap, p.alamat, p.kategori_kemiskinan, 
           p.penghasilan_bulanan, p.kondisi_rumah,
           k.nama_kelurahan, u.nama AS dihitung_oleh
    FROM saw_hasil sh
    JOIN peserta p ON sh.peserta_id = p.id
    LEFT JOIN kelurahan k ON p.kelurahan_id = k.id
    LEFT JOIN users u ON sh.dihitung_oleh = u.id
    ORDER BY sh.nilai_preferensi DESC, p.kategori_kemiskinan ASC, p.penghasilan_bulanan ASC
")->fetch_all(MYSQLI_ASSOC);

// Ambil nilai kriteria untuk tiebreaker
$kriteriaData = [];
if (!empty($hasil)) {
    $pesertaIds = array_column($hasil, 'peserta_id');
    if (!empty($pesertaIds)) {
        $kriteriaSql = "
            SELECT sp.peserta_id, 
                   MAX(CASE WHEN sk.nama LIKE '%C1%' THEN sp.nilai ELSE NULL END) as c1,
                   MAX(CASE WHEN sk.nama LIKE '%C2%' THEN sp.nilai ELSE NULL END) as c2,
                   MAX(CASE WHEN sk.nama LIKE '%C4%' THEN sp.nilai ELSE NULL END) as c4,
                   MAX(CASE WHEN sk.nama LIKE '%C5%' THEN sp.nilai ELSE NULL END) as c5
            FROM saw_penilaian sp
            JOIN saw_kriteria sk ON sp.kriteria_id = sk.id
            WHERE sp.peserta_id IN (" . implode(',', $pesertaIds) . ")
            GROUP BY sp.peserta_id
        ";
        $kriteriaRes = $db->query($kriteriaSql);
        if ($kriteriaRes) {
            while ($r = $kriteriaRes->fetch_assoc()) {
                $kriteriaData[$r['peserta_id']] = $r;
            }
        }
    }
}

// Assign rekomendasi status berdasarkan TOP 3 dan threshold
$top3Rank = 0;
$nilaiSebelumnya = null;
$rankSebelumnya = 0;
foreach ($hasil as &$h) {
    // Deteksi apakah ini TOP 3
    if ($top3Rank < 3) {
        // Jika nilai sama dengan sebelumnya, gunakan rank yang sama
        if ($nilaiSebelumnya !== null && $h['nilai_preferensi'] == $nilaiSebelumnya) {
            $h['rank_top'] = $rankSebelumnya;
        } else {
            $top3Rank++;
            $rankSebelumnya = $top3Rank;
            $h['rank_top'] = $top3Rank;
        }
        $nilaiSebelumnya = $h['nilai_preferensi'];
    } else {
        $h['rank_top'] = null;
    }
    
    // Tentukan status rekomendasi berdasarkan range nilai preferensi
    // 0.80 – 1.00 = Direkomendasikan
    // 0.60 – 0.79 = Cadangan
    // 0.00 – 0.59 = Tidak Direkomendasikan
    if ($h['nilai_preferensi'] >= 0.80) {
        $h['status_badge'] = 'Direkomendasikan';
        $h['badge_color'] = 'success';
    } elseif ($h['nilai_preferensi'] >= 0.60 && $h['nilai_preferensi'] < 0.80) {
        $h['status_badge'] = 'Cadangan';
        $h['badge_color'] = 'warning';
    } else {
        $h['status_badge'] = 'Tidak Direkomendasikan';
        $h['badge_color'] = 'secondary';
    }
}
unset($h);

// Cari nilai yang sama untuk info
$nilaiGroups = [];
foreach ($hasil as $h) {
    $nilai = $h['nilai_preferensi'];
    if (!isset($nilaiGroups[$nilai])) {
        $nilaiGroups[$nilai] = [];
    }
    $nilaiGroups[$nilai][] = $h['peserta_id'];
}

$totalDirekomendasikan = 0;
$totalCadangan = 0;
foreach ($hasil as $r) {
    if ($r['status_badge'] === 'Direkomendasikan') {
        $totalDirekomendasikan++;
    } elseif ($r['status_badge'] === 'Cadangan') {
        $totalCadangan++;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-start">
    <div>
        <h4><i class="bi bi-trophy"></i> Hasil Akhir SAW</h4>
        <small class="text-muted">Hasil perhitungan dan rekomendasi penerima BLT</small>
    </div>
    <div class="d-flex gap-2">
        <?php if (in_array(getCurrentUser()['role'], ['admin','atasan'])): ?>
        <a href="perhitungan.php?refresh=1"
           onclick="return confirm('Hitung ulang nilai SAW?')"
           class="btn btn-warning btn-sm">
            <i class="bi bi-arrow-clockwise"></i> Hitung Ulang
        </a>
        <?php endif; ?>
        <?php if (!empty($hasil)): ?>
        <a href="hasil.php?cetak=1" target="_blank" class="btn btn-dark btn-sm">
            <i class="bi bi-printer"></i> Cetak Rekap
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($hasil)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle-fill"></i>
    Belum ada hasil perhitungan. Pastikan sudah input nilai di
    <a href="penilaian.php">Input Penilaian SAW</a>,
    lalu klik <a href="perhitungan.php">Perhitungan SAW</a> untuk menghitung.
</div>
<?php else: ?>

<!-- Filter -->
<div class="mb-3 d-flex gap-2 align-items-center flex-wrap">
    <input type="text" id="searchHasil" class="form-control" style="max-width:260px" placeholder="Cari nama / NIK..." oninput="filterTabel()">
    <select id="filterRek" class="form-select" style="max-width:220px" onchange="filterTabel()">
        <option value="">Semua Status</option>
        <option value="Direkomendasikan">Direkomendasikan (0.80 – 1.00)</option>
        <option value="Cadangan">Cadangan (0.60 – 0.79)</option>
        <option value="Tidak Direkomendasikan">Tidak Direkomendasikan (0.00 – 0.59)</option>
    </select>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" id="tblHasil">
                <thead class="table-dark">
                    <tr>
                        <th style="width:60px">Rank</th>
                        <th>NIK</th>
                        <th>Nama Peserta</th>
                        <th>Kelurahan</th>
                        <th class="text-center" style="width:140px">Nilai V</th>
                        <th>Status</th>
                        <th class="text-center" style="width:120px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($hasil as $r):
                    $badgeColors = [
                        'success' => '#198754',
                        'info' => '#0dcaf0',
                        'warning' => '#ffc107',
                        'secondary' => '#6c757d'
                    ];
                    $badgeColor = $badgeColors[$r['badge_color']] ?? '#000';
                    $nilaiIdentik = isset($nilaiGroups[$r['nilai_preferensi']]) && count($nilaiGroups[$r['nilai_preferensi']]) > 1;
                ?>
                <tr class="align-middle"
                    data-nama="<?= strtolower($r['nama_lengkap']) ?>"
                    data-nik="<?= $r['nik'] ?>"
                    data-status="<?= $r['status_badge'] ?>">
                    <td class="text-center fw-bold">
                        <?php if ($r['rank_top'] !== null): ?>
                            <span class="badge bg-success" style="font-size:0.9rem; padding:6px 8px;">
                                🏆 TOP <?= $r['rank_top'] ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><code><?= $r['nik'] ?></code></td>
                    <td><strong><?= htmlspecialchars($r['nama_lengkap']) ?></strong></td>
                    <td><?= $r['nama_kelurahan'] ?? '-' ?></td>
                    <td class="text-center fw-bold">
                        <span style="font-size:1.05rem; color:<?= $badgeColor ?>;">
                            <?= number_format($r['nilai_preferensi'], 4, ',', '.') ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge fs-6 px-3" style="background-color:<?= $badgeColor ?>;">
                            <?php 
                                $statusIcons = [
                                    'Direkomendasikan' => '✅ ',
                                    'Cadangan' => '⚠️  ',
                                    'Tidak Direkomendasikan' => '❌ '
                                ];
                                echo ($statusIcons[$r['status_badge']] ?? '') . $r['status_badge'];
                            ?>
                        </span>
                        <?php if ($nilaiIdentik): ?>
                            <br><small class="text-muted d-block mt-1">
                                <i class="bi bi-info-circle"></i> Nilai sama, urut: PKH → C1
                            </small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="lihatNilaiKriteria(<?= $r['peserta_id'] ?>, '<?= htmlspecialchars($r['nama_lengkap']) ?>')"
                                title="Lihat Nilai Kriteria">
                            <i class="bi bi-eye"></i> Detail
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<!-- MODAL DETAIL NILAI KRITERIA -->
<div class="modal fade" id="modalNilaiKriteria" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-chart-bar"></i> Detail Nilai Kriteria SAW</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalNilaiBody">
                <p class="text-center text-muted"><i class="bi bi-hourglass-split"></i> Memuat data...</p>
            </div>
        </div>
    </div>
</div>

<script>
function lihatNilaiKriteria(peserta_id, nama) {
    const modal = new bootstrap.Modal(document.getElementById('modalNilaiKriteria'));
    document.getElementById('modalNilaiBody').innerHTML = '<p class="text-center text-muted"><i class="bi bi-hourglass-split"></i> Memuat data...</p>';
    modal.show();
    
    fetch(`hasil.php?detail_nilai=1&peserta_id=${peserta_id}`, {credentials: 'include'})
        .then(r => r.json())
        .then(data => {
            if (!data || data.length === 0) {
                document.getElementById('modalNilaiBody').innerHTML = '<p class="text-muted text-center">Belum ada penilaian untuk peserta ini.</p>';
                return;
            }
            
            let html = `<h6 class="fw-bold mb-3"><i class="bi bi-person-fill"></i> ${nama}</h6>`;
            html += '<div class="table-responsive"><table class="table table-sm table-bordered align-middle">';
            html += '<thead class="table-secondary"><tr><th>Kriteria</th><th class="text-center">Nilai</th><th>Sub-Kriteria</th><th class="text-center">Bobot</th></tr></thead><tbody>';
            
            data.forEach(d => {
                const badgeJenis = d.jenis === 'Benefit' ? '<span class="badge bg-success">Benefit</span>' : '<span class="badge bg-danger">Cost</span>';
                const subDesc = d.sub_kriteria_deskripsi ? `<strong>${d.nilai_sub}</strong> - ${d.sub_kriteria_deskripsi}` : '<span class="text-muted">-</span>';
                html += `<tr>
                    <td><strong>${d.kode_kriteria}</strong><br><small class="text-muted">${d.nama_kriteria}</small><br>${badgeJenis}</td>
                    <td class="text-center fw-bold">${d.nilai}</td>
                    <td>${subDesc}</td>
                    <td class="text-center">${parseFloat(d.bobot).toFixed(2)}</td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
            html += '<div class="alert alert-info small mt-3 mb-0">';
            html += '<i class="bi bi-info-circle-fill"></i> <strong>Keterangan:</strong> Tabel di atas menampilkan semua kriteria yang digunakan dalam perhitungan nilai SAW untuk peserta ini beserta sub-kriteria yang dipilih.';
            html += '</div>';
            
            document.getElementById('modalNilaiBody').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('modalNilaiBody').innerHTML = '<p class="text-danger text-center">Gagal memuat data. Silakan coba lagi.</p>';
            console.error(err);
        });
}

function filterTabel() {
    const q = document.getElementById('searchHasil').value.toLowerCase();
    const status = document.getElementById('filterRek').value;
    document.querySelectorAll('#tblHasil tbody tr').forEach(tr => {
        const matchQ = !q || tr.dataset.nama.includes(q) || tr.dataset.nik.includes(q);
        const matchStatus = !status || tr.dataset.status === status;
        tr.style.display = (matchQ && matchStatus) ? '' : 'none';
    });
}
</script>
