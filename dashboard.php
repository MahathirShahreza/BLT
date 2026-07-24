<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$role = $currentUser['role'];
?>

<!-- Menu Akses Cepat -->
<div class="container-fluid p-4">
    <div class="mb-4">
        <h5 class="text-muted">Akses Cepat Menu</h5>
    </div>

    <div class="row g-3">
        <!-- Data Peserta -->
        <div class="col-md-4 col-lg-3">
            <a href="pages/peserta/index.php" class="card h-100 text-decoration-none shadow-sm" style="border-left:4px solid #2A5298;transition:all 0.3s;cursor:pointer">
                <div class="card-body text-center py-4">
                    <div style="font-size:50px;color:#2A5298;margin-bottom:16px"><i class="bi bi-people-fill"></i></div>
                    <h6 class="fw-bold mb-2">Data Peserta</h6>
                    <p class="text-muted" style="font-size:13px">Kelola data peserta penerima BLT</p>
                </div>
                <div class="card-footer bg-transparent border-top-0 text-center pb-3">
                    <small class="text-primary fw-semibold">Buka →</small>
                </div>
            </a>
        </div>

        <!-- Kriteria SAW (Admin Only) -->
        <?php if ($role === 'admin'): ?>
        <div class="col-md-4 col-lg-3">
            <a href="pages/saw/kriteria.php" class="card h-100 text-decoration-none shadow-sm" style="border-left:4px solid #7B68EE;transition:all 0.3s;cursor:pointer">
                <div class="card-body text-center py-4">
                    <div style="font-size:50px;color:#7B68EE;margin-bottom:16px"><i class="bi bi-sliders"></i></div>
                    <h6 class="fw-bold mb-2">Kriteria SAW</h6>
                    <p class="text-muted" style="font-size:13px">Atur kriteria penilaian</p>
                </div>
                <div class="card-footer bg-transparent border-top-0 text-center pb-3">
                    <small class="text-primary fw-semibold">Buka →</small>
                </div>
            </a>
        </div>

        <!-- Sub Kriteria SAW (Admin Only) -->
        <div class="col-md-4 col-lg-3">
            <a href="pages/saw/sub_kriteria.php" class="card h-100 text-decoration-none shadow-sm" style="border-left:4px solid #FF6B6B;transition:all 0.3s;cursor:pointer">
                <div class="card-body text-center py-4">
                    <div style="font-size:50px;color:#FF6B6B;margin-bottom:16px"><i class="bi bi-list-check"></i></div>
                    <h6 class="fw-bold mb-2">Sub Kriteria SAW</h6>
                    <p class="text-muted" style="font-size:13px">Kelola sub kriteria penilaian</p>
                </div>
                <div class="card-footer bg-transparent border-top-0 text-center pb-3">
                    <small class="text-primary fw-semibold">Buka →</small>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <!-- Perhitungan SAW -->
        <div class="col-md-4 col-lg-3">
            <a href="pages/saw/perhitungan.php" class="card h-100 text-decoration-none shadow-sm" style="border-left:4px solid #4ECDC4;transition:all 0.3s;cursor:pointer">
                <div class="card-body text-center py-4">
                    <div style="font-size:50px;color:#4ECDC4;margin-bottom:16px"><i class="bi bi-calculator"></i></div>
                    <h6 class="fw-bold mb-2">Perhitungan SAW</h6>
                    <p class="text-muted" style="font-size:13px">Hitung nilai SAW peserta</p>
                </div>
                <div class="card-footer bg-transparent border-top-0 text-center pb-3">
                    <small class="text-primary fw-semibold">Buka →</small>
                </div>
            </a>
        </div>

        <!-- Hasil & Rekomendasi -->
        <div class="col-md-4 col-lg-3">
            <a href="pages/saw/hasil.php" class="card h-100 text-decoration-none shadow-sm" style="border-left:4px solid #FFD700;transition:all 0.3s;cursor:pointer">
                <div class="card-body text-center py-4">
                    <div style="font-size:50px;color:#FFD700;margin-bottom:16px"><i class="bi bi-trophy"></i></div>
                    <h6 class="fw-bold mb-2">Hasil & Rekomendasi</h6>
                    <p class="text-muted" style="font-size:13px">Lihat hasil dan rekomendasi SAW</p>
                </div>
                <div class="card-footer bg-transparent border-top-0 text-center pb-3">
                    <small class="text-primary fw-semibold">Buka →</small>
                </div>
            </a>
        </div>

        <!-- Manajemen User (Admin Only) -->
        <?php if ($role === 'admin'): ?>
        <div class="col-md-4 col-lg-3">
            <a href="pages/users/index.php" class="card h-100 text-decoration-none shadow-sm" style="border-left:4px solid #E74C3C;transition:all 0.3s;cursor:pointer">
                <div class="card-body text-center py-4">
                    <div style="font-size:50px;color:#E74C3C;margin-bottom:16px"><i class="bi bi-person-gear"></i></div>
                    <h6 class="fw-bold mb-2">Manajemen User</h6>
                    <p class="text-muted" style="font-size:13px">Kelola user dan permission</p>
                </div>
                <div class="card-footer bg-transparent border-top-0 text-center pb-3">
                    <small class="text-primary fw-semibold">Buka →</small>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
