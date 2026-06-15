<?php
// ============================================================
//  UPNEX — Cấu hình hệ thống
//  ⚠️  Đổi các giá trị có dấu ← trước khi chạy
// ============================================================

// ── Môi trường ───────────────────────────────────────────────
define('APP_NAME',   'UPNEX');
define('BASE_URL',   'http://localhost/upnex');   // ← Đổi nếu tên thư mục khác
define('ROOT_PATH',  dirname(__DIR__));

// ── Database ─────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'upnex');
define('DB_USER',    'root');
define('DB_PASS',    '');                          // ← Mật khẩu MySQL nếu có
define('DB_CHARSET', 'utf8mb4');

// ── Session & Cookie ─────────────────────────────────────────
define('SESSION_USER',    'upnex_user');
define('SESSION_ADMIN',   'upnex_admin');
define('COOKIE_NAME',     'upnex_remember');
define('COOKIE_LIFETIME', 60 * 60 * 24 * 30);    // 30 ngày

// ── Upload ───────────────────────────────────────────────────
define('UPLOAD_PATH',  ROOT_PATH . '/uploads/products/');
define('UPLOAD_URL',   BASE_URL  . '/uploads/products/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);          // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// ── Phân loại VIP (tổng chi tiêu VNĐ) ───────────────────────
define('TIER_GOLD',    5_000_000);
define('TIER_DIAMOND', 20_000_000);

// ── Phân trang ───────────────────────────────────────────────
define('ITEMS_PER_PAGE', 12);

// ============================================================
//  📧 PHPMailer — Gmail SMTP
//  Hướng dẫn lấy App Password:
//  Google Account → Bảo mật → Xác minh 2 bước → App Password
// ============================================================
define('MAIL_ENABLED',    false);                  // ← Đổi true sau khi cấu hình
define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',   'your_gmail@gmail.com'); // ← Gmail của bạn
define('MAIL_PASSWORD',   'xxxx xxxx xxxx xxxx'); // ← App Password 16 ký tự
define('MAIL_FROM',       'your_gmail@gmail.com'); // ← Giống MAIL_USERNAME
define('MAIL_FROM_NAME',  APP_NAME . ' Store');

// ============================================================
//  📱 MoMo Payment Gateway — Sandbox
//  Thông tin sandbox chính thức từ MoMo Developer Portal
//  Đăng ký: https://developers.momo.vn
// ============================================================
define('MOMO_ENABLED',      true);                 // Sandbox luôn bật được
define('MOMO_ENV',          'sandbox');            // 'sandbox' | 'production'
define('MOMO_PARTNER_CODE', 'MOMOBKUN20180529');   // Sandbox partner code
define('MOMO_ACCESS_KEY',   'klm05TvNBzhg7h7j');  // Sandbox access key
define('MOMO_SECRET_KEY',   'at67qH6mk8w5Y1nAyMoTku1i08zALTH7'); // Sandbox secret
define('MOMO_ENDPOINT',     'https://test-payment.momo.vn/v2/gateway/api/create');
define('MOMO_REDIRECT_URL', BASE_URL . '/?case=momo_return');
define('MOMO_IPN_URL',      BASE_URL . '/?case=momo_ipn');
// Tài khoản test sandbox MoMo:
//   SĐT: 0000000001  PIN: 000000  OTP: 000000

// ── Dev mode ─────────────────────────────────────────────────
define('DEV_MODE', true);
if (DEV_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
