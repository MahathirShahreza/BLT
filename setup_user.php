<?php
require_once 'config.php';
require_once 'includes/functions.php';

echo "<h2>Setup User Otomatis</h2>";

$db = getDB();

// Cek berapa user ada
$count = dbFetchOne("SELECT COUNT(*) as total FROM users");
$total = $count['total'] ?? 0;

if ($total > 0) {
    echo "<p style='color:green'>✓ Sudah ada " . $total . " user di database</p>";
    echo "<p><a href='login.php'>Langsung ke Login</a></p>";
    exit;
}

echo "<p>Membuat user default...</p>";

// Hash password
$password = password_hash('password', PASSWORD_DEFAULT);

// User 1: Admin
$db->query("INSERT INTO users (nama, username, password, role, email, status) 
           VALUES ('Administrator', 'admin', '$password', 'admin', 'admin@example.com', 'aktif')");

// User 2: Atasan
$db->query("INSERT INTO users (nama, username, password, role, email, status) 
           VALUES ('Atasan Verifikasi', 'atasan', '$password', 'atasan', 'atasan@example.com', 'aktif')");

// User 3: Sekretaris
$db->query("INSERT INTO users (nama, username, password, role, email, status)
           VALUES ('Sekretaris', 'sekretaris', '$password', 'sekretaris', 'sekretaris@example.com', 'aktif')");

echo "<div style='background:#d4edda; padding:20px; border-radius:8px; margin:20px 0;'>";
echo "<h3 style='color:#155724; margin-top:0'>✓ User Berhasil Dibuat!</h3>";
echo "<table style='width:100%; border-collapse:collapse;'>";
echo "<tr style='background:#fff; border-bottom:1px solid #c3e6cb;'>";
echo "<th style='text-align:left; padding:8px'>Username</th>";
echo "<th style='text-align:left; padding:8px'>Password</th>";
echo "<th style='text-align:left; padding:8px'>Role</th>";
echo "</tr>";
echo "<tr style='border-bottom:1px solid #c3e6cb;'><td style='padding:8px'>admin</td><td style='padding:8px'>password</td><td style='padding:8px'>Admin</td></tr>";
echo "<tr style='border-bottom:1px solid #c3e6cb;'><td style='padding:8px'>atasan</td><td style='padding:8px'>password</td><td style='padding:8px'>Atasan</td></tr>";
echo "<tr><td style='padding:8px'>sekretaris</td><td style='padding:8px'>password</td><td style='padding:8px'>Sekretaris</td></tr>";
echo "</table>";
echo "</div>";

echo "<p><a href='login.php' style='display:inline-block; background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Klik untuk Login</a></p>";

echo "<hr>";
echo "<p style='color:red; font-weight:bold;'>⚠ HAPUS FILE: setup_user.php SETELAH SELESAI (untuk keamanan)</p>";
?>
