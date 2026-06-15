<?php
// ============================================================
//  Controller: AdminController
//  - Session bảo vệ toàn bộ admin area
//  - CRUD: sản phẩm, đơn hàng, user, nhân viên, voucher
//  - AJAX endpoints cho admin dashboard
// ============================================================

require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../model/ProductModel.php';
require_once __DIR__ . '/../model/OrderModel.php';
require_once __DIR__ . '/../model/CategoryModel.php';
require_once __DIR__ . '/../model/VoucherModel.php';
require_once __DIR__ . '/../model/EmployeeModel.php';

class AdminController
{
    private ProductModel  $productModel;
    private OrderModel    $orderModel;
    private UserModel     $userModel;
    private CategoryModel $categoryModel;
    private VoucherModel  $voucherModel;
    private EmployeeModel $employeeModel;

    public function __construct()
    {
        $this->productModel  = new ProductModel();
        $this->orderModel    = new OrderModel();
        $this->userModel     = new UserModel();
        $this->categoryModel = new CategoryModel();
        $this->voucherModel  = new VoucherModel();
        $this->employeeModel = new EmployeeModel();
    }

    // ── Router ───────────────────────────────────────────────
    public function dispatch(string $case): void
    {
        // Cho phép login mà không cần session admin
        if ($case === 'admin_login') { $this->login(); return; }

        // Tất cả route khác → bắt buộc session admin
        $this->requireAdmin();

        match($case) {
            'admin', 'admin_dashboard' => $this->dashboard(),

            // Sản phẩm
            'admin_products'        => $this->products(),
            'admin_product_add'     => $this->productAdd(),
            'admin_product_edit'    => $this->productEdit(),
            'admin_product_delete'  => $this->productDelete(),

            // Đơn hàng
            'admin_orders'          => $this->orders(),
            'admin_order_update'    => $this->orderUpdate(),  // AJAX

            // Khách hàng
            'admin_users'           => $this->users(),
            'admin_user_lock'       => $this->userLock(),     // AJAX

            // Nhân viên
            'admin_employees'       => $this->employees(),
            'admin_employee_add'    => $this->employeeAdd(),
            'admin_employee_lock'   => $this->employeeLock(), // AJAX

            // Voucher
            'admin_vouchers'        => $this->vouchers(),
            'admin_voucher_add'     => $this->voucherAdd(),
            'admin_voucher_delete'  => $this->voucherDelete(),

            // Đánh giá
            'admin_reviews'         => $this->reviews(),
            'admin_review_toggle'   => $this->reviewToggle(),  // AJAX

            // Danh mục
            'admin_categories'      => $this->categories(),
            'admin_category_add'    => $this->categoryAdd(),
            'admin_category_delete' => $this->categoryDelete(),

            // Doanh thu
            'admin_revenue'         => $this->revenue(),

            // Email voucher
            'admin_send_voucher'    => $this->sendVoucherEmail(),

            // Logout
            'admin_logout'          => $this->logout(),

            default => $this->dashboard(),
        };
    }

