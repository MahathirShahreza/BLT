<?php
require_once '../../includes/functions.php';
startSession();
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ../peserta/index.php'); exit; }

$peserta = dbFetchOne("SELECT p.*, k.nama_kelurahan, k.kecamatan FROM peserta p LEFT JOIN kelurahan k ON p.kelurahan_id=k.id WHERE p.id=?", [$id]);
if (!$peserta) { header('Location: ../peserta/index.php?error=Peserta+tidak+ditemukan'); exit; }

// Ambil skor SAW jika ada
$sawScore = dbFetchOne("SELECT hasil_preferensi FROM saw_hasil WHERE peserta_id=?", [$id]);

$pageTitle = 'Kartu Peserta - ' . $peserta['nama_lengkap'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; margin: 20px; }
        .card-container { max-width: 600px; margin: 0 auto; }
        .kartu-peserta {
            background: white;
            padding: 30px;
            border: 2px solid #333;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            page-break-inside: avoid;
        }
        .card-header-section {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .card-title { font-size: 18px; font-weight: bold; margin: 0; }
        .card-subtitle { font-size: 12px; color: #666; margin: 5px 0 0 0; }
        .info-row {
            display: flex;
            margin-bottom: 12px;
            font-size: 13px;
            border-bottom: 1px dotted #ddd;
            padding-bottom: 8px;
        }
        .info-label {
            flex: 0 0 35%;
            font-weight: 600;
            color: #333;
        }
        .info-value {
            flex: 1;
            color: #555;
            word-break: break-word;
        }
        .foto-section {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .foto-preview {
            max-width: 150px;
            max-height: 150px;
            border: 1px solid #ccc;
            padding: 2px;
        }
        .foto-label { font-size: 11px; color: #666; margin-top: 5px; }
        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            text-align: center;
        }
        .signature-box {
            width: 45%;
            font-size: 12px;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            margin-bottom: 5px;
        }
        .print-btn {
            margin: 20px 0;
            text-align: center;
        }
        @media print {
            body { background: white; margin: 0; }
            .print-btn { display: none; }
            .kartu-peserta { box-shadow: none; border: 1px solid #999; }
        }
    </style>
</head>
<body>
    <div class="card-container">
        <div class="print-btn">
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
            <a href="../peserta/detail.php?id=<?= $id ?>" class="btn btn-secondary">Kembali</a>
        </div>

        <div class="kartu-peserta">
            <!-- Header -->
            <div class="card-header-section">
                <p class="card-title">KARTU DATA PESERTA BLT</p>
                <p class="card-subtitle">Bantuan Langsung Tunai - Pemerintah Daerah</p>
            </div>

            <!-- Identitas -->
            <div class="info-row">
                <div class="info-label">NIK</div>
                <div class="info-value"><?= sanitize($peserta['nik']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Nama Lengkap</div>
                <div class="info-value fw-bold" style="font-size: 14px;"><?= sanitize($peserta['nama_lengkap']) ?></div>
            </div>

            <!-- Data Pribadi -->
            <div class="info-row">
                <div class="info-label">Jenis Kelamin</div>
                <div class="info-value"><?= $peserta['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Tempat/Tgl Lahir</div>
                <div class="info-value"><?= sanitize($peserta['tempat_lahir']) ?>, <?= formatTanggal($peserta['tanggal_lahir']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Agama</div>
                <div class="info-value"><?= sanitize($peserta['agama']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Pekerjaan</div>
                <div class="info-value"><?= sanitize($peserta['pekerjaan'] ?? '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Status Kawin</div>
                <div class="info-value"><?= sanitize($peserta['status_kawin']) ?></div>
            </div>

            <!-- Alamat -->
            <div class="info-row">
                <div class="info-label">Alamat</div>
                <div class="info-value"><?= sanitize($peserta['alamat']) ?> RT <?= sanitize($peserta['rt']) ?>/RW <?= sanitize($peserta['rw']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Kelurahan</div>
                <div class="info-value"><?= sanitize($peserta['nama_kelurahan'] ?? $peserta['kelurahan_id']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Kecamatan</div>
                <div class="info-value"><?= sanitize($peserta['kecamatan'] ?? '-') ?></div>
            </div>

            <!-- Keluarga -->
            <div class="info-row">
                <div class="info-label">No. KK</div>
                <div class="info-value"><?= sanitize($peserta['no_kk'] ?? '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Kep. Keluarga</div>
                <div class="info-value"><?= sanitize($peserta['nama_kepala_keluarga'] ?? '-') ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Jml Anggota KK</div>
                <div class="info-value"><?= $peserta['jumlah_anggota_keluarga'] ?> orang</div>
            </div>

            <!-- Ekonomi -->
            <div class="info-row">
                <div class="info-label">Penghasilan/Bulan</div>
                <div class="info-value"><?= formatRupiah($peserta['penghasilan_bulanan']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Kategori</div>
                <div class="info-value"><?= sanitize($peserta['kategori_kemiskinan']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Kondisi Rumah</div>
                <div class="info-value"><?= sanitize($peserta['kondisi_rumah']) ?></div>
            </div>

            <!-- SAW Score -->
            <?php if ($sawScore): ?>
            <div class="info-row" style="background: #e7f3ff; padding: 10px;">
                <div class="info-label">Skor SAW</div>
                <div class="info-value fw-bold" style="color: #0c5460;"><?= number_format($sawScore['hasil_preferensi'], 4) ?></div>
            </div>
            <?php endif; ?>

            <!-- Foto -->
            <?php if ($peserta['foto_peserta'] || $peserta['foto_ktp']): ?>
            <div class="foto-section">
                <?php if ($peserta['foto_peserta']): ?>
                <div>
                    <img src="<?= $baseUrl . UPLOAD_URL . $peserta['foto_peserta'] ?>" alt="Foto" class="foto-preview">
                    <p class="foto-label">Foto Peserta</p>
                </div>
                <?php endif; ?>
                <?php if ($peserta['foto_ktp']): ?>
                <div style="margin-top: 10px;">
                    <img src="<?= $baseUrl . UPLOAD_URL . $peserta['foto_ktp'] ?>" alt="KTP" class="foto-preview">
                    <p class="foto-label">Foto KTP</p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Informasi Tambahan -->
            <div class="info-row" style="border: none; margin-top: 20px; font-size: 11px; color: #999;">
                <div>Dicetak: <?= formatTanggal(date('Y-m-d'), true) ?></div>
            </div>

            <!-- Signature Section -->
            <div class="signature-section">
                <div class="signature-box">
                    Petugas
                    <div class="signature-line"></div>
                </div>
                <div class="signature-box">
                    Kepala Desa
                    <div class="signature-line"></div>
                </div>
            </div>
        </div>

        <div class="print-btn">
            <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
            <a href="../peserta/detail.php?id=<?= $id ?>" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
