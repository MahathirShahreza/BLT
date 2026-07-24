<?php
require_once '../../includes/functions.php';
startSession();
requireLogin();
requireRole(['admin']);
$currentUser = getCurrentUser();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$peserta = dbFetchOne("SELECT * FROM peserta WHERE id=?", [$id]);
if (!$peserta) { header('Location: index.php?error=Peserta+tidak+ditemukan'); exit; }

// Soft delete — set nonaktif
dbQuery("UPDATE peserta SET status_aktif='nonaktif' WHERE id=?", [$id]);
logAktivitas('Hapus Peserta', 'Menonaktifkan peserta: ' . $peserta['nama_lengkap'] . ' (' . $peserta['nik'] . ')');
header('Location: index.php?success=' . urlencode('Peserta berhasil dihapus'));
exit;
