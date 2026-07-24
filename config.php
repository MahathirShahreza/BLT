<?php
// ============================================================
// KONFIGURASI SISTEM BLT
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'beelte');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'SisBLT - Sistem Informasi Bantuan Langsung Tunai');
define('APP_SHORT', 'SisBLT');
define('APP_VERSION', '1.0.0');
define('APP_INSTANSI', 'Pemerintah Pulau Tamang, Mandailing Natal');
define('APP_ALAMAT', 'Pulau Tamang, Kabupaten Mandailing Natal, Sumatera Utara');
define('APP_TAHUN', date('Y'));

define('BASE_URL', 'http://localhost/blt-dashboardddd/');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_DOC_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);
define('ALLOWED_IMG_TYPES', ['jpg', 'jpeg', 'png']);

define('SESSION_TIMEOUT', 3600); // 1 jam
define('ROWS_PER_PAGE', 10);
