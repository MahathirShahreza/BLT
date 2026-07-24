<?php
require_once 'includes/functions.php';
startSession();

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $user = dbFetchOne("SELECT * FROM users WHERE username = ? AND status = 'aktif'", [$username]);
        
        // DEBUG: Cek apakah user ditemukan
        if (!$user) {
            $error = '❌ DEBUG: User "' . $username . '" tidak ditemukan atau tidak aktif!';
        } else {
            // DEBUG: Cek password verify
            $passVerify = password_verify($password, $user['password']);
            if ($passVerify) {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user_nama']     = $user['nama'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['last_activity'] = time();
            // Update last login
            dbQuery("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
            logAktivitas('Login', 'Login berhasil');
            header('Location: ' . BASE_URL . 'dashboard.php');
            exit;
            } else {
                $error = '❌ DEBUG: Password tidak cocok untuk user "' . $username . '"';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Noto+Serif:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html, body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body.login-body {
            min-height: 100vh;
            background: linear-gradient(135deg, #006ec8 0%, #0077d4 50%, #0084db 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        body.login-body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: repeating-linear-gradient(45deg, transparent 0, transparent 50px, rgba(255,255,255,0.02) 50px, rgba(255,255,255,0.02) 100px);
            animation: slide 25s linear infinite;
        }

        @keyframes slide {
            0% { transform: translate(0, 0); }
            100% { transform: translate(100px, 100px); }
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 500px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 50px 42px;
            box-shadow: 0 20px 70px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-size: 32px;
            font-weight: 800;
            color: #006ec8;
            margin: 0 0 8px 0;
            letter-spacing: 2px;
        }

        .login-header p {
            font-size: 13px;
            color: #999;
            margin: 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        .login-alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease-out;
        }

        .login-alert.alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .login-alert.alert-danger {
            background: #f8d7da;
            color: #a02020;
            border: 1px solid #f5c6cb;
        }

        .login-alert i {
            font-size: 16px;
            flex-shrink: 0;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 30px;
        }

        .login-input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .login-input {
            width: 100%;
            padding: 14px 18px;
            border: none;
            border-radius: 12px;
            background: #f0f0f6;
            font-size: 14px;
            color: #333;
            transition: all 0.3s ease;
            font-weight: 500;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .login-input::placeholder {
            color: #b0b3bf;
            font-weight: 500;
        }

        .login-input:focus {
            outline: none !important;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0, 110, 200, 0.15);
            border: none;
        }

        .login-password-toggle {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            color: #999;
            font-size: 16px;
            padding: 4px 8px;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-password-toggle:hover {
            color: #006ec8;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #52c77a 0%, #45b56e 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.8px;
            box-shadow: 0 4px 15px rgba(82, 199, 122, 0.3);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(82, 199, 122, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #f0f0f6;
            font-size: 12px;
            color: #999;
        }

        .login-footer p {
            margin: 4px 0;
            font-weight: 600;
            line-height: 1.4;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 32px 20px;
                border-radius: 16px;
            }
            .login-header h2 { font-size: 22px; }
            .login-input { padding: 11px 14px; font-size: 13px; }
            .login-btn { padding: 11px; font-size: 13px; }
        }
    </style>
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <h2>WELCOME</h2>
                    <p><?= APP_SHORT ?></p>
                </div>

                <?php if (isset($_GET['timeout'])): ?>
                    <div class="login-alert alert-warning"><i class="bi bi-clock"></i>Sesi Anda telah berakhir. Silakan login kembali.</div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="login-alert alert-danger"><i class="bi bi-exclamation-triangle"></i><?= sanitize($error) ?></div>
                <?php endif; ?>

                <form method="POST" autocomplete="off" class="login-form">
                    <div class="login-input-group">
                        <input type="text" class="login-input" name="username" value="<?= sanitize($_POST['username'] ?? '') ?>" required autofocus placeholder="Username">
                    </div>

                    <div class="login-input-group">
                        <input type="password" class="login-input" name="password" id="passwordInput" required placeholder="Password">
                        <button class="login-password-toggle" type="button" onclick="togglePass()">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>

                    <button type="submit" class="login-btn">MASUK</button>
                </form>

                <div class="login-footer">
                    <p><?= APP_INSTANSI ?></p>
                    <p><?= APP_ALAMAT ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
    function togglePass() {
        const p = document.getElementById('passwordInput');
        const e = document.getElementById('eyeIcon');
        if (p.type === 'password') { p.type = 'text'; e.className = 'bi bi-eye-slash'; }
        else { p.type = 'password'; e.className = 'bi bi-eye'; }
    }
    </script>
</body>
</html>
