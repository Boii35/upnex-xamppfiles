<?php
// ============================================================
//  UPNEX — Entry Point (index.php)
//  Pattern: Front Controller
// ============================================================

require_once __DIR__ . '/config/config.php';

// ── Session bảo mật ──────────────────────────────────────────
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Tái tạo session ID mỗi 30 phút → chống Session Fixation
if (!isset($_SESSION['_last_regenerate'])) {
    session_regenerate_id(true);
    $_SESSION['_last_regenerate'] = time();
} elseif (time() - $_SESSION['_last_regenerate'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_last_regenerate'] = time();
}

// ── CSRF Token ───────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Auto-login bằng Cookie ───────────────────────────────────
if (empty($_SESSION[SESSION_USER])) {
    require_once __DIR__ . '/model/UserModel.php';
    $cookieUser = (new UserModel())->loginByCookie();
    if ($cookieUser) {
        $_SESSION[SESSION_USER] = [
            'id'    => $cookieUser['id'],
            'name'  => $cookieUser['name'],
            'email' => $cookieUser['email'],
            'tier'  => $cookieUser['tier'],
        ];
    }
}

// ── CSRF helper ───────────────────────────────────────────────
function verifyCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ── Route ─────────────────────────────────────────────────────
$case = $_GET['case'] ?? 'home';

$adminCases = [
    'admin', 'admin_login', 'admin_logout',
    'admin_products', 'admin_product_add', 'admin_product_edit', 'admin_product_delete',
    'admin_orders', 'admin_order_update',
    'admin_users', 'admin_user_lock',
    'admin_employees', 'admin_employee_add', 'admin_employee_lock',
    'admin_vouchers', 'admin_voucher_add', 'admin_voucher_delete',
    'admin_reviews', 'admin_review_toggle',
    'admin_revenue',
    'admin_categories', 'admin_category_add', 'admin_category_delete',
    'admin_send_voucher',
];

// Payment routes — MoMo callback (IPN không cần session)
$paymentCases = ['momo_pay', 'momo_return', 'momo_ipn'];

if (in_array($case, $adminCases)) {
    require_once __DIR__ . '/controller/AdminController.php';
    $controller = new AdminController();
} elseif (in_array($case, $paymentCases)) {
    require_once __DIR__ . '/controller/PaymentController.php';
    $controller = new PaymentController();
} else {
    require_once __DIR__ . '/controller/UserController.php';
    $controller = new UserController();
}

$controller->dispatch($case);
