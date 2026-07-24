<?php
// File untuk generate hash password
// Setelah selesai, HAPUS file ini!

$password = '123';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

echo "Password: " . $password . "<br>";
echo "Hash: " . $hash . "<br><br>";
echo "Copy hash di atas dan gunakan untuk UPDATE di phpMyAdmin:<br><br>";
echo "<code>UPDATE users SET password = '" . $hash . "' WHERE id IN (1,2,3);</code>";
?>
