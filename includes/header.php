<?php
require_once __DIR__ . '/functions.php';
startSession();
requireLogin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentPath = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
$pageFolder = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?><?= APP_SHORT ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Noto+Serif:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-patch-check-fill"></i></div>
        <div>
            <div class="brand-title"><?= APP_SHORT ?></div>
            <div class="brand-sub">Sistem Informasi BLT</div>
        </div>
    </div>

    <div class="sidebar-instansi">
        <small><?= APP_INSTANSI ?></small>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">UTAMA</div>
        <a href="<?= BASE_URL ?>dashboard.php" class="nav-item <?= basename($currentPath) === 'htdocs' || basename($currentPath) === 'blt-dashboardddd' || $currentPage === 'dashboard' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <?php if (in_array($currentUser['role'], ['admin', 'atasan'])): ?>
        <div class="nav-label">DATA</div>
        <a href="<?= BASE_URL ?>pages/peserta/index.php" class="nav-item <?= $pageFolder === 'peserta' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i> Data Peserta
        </a>
        <?php endif; ?>



        <div class="nav-label">PENDUKUNG KEPUTUSAN</div>
        <?php if (in_array($currentUser['role'], ['admin'])): ?>
        <a href="<?= BASE_URL ?>pages/saw/kriteria.php" class="nav-item <?= $pageFolder === 'saw' && $currentPage === 'kriteria' ? 'active' : '' ?>">
            <i class="bi bi-sliders"></i> Kriteria SAW
        </a>
        <a href="<?= BASE_URL ?>pages/saw/sub_kriteria.php" class="nav-item <?= $pageFolder === 'saw' && $currentPage === 'sub_kriteria' ? 'active' : '' ?>">
            <i class="bi bi-list-check"></i> Sub Kriteria SAW
        </a>
        <?php endif; ?>
        <?php if (in_array($currentUser['role'], ['atasan'])): ?>
        <a href="<?= BASE_URL ?>pages/saw/penilaian.php" class="nav-item <?= $pageFolder === 'saw' && $currentPage === 'penilaian' ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-pulse"></i> Input Penilaian SAW
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>pages/saw/perhitungan.php" class="nav-item <?= $pageFolder === 'saw' && $currentPage === 'perhitungan' ? 'active' : '' ?>">
            <i class="bi bi-calculator"></i> Perhitungan SAW
        </a>
        <a href="<?= BASE_URL ?>pages/saw/hasil.php" class="nav-item <?= $pageFolder === 'saw' && $currentPage === 'hasil' ? 'active' : '' ?>">
            <i class="bi bi-trophy"></i> Hasil & Rekomendasi
        </a>

        <?php if ($currentUser['role'] === 'admin'): ?>
        <div class="nav-label">PENGATURAN</div>
        <a href="<?= BASE_URL ?>pages/users/index.php" class="nav-item <?= $pageFolder === 'users' ? 'active' : '' ?>">
            <i class="bi bi-person-gear"></i> Manajemen User
        </a>
        <?php endif; ?>
    </nav>
</div>

<!-- MAIN -->
<div class="main-wrapper" id="mainWrapper">
    <!-- TOPBAR -->
    <header class="topbar">
        <button class="btn btn-icon sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title"><?= isset($pageTitle) ? sanitize($pageTitle) : 'Dashboard' ?></div>
        <div class="topbar-right">
            <div class="topbar-date">
                <i class="bi bi-calendar3"></i>
                <?= formatTanggal(date('Y-m-d')) ?>
            </div>
            <div class="dropdown">
                <button class="btn btn-user dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="user-avatar"><?= strtoupper(substr($currentUser['nama'], 0, 1)) ?></div>
                    <div class="user-info d-none d-md-block">
                        <div class="user-name"><?= sanitize($currentUser['nama']) ?></div>
                        <div class="user-role"><?= getRoleLabel($currentUser['role']) ?></div>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>pages/profil.php"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>pages/ubah_password.php"><i class="bi bi-key me-2"></i>Ubah Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="main-content">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= sanitize($_GET['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= sanitize($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
