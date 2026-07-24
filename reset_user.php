<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Hash untuk password "123"
$password_hash = password_hash('123', PASSWORD_BCRYPT);

$db = getDB();

// Delete user lama dulu
$db->query("DELETE FROM users");

// Insert user baru dengan password "123"
$db->query("INSERT INTO users (nama, username, password, role, email, telepon, status) VALUES 
('Administrator', 'admin', '$password_hash', 'admin', 'admin@blt.go.id', '081234567890', 'aktif'),
('Atasan Desa', 'atasan', '$password_hash', 'atasan', 'atasan@blt.go.id', '081234567891', 'aktif'),
('Sekretaris Desa', 'sekretaris', '$password_hash', 'sekretaris', 'sekretaris@blt.go.id', '081234567892', 'aktif')");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset User</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        a:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="box">
        <h2>✓ User Reset Berhasil!</h2>
        <div class="success">
            <strong>3 user default telah dibuat dengan password: <code>123</code></strong>
        </div>
        
        <table>
            <tr>
                <th>Username</th>
                <th>Password</th>
                <th>Role</th>
            </tr>
            <tr>
                <td><strong>admin</strong></td>
                <td><strong>123</strong></td>
                <td>Administrator</td>
            </tr>
            <tr>
                <td><strong>atasan</strong></td>
                <td><strong>123</strong></td>
                <td>Atasan Desa</td>
            </tr>
            <tr>
                <td><strong>sekretaris</strong></td>
                <td><strong>123</strong></td>
                <td>Sekretaris Desa</td>
            </tr>
        </table>
        
        <a href="login.php">Ke Login</a>
    </div>
</body>
</html>
