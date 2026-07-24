<?php
require_once 'includes/functions.php';

$hash = password_hash('password', PASSWORD_DEFAULT);

$db = getDB();
$db->query("UPDATE users SET password = '$hash'");

echo "Password semua user berhasil direset ke: <strong>password</strong>";
echo "<br>Hash: " . $hash;
echo "<br><br><a href='login.php'>Klik di sini untuk login</a>";
echo "<br><br><strong style='color:red'>HAPUS file ini setelah selesai!</strong>";
?>