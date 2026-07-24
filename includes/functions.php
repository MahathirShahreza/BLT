<?php
require_once __DIR__ . '/../config.php';

// ============================================================
// KONEKSI DATABASE
// ============================================================
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset(DB_CHARSET);
        if ($conn->connect_error) {
            die('<div style="padding:20px;background:#fee;border:1px solid #c00;margin:20px;border-radius:8px;font-family:sans-serif"><strong>Koneksi Database Gagal:</strong> ' . $conn->connect_error . '</div>');
        }
    }
    return $conn;
}

// ============================================================
// SESSION & AUTH
// ============================================================
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_destroy();
        header('Location: ' . BASE_URL . 'login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['user_role'], $roles)) {
        header('Location: ' . BASE_URL . 'dashboard.php?error=akses_ditolak');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'nama'  => $_SESSION['user_nama'],
        'role'  => $_SESSION['user_role'],
        'username' => $_SESSION['user_username'],
    ];
}

function getRoleLabel($role) {
    $labels = [
        'admin'      => 'Sekretaris',
        'atasan'     => 'Kepala Desa',
        'sekretaris' => 'Sekretaris',
    ];
    return $labels[$role] ?? ucfirst($role);
}

// ============================================================
// QUERY HELPERS
// ============================================================
function dbQuery($sql, $params = [], $types = '') {
    $db = getDB();
    $stmt = $db->prepare($sql);
    if (!$stmt) return false;
    if ($params) {
        if (!$types) {
            $types = str_repeat('s', count($params));
        }
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

function dbFetchAll($sql, $params = [], $types = '') {
    $stmt = dbQuery($sql, $params, $types);
    if (!$stmt) return [];
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function dbFetchOne($sql, $params = [], $types = '') {
    $stmt = dbQuery($sql, $params, $types);
    if (!$stmt) return null;
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function dbInsert($sql, $params = [], $types = '') {
    $stmt = dbQuery($sql, $params, $types);
    if (!$stmt) return false;
    return getDB()->insert_id;
}

function dbCount($table, $where = '', $params = [], $types = '') {
    $sql = "SELECT COUNT(*) as total FROM $table" . ($where ? " WHERE $where" : '');
    $row = dbFetchOne($sql, $params, $types);
    return $row ? (int)$row['total'] : 0;
}

// ============================================================
// LOG AKTIVITAS
// ============================================================
function logAktivitas($aksi, $keterangan = '') {
    $user = getCurrentUser();
    $userId = $user ? $user['id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if ($userId) {
        dbQuery("INSERT INTO log_aktivitas (user_id, aksi, keterangan, ip_address) VALUES (?, ?, ?, ?)",
            [$userId, $aksi, $keterangan, $ip]);
    }
}

// ============================================================
// UPLOAD FILE
// ============================================================
function uploadFile($fileInput, $subfolder = 'dokumen', $allowedTypes = null) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File tidak valid atau tidak diunggah'];
    }
    $file = $_FILES[$fileInput];
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'Ukuran file melebihi 5MB'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = $allowedTypes ?? ALLOWED_DOC_TYPES;
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan (' . implode(', ', $allowed) . ')'];
    }
    $destDir = UPLOAD_PATH . $subfolder . '/';
    if (!is_dir($destDir)) mkdir($destDir, 0775, true);
    $newName = uniqid() . '_' . time() . '.' . $ext;
    $destPath = $destDir . $newName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'message' => 'Gagal menyimpan file'];
    }
    return ['success' => true, 'filename' => $subfolder . '/' . $newName, 'original' => $file['name'], 'size' => $file['size']];
}

// ============================================================
// FORMAT HELPERS
// ============================================================
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatTanggal($date, $withTime = false) {
    if (!$date) return '-';
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
              'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($date);
    $d = date('d', $ts);
    $m = $bulan[(int)date('m', $ts)];
    $y = date('Y', $ts);
    $result = "$d $m $y";
    if ($withTime) $result .= ' ' . date('H:i', $ts);
    return $result;
}

function sanitize($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function badgeStatus($status) {
    $map = [
        'menunggu'   => ['warning', 'Menunggu'],
        'disetujui'  => ['success', 'Disetujui'],
        'ditolak'    => ['danger', 'Ditolak'],
        'aktif'      => ['success', 'Aktif'],
        'nonaktif'   => ['secondary', 'Nonaktif'],
        'sudah_bayar'=> ['success', 'Sudah Dibayar'],
        'gagal'      => ['danger', 'Gagal'],
        'draft'      => ['secondary', 'Draft'],
        'selesai'    => ['info', 'Selesai'],
    ];
    $info = $map[$status] ?? ['secondary', ucfirst($status)];
    return '<span class="badge bg-' . $info[0] . '">' . $info[1] . '</span>';
}

function pagination($totalRows, $currentPage, $baseUrl, $perPage = ROWS_PER_PAGE) {
    $totalPages = ceil($totalRows / $perPage);
    if ($totalPages <= 1) return '';
    $html = '<nav><ul class="pagination pagination-sm mb-0">';
    $html .= '<li class="page-item ' . ($currentPage <= 1 ? 'disabled' : '') . '"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage - 1) . '">‹</a></li>';
    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        $html .= '<li class="page-item ' . ($i == $currentPage ? 'active' : '') . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }
    $html .= '<li class="page-item ' . ($currentPage >= $totalPages ? 'disabled' : '') . '"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage + 1) . '">›</a></li>';
    $html .= '</ul></nav>';
    return $html;
}
