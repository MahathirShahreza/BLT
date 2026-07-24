<?php
require_once 'config.php';
require_once 'includes/functions.php';

echo "=== CEK DATABASE ===\n\n";

// Cek koneksi
try {
    $db = getDB();
    echo "✓ Database terkoneksi: " . DB_NAME . "\n\n";
} catch (Exception $e) {
    echo "✗ Koneksi gagal: " . $e->getMessage() . "\n";
    exit;
}

// Cek tabel users
echo "=== DAFTAR USER ===\n";
$users = dbFetchAll("SELECT id, nama, username, role, status FROM users");

if (empty($users)) {
    echo "⚠ Tidak ada user di database!\n";
    echo "Cara fix:\n";
    echo "1. Import database.sql di phpMyAdmin\n";
    echo "2. Atau jalankan reset_password.php\n";
} else {
    foreach ($users as $user) {
        echo "\n- ID: {$user['id']}\n";
        echo "  Nama: {$user['nama']}\n";
        echo "  Username: {$user['username']}\n";
        echo "  Role: {$user['role']}\n";
        echo "  Status: {$user['status']}\n";
    }
}

echo "\n\n=== SOLUSI ===\n";
echo "Jika tidak ada user, jalankan: http://localhost/blt-dashboard/reset_password.php\n";
echo "Username: admin, atasan, sekretaris\n";
echo "Password: password\n";
?>