    // ── Đăng nhập Admin ──────────────────────────────────────
    private function login(): void
    {
        if (!empty($_SESSION[SESSION_ADMIN])) { $this->redirect('?case=admin'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../model/EmployeeModel.php';
            $employee = $this->employeeModel->login(
                $_POST['email'] ?? '',
                $_POST['password'] ?? ''
            );

            if ($employee) {
                // Tạo lại session ID để tránh session fixation
                session_regenerate_id(true);
                $_SESSION[SESSION_ADMIN] = [
                    'id'       => $employee['id'],
                    'name'     => $employee['name'],
                    'email'    => $employee['email'],
                    'position' => $employee['position'],
                ];
                $this->redirect('?case=admin');
            } else {
                $this->adminView('admin/login', ['error' => 'Email hoặc mật khẩu không đúng.']);
            }
            return;
        }
        $this->adminView('admin/login', ['error' => '']);
    }

    private function logout(): void
    {
        unset($_SESSION[SESSION_ADMIN]);
        session_regenerate_id(true);
        $this->redirect('?case=admin_login');
    }

    // ── Dashboard ────────────────────────────────────────────
    private function dashboard(): void
    {
        $stats = [
            'total_orders'   => Database::getInstance()->fetchOne("SELECT COUNT(*) AS c FROM orders")['c'] ?? 0,
            'pending_orders' => Database::getInstance()->fetchOne("SELECT COUNT(*) AS c FROM orders WHERE status='pending'")['c'] ?? 0,
            'total_users'    => Database::getInstance()->fetchOne("SELECT COUNT(*) AS c FROM users")['c'] ?? 0,
            'total_revenue'  => Database::getInstance()->fetchOne("SELECT SUM(total) AS c FROM orders WHERE status='completed'")['c'] ?? 0,
            'total_products' => Database::getInstance()->fetchOne("SELECT COUNT(*) AS c FROM products WHERE is_active=1")['c'] ?? 0,
        ];
        $recentOrders  = $this->orderModel->getAdminList(1)['orders'];
        $bestSellers   = $this->productModel->getBestSellers(5);
        $revenueStats  = $this->orderModel->getRevenueStats('month');
        $this->adminView('admin/dashboard', compact('stats', 'recentOrders', 'bestSellers', 'revenueStats'));
    }

    // ── Sản phẩm ─────────────────────────────────────────────
    private function products(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $keyword = $_GET['q'] ?? '';
        $result  = $this->productModel->getAdminList($page, $keyword);
        $this->adminView('admin/products', $result + compact('keyword'));
    }

    private function productAdd(): void
    {
        $categories = $this->categoryModel->getAll();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrf()) { $this->redirect('?case=admin_product_add'); return; }
            $result = $this->productModel->create($_POST);
            if ($result['success']) {
                // Upload ảnh nếu có
                if (!empty($_FILES['images']['name'][0])) {
                    $this->handleImageUpload($result['id'], $_FILES['images']);
                }
                $this->redirect('?case=admin_products&added=1');
            } else {
                $this->adminView('admin/product_form', ['errors' => $result['errors'], 'old' => $_POST, 'categories' => $categories, 'product' => null]);
            }
            return;
        }
        $this->adminView('admin/product_form', ['errors' => [], 'old' => [], 'categories' => $categories, 'product' => null]);
    }

    private function productEdit(): void
    {
        $id      = (int)($_GET['id'] ?? 0);
        $product = $this->productModel->getDetail($id);
        if (!$product) { $this->redirect('?case=admin_products'); return; }
        $categories = $this->categoryModel->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrf()) { $this->redirect('?case=admin_product_edit&id=' . $id); return; }
            $result = $this->productModel->update($id, $_POST);
            if ($result['success']) {
                if (!empty($_FILES['images']['name'][0])) {
                    $this->handleImageUpload($id, $_FILES['images']);
                }
                $this->redirect('?case=admin_products&updated=1');
            } else {
                $this->adminView('admin/product_form', ['errors' => $result['errors'], 'old' => $_POST, 'categories' => $categories, 'product' => $product]);
            }
            return;
        }
        $this->adminView('admin/product_form', ['errors' => [], 'old' => $product, 'categories' => $categories, 'product' => $product]);
    }

    private function productDelete(): void
    {
        if (!verifyCsrf()) { $this->jsonResponse(['success' => false]); return; }
        $id = (int)($_POST['id'] ?? 0);
        // Soft delete
        Database::getInstance()->query("UPDATE products SET is_active = 0 WHERE id = ?", [$id]);
        $this->jsonResponse(['success' => true]);
    }

    // ── Đơn hàng ─────────────────────────────────────────────
    private function orders(): void
    {
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $status = $_GET['status'] ?? '';
        $result = $this->orderModel->getAdminList($page, $status);
        $this->adminView('admin/orders', $result + compact('status'));
    }

    private function orderUpdate(): void  // AJAX
    {
        if (!verifyCsrf()) { $this->jsonResponse(['success' => false, 'message' => 'CSRF error']); return; }
        $orderId    = (int)($_POST['order_id'] ?? 0);
        $status     = $_POST['status'] ?? '';
        $note       = $_POST['note'] ?? '';
        $employeeId = $_SESSION[SESSION_ADMIN]['id'];
        $result     = $this->orderModel->updateStatus($orderId, $status, $employeeId, $note);

        // Gửi email thông báo cập nhật trạng thái cho khách hàng
        if ($result['success']) {
            try {
                require_once __DIR__ . '/../includes/MailService.php';
                $order = $this->orderModel->getOrderDetail($orderId);
                if ($order) {
                    MailService::sendStatusUpdate($order, [
                        'name'  => $order['user_name'],
                        'email' => $order['user_email'],
                    ]);
                }
            } catch (\Exception $e) {
                error_log('[Admin Mail Error] ' . $e->getMessage());
            }
        }

        $this->jsonResponse($result);
    }

    // ── Khách hàng ───────────────────────────────────────────
    private function users(): void
    {
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $users = $this->userModel->getWithPagination($page);
        $total = $this->userModel->count();
        $this->adminView('admin/users', compact('users', 'total', 'page'));
    }

    private function userLock(): void  // AJAX
    {
        if (!verifyCsrf()) { $this->jsonResponse(['success' => false]); return; }
        $id = (int)($_POST['id'] ?? 0);
        $this->userModel->toggleLock($id);
        $this->jsonResponse(['success' => true]);
    }

    // ── Nhân viên ────────────────────────────────────────────
    private function employees(): void
    {
        $employees = $this->employeeModel->getAll();
        $this->adminView('admin/employees', compact('employees'));
    }

    private function employeeLock(): void  // AJAX
    {
        if (!verifyCsrf()) { $this->jsonResponse(['success' => false]); return; }
        $id = (int)($_POST['id'] ?? 0);
        // Không cho phép tự khóa chính mình
        if ($id === $_SESSION[SESSION_ADMIN]['id']) {
            $this->jsonResponse(['success' => false, 'message' => 'Không thể khóa tài khoản của chính mình.']);
            return;
        }
        $this->employeeModel->toggleLock($id);
        $this->jsonResponse(['success' => true]);
    }

    private function employeeAdd(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrf()) { $this->redirect('?case=admin_employees'); return; }
            $result = $this->employeeModel->create($_POST);
            $this->redirect('?case=admin_employees' . ($result['success'] ? '&added=1' : '&error=1'));
            return;
        }
        $this->adminView('admin/employee_form', ['errors' => []]);
    }

    // ── Voucher ──────────────────────────────────────────────
    private function vouchers(): void
    {
        $vouchers = $this->voucherModel->getAll('expires_at DESC');
        $this->adminView('admin/vouchers', compact('vouchers'));
    }

    private function voucherAdd(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrf()) { $this->redirect('?case=admin_vouchers'); return; }
            $result = $this->voucherModel->create($_POST);
            $this->redirect('?case=admin_vouchers' . ($result['success'] ? '&added=1' : '&error=' . urlencode($result['message'] ?? '')));
        }
    }

    private function voucherDelete(): void  // AJAX
    {
        if (!verifyCsrf()) { $this->jsonResponse(['success' => false]); return; }
        $id = (int)($_POST['id'] ?? 0);
        $this->voucherModel->delete($id);
        $this->jsonResponse(['success' => true]);
    }

    // ── Đánh giá ─────────────────────────────────────────────
    private function reviews(): void
    {
        $reviews = Database::getInstance()->fetchAll(
            "SELECT r.*, u.name AS user_name, p.name AS product_name
             FROM reviews r JOIN users u ON r.user_id = u.id JOIN products p ON r.product_id = p.id
             ORDER BY r.created_at DESC LIMIT 100"
        );
        $this->adminView('admin/reviews', compact('reviews'));
    }

    private function reviewToggle(): void  // AJAX
    {
        if (!verifyCsrf()) { $this->jsonResponse(['success' => false]); return; }
        $id = (int)($_POST['id'] ?? 0);
        Database::getInstance()->query("UPDATE reviews SET is_visible = NOT is_visible WHERE id = ?", [$id]);
        $this->jsonResponse(['success' => true]);
    }

    // ── Danh mục ─────────────────────────────────────────────
    private function categories(): void
    {
        $categories = $this->categoryModel->getAll();
        $this->adminView('admin/categories', compact('categories'));
    }

    private function categoryAdd(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrf()) { $this->redirect('?case=admin_categories'); return; }
            $result = $this->categoryModel->create($_POST);
            $this->redirect('?case=admin_categories' . ($result['success'] ? '&added=1' : ''));
        }
    }

    private function categoryDelete(): void
    {
        if (!verifyCsrf()) { $this->jsonResponse(['success' => false]); return; }
        $id = (int)($_POST['id'] ?? 0);
        $this->categoryModel->delete($id);
        $this->jsonResponse(['success' => true]);
    }

    // ── Doanh thu ────────────────────────────────────────────
    private function revenue(): void
    {
        $period      = $_GET['period'] ?? 'month';
        $stats       = $this->orderModel->getRevenueStats($period);
        $bestSellers = $this->productModel->getBestSellers(10);
        $worstSellers= $this->productModel->getWorstSellers(10);
        $this->adminView('admin/revenue', compact('stats', 'period', 'bestSellers', 'worstSellers'));
    }

    // ── Upload ảnh sản phẩm ──────────────────────────────────
    private function handleImageUpload(int $productId, array $files): void
    {
        $isPrimary = true;
        foreach ($files['tmp_name'] as $i => $tmpName) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
            if ($files['size'][$i] > MAX_FILE_SIZE) continue;
            if (!in_array($files['type'][$i], ALLOWED_TYPES)) continue;

            $ext      = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $filename = 'product_' . $productId . '_' . uniqid() . '.' . $ext;
            $dest     = UPLOAD_PATH . $filename;

            if (move_uploaded_file($tmpName, $dest)) {
                $this->productModel->addImage($productId, $filename, $isPrimary);
                $isPrimary = false;
            }
        }
    }

    // ── Helpers ──────────────────────────────────────────────
    private function adminView(string $template, array $data = []): void
    {
        extract($data);
        $admin     = $_SESSION[SESSION_ADMIN];
        $csrfToken = $_SESSION['csrf_token'];
        require_once __DIR__ . '/../view/admin/layout_header.php';
        require_once __DIR__ . '/../view/' . $template . '.php';
        require_once __DIR__ . '/../view/admin/layout_footer.php';
    }

    private function redirect(string $url): void
    {
        header('Location: ' . BASE_URL . '/' . $url);
        exit;
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function requireAdmin(): void
    {
        if (empty($_SESSION[SESSION_ADMIN])) {
            $this->redirect('?case=admin_login');
        }
    }

    // ── Gửi voucher qua email ────────────────────────────────
    private function sendVoucherEmail(): void
    {
        $vouchers = $this->voucherModel->getAll('expires_at DESC');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrf()) { $this->redirect('?case=admin_send_voucher'); return; }

            $voucherId = (int)($_POST['voucher_id'] ?? 0);
            $target    = $_POST['target'] ?? 'all';

            $voucher = $this->voucherModel->getById($voucherId);
            if (!$voucher) { $this->redirect('?case=admin_send_voucher&error=1'); return; }

            $sql    = "SELECT name, email FROM users WHERE is_locked = 0";
            $params = [];
            if (in_array($target, ['Gold', 'Diamond'])) {
                $sql    .= " AND tier = ?";
                $params[] = $target;
            }
            $users = Database::getInstance()->fetchAll($sql, $params);

            require_once __DIR__ . '/../includes/MailService.php';
            $sent = 0;
            foreach ($users as $user) {
                if (MailService::sendVoucherGift($user, $voucher)) $sent++;
            }
            $this->redirect('?case=admin_send_voucher&sent=' . $sent);
            return;
        }

        $this->adminView('admin/send_voucher', compact('vouchers'));
    }

}
