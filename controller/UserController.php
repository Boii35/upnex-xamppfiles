<?php
// ============================================================
//  Controller: UserController
//  Xử lý tất cả request phía user
//  - Cookie user, session user
//  - AJAX endpoints (trả về JSON)
//  - Bảo vệ CSRF
// ============================================================

require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../model/ProductModel.php';
require_once __DIR__ . '/../model/OrderModel.php';
require_once __DIR__ . '/../model/CategoryModel.php';

class UserController
{
    private UserModel   $userModel;
    private ProductModel $productModel;
    private OrderModel  $orderModel;
    private CategoryModel $categoryModel;

    public function __construct()
    {
        $this->userModel     = new UserModel();
        $this->productModel  = new ProductModel();
        $this->orderModel    = new OrderModel();
        $this->categoryModel = new CategoryModel();
    }

    // ── Router chính ─────────────────────────────────────────
    public function dispatch(string $case): void
    {
        match($case) {
            'home'             => $this->home(),
            'products'         => $this->products(),
            'product_detail'   => $this->productDetail(),
            'register'         => $this->register(),
            'login'            => $this->login(),
            'logout'           => $this->logout(),
            'profile'          => $this->profile(),
            'cart'             => $this->cart(),
            'add_to_cart'      => $this->addToCart(),      // AJAX
            'update_cart'      => $this->updateCart(),     // AJAX
            'remove_cart'      => $this->removeFromCart(), // AJAX
            'checkout'         => $this->checkout(),
            'place_order'      => $this->placeOrder(),
            'apply_voucher'    => $this->applyVoucher(),   // AJAX
            'order_history'    => $this->orderHistory(),
            'order_detail'     => $this->orderDetail(),
            'cancel_order'     => $this->cancelOrder(),    // AJAX
            'add_review'       => $this->addReview(),
            'search_ajax'      => $this->searchAjax(),     // AJAX
            'change_password'  => $this->changePassword(), // AJAX
            default            => $this->home(),
        };
    }

    // ── Trang chủ ────────────────────────────────────────────
    private function home(): void
    {
        $featured  = $this->productModel->search(['sort' => 'best_sell'], 1)['products'];
        $newArrive = $this->productModel->search(['sort' => 'newest'], 1)['products'];
        $categories = $this->categoryModel->getMainCategories();
        $this->view('user/home', compact('featured', 'newArrive', 'categories'));
    }

    // ── Danh sách sản phẩm ───────────────────────────────────
    private function products(): void
    {
        $filters = [
            'keyword'     => $_GET['q'] ?? '',
            'category_id' => (int)($_GET['cat'] ?? 0),
            'brand'       => $_GET['brand'] ?? '',
            'price_min'   => (int)($_GET['pmin'] ?? 0),
            'price_max'   => (int)($_GET['pmax'] ?? 0),
            'sort'        => $_GET['sort'] ?? 'newest',
        ];
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $result = $this->productModel->search($filters, $page);
        $categories = $this->categoryModel->getAll();
        $this->view('user/products', array_merge($result, compact('filters', 'categories')));
    }

    // ── Chi tiết sản phẩm ────────────────────────────────────
    private function productDetail(): void
    {
        $id      = (int)($_GET['id'] ?? 0);
        $product = $this->productModel->getDetail($id);
        if (!$product) { $this->redirect('?case=products'); return; }

        $related = $this->productModel->search(['category_id' => $product['category_id']], 1)['products'];
        $this->view('user/product_detail', compact('product', 'related'));
    }

    // ── Đăng ký ──────────────────────────────────────────────
    private function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrf()) { $this->jsonResponse(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']); return; }

            $result = $this->userModel->register($_POST);
            if ($result['success']) {
                $this->redirect('?case=login&registered=1');
            } else {
                $this->view('user/register', ['errors' => $result['errors'], 'old' => $_POST]);
            }
            return;
        }
        $this->view('user/register', ['errors' => [], 'old' => []]);
    }

    // ── Đăng nhập ────────────────────────────────────────────
    private function login(): void
    {
        if (!empty($_SESSION[SESSION_USER])) { $this->redirect('?case=home'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCsrf()) { $this->view('user/login', ['error' => 'Yêu cầu không hợp lệ.']); return; }

            $user = $this->userModel->login($_POST['email'] ?? '', $_POST['password'] ?? '');

            if (!$user) {
                $this->view('user/login', ['error' => 'Email hoặc mật khẩu không đúng.']);
                return;
            }
            if (isset($user['locked'])) {
                $this->view('user/login', ['error' => 'Tài khoản của bạn đã bị khóa.']);
                return;
            }

            // Lưu session user
            $_SESSION[SESSION_USER] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'tier'  => $user['tier'],
            ];

            // Cookie "remember me"
            if (!empty($_POST['remember'])) {
                $this->userModel->setRememberCookie($user['id']);
            }

            $this->redirect('?case=home');
            return;
        }
        $this->view('user/login', ['error' => '']);
    }

    // ── Đăng xuất ────────────────────────────────────────────
    private function logout(): void
    {
        if (!empty($_SESSION[SESSION_USER])) {
            $this->userModel->clearRememberCookie($_SESSION[SESSION_USER]['id']);
        }
        session_unset();
        session_destroy();
        $this->redirect('?case=home');
    }

    // ── Trang cá nhân ────────────────────────────────────────
    private function profile(): void
    {
        $this->requireLogin();
        $userId = $_SESSION[SESSION_USER]['id'];
        $user   = $this->userModel->getById($userId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->userModel->updateProfile($userId, $_POST);
            if ($result['success']) {
                $_SESSION[SESSION_USER]['name'] = $_POST['name'];
                $this->redirect('?case=profile&updated=1');
            } else {
                $this->view('user/profile', ['user' => $user, 'errors' => $result['errors']]);
            }
            return;
        }
        $this->view('user/profile', ['user' => $user, 'errors' => []]);
    }

    // ── Giỏ hàng ─────────────────────────────────────────────
    private function cart(): void
    {
        $this->requireLogin();
        $userId = $_SESSION[SESSION_USER]['id'];
        $items  = $this->orderModel->getCart($userId);
        $subtotal = array_sum(array_map(fn($i) => $i['unit_price'] * $i['quantity'], $items));
        $this->view('user/cart', compact('items', 'subtotal'));
    }

    // ── AJAX: Thêm vào giỏ ───────────────────────────────────
    private function addToCart(): void
    {
        $this->requireLoginAjax();
        $userId    = $_SESSION[SESSION_USER]['id'];
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty       = max(1, (int)($_POST['quantity'] ?? 1));
        $result    = $this->orderModel->addToCart($userId, $productId, $qty);
        $this->jsonResponse($result);
    }

    // ── AJAX: Cập nhật giỏ ───────────────────────────────────
    private function updateCart(): void
    {
        $this->requireLoginAjax();
        $userId = $_SESSION[SESSION_USER]['id'];
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $qty    = (int)($_POST['quantity'] ?? 1);
        $result = $this->orderModel->updateCart($userId, $cartId, $qty);
        $this->jsonResponse($result);
    }

    // ── AJAX: Xóa khỏi giỏ ───────────────────────────────────
    private function removeFromCart(): void
    {
        $this->requireLoginAjax();
        $userId = $_SESSION[SESSION_USER]['id'];
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $result = $this->orderModel->removeFromCart($userId, $cartId);
        $this->jsonResponse($result);
    }

    // ── Trang thanh toán ─────────────────────────────────────
    private function checkout(): void
    {
        $this->requireLogin();
        $userId = $_SESSION[SESSION_USER]['id'];
        $items  = $this->orderModel->getCart($userId);
        if (empty($items)) { $this->redirect('?case=cart'); return; }

        $subtotal = array_sum(array_map(fn($i) => $i['unit_price'] * $i['quantity'], $items));
        $user     = $this->userModel->getById($userId);
        $this->view('user/checkout', compact('items', 'subtotal', 'user'));
    }

    // ── Đặt hàng (POST) ──────────────────────────────────────
    private function placeOrder(): void
    {
        $this->requireLogin();
        if (!verifyCsrf()) { $this->redirect('?case=checkout'); return; }

        $userId = $_SESSION[SESSION_USER]['id'];
        $result = $this->orderModel->placeOrder($userId, $_POST);

        if ($result['success']) {
            // Nếu thanh toán MoMo → redirect sang cổng thanh toán
            if (!empty($result['need_payment']) && $result['need_payment']) {
                $this->redirect('?case=momo_pay&order_id=' . $result['order_id']);
            } else {
                // COD hoặc chuyển khoản → thẳng đến trang chi tiết
                $this->redirect('?case=order_detail&id=' . $result['order_id'] . '&success=1');
            }
        } else {
            $this->redirect('?case=checkout&error=' . urlencode($result['message']));
        }
    }

    // ── AJAX: Kiểm tra voucher ────────────────────────────────
    private function applyVoucher(): void
    {
        $this->requireLoginAjax();
        $userId   = $_SESSION[SESSION_USER]['id'];
        $code     = $_POST['code'] ?? '';
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        $result   = $this->orderModel->applyVoucher($code, $userId, $subtotal);
        $this->jsonResponse($result);
    }

    // ── Lịch sử mua hàng ─────────────────────────────────────
    private function orderHistory(): void
    {
        $this->requireLogin();
        $userId = $_SESSION[SESSION_USER]['id'];
        $orders = $this->orderModel->getOrdersByUser($userId);
        $this->view('user/order_history', compact('orders'));
    }

    // ── Chi tiết đơn hàng ────────────────────────────────────
    private function orderDetail(): void
    {
        $this->requireLogin();
        $userId  = $_SESSION[SESSION_USER]['id'];
        $orderId = (int)($_GET['id'] ?? 0);
        $order   = $this->orderModel->getOrderDetail($orderId, $userId);
        if (!$order) { $this->redirect('?case=order_history'); return; }
        $success = !empty($_GET['success']);
        $this->view('user/order_detail', compact('order', 'success'));
    }

    // ── AJAX: Hủy đơn ────────────────────────────────────────
    private function cancelOrder(): void
    {
        $this->requireLoginAjax();
        if (!verifyCsrf()) { $this->jsonResponse(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']); return; }
        $userId  = $_SESSION[SESSION_USER]['id'];
        $orderId = (int)($_POST['order_id'] ?? 0);
        $result  = $this->orderModel->cancelOrder($orderId, $userId);
        $this->jsonResponse($result);
    }

    // ── Thêm đánh giá ────────────────────────────────────────
    private function addReview(): void
    {
        $this->requireLoginAjax();
        if (!verifyCsrf()) { $this->jsonResponse(['success' => false, 'message' => 'CSRF error.']); return; }

        $userId    = $_SESSION[SESSION_USER]['id'];
        $productId = (int)($_POST['product_id'] ?? 0);
        $orderId   = (int)($_POST['order_id'] ?? 0);
        $rating    = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $comment   = htmlspecialchars(trim($_POST['comment'] ?? ''), ENT_QUOTES, 'UTF-8');

        try {
            Database::getInstance()->query(
                "INSERT INTO reviews (product_id, user_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)",
                [$productId, $userId, $orderId ?: null, $rating, $comment]
            );
            $this->jsonResponse(['success' => true]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này rồi.']);
        }
    }

    // ── AJAX: Đổi mật khẩu ───────────────────────────────────
    private function changePassword(): void
    {
        $this->requireLoginAjax();
        if (!verifyCsrf()) {
            $this->jsonResponse(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
            return;
        }

        $userId  = $_SESSION[SESSION_USER]['id'];
        $oldPw   = $_POST['old_password'] ?? '';
        $newPw   = $_POST['new_password'] ?? '';

        if (strlen($newPw) < 8) {
            $this->jsonResponse(['success' => false, 'message' => 'Mật khẩu mới phải ít nhất 8 ký tự.']);
            return;
        }

        $user = $this->userModel->getById($userId);
        if (!$user || !password_verify($oldPw, $user['password'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng.']);
            return;
        }

        $newHash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::getInstance()->query(
            "UPDATE users SET password = ? WHERE id = ?",
            [$newHash, $userId]
        );

        // Xóa cookie remember_me để bắt đăng nhập lại trên thiết bị khác
        $this->userModel->clearRememberCookie($userId);
        $this->jsonResponse(['success' => true]);
    }
    // ── AJAX: Tìm kiếm autocomplete ───────────────────────
    private function searchAjax(): void
    {
        $keyword = htmlspecialchars(trim($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8');

        if (strlen($keyword) < 2) {
            $this->jsonResponse(['products' => []]);
            return;
        }

        $result = $this->productModel->search(['keyword' => $keyword], 1);

        $products = $result['products'] ?? [];
        $products = array_slice($products, 0, 6);

        $data = array_map(function ($p) {
            return [
                'id'    => $p['id'],
                'name'  => $p['name'],
                'price' => number_format($p['final_price']),
                'image' => $p['main_image'] ?? '',
            ];
        }, $products);

        $this->jsonResponse(['products' => $data]);
    }



    // ── Helpers ──────────────────────────────────────────────

    private function view(string $template, array $data = []): void
    {
        extract($data);
        $categories  = $categories ?? $this->categoryModel->getMainCategories();
        $currentUser = $_SESSION[SESSION_USER] ?? null;
        $cartCount   = $currentUser ? $this->orderModel->countCartItems($currentUser['id']) : 0;
        $csrfToken   = $_SESSION['csrf_token'];
        require_once __DIR__ . '/../view/shared/header.php';
        require_once __DIR__ . '/../view/' . $template . '.php';
        require_once __DIR__ . '/../view/shared/footer.php';
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

    private function requireLogin(): void
    {
        if (empty($_SESSION[SESSION_USER])) {
            $this->redirect('?case=login&next=' . urlencode($_SERVER['REQUEST_URI']));
        }
    }

    private function requireLoginAjax(): void
    {
        if (empty($_SESSION[SESSION_USER])) {
            $this->jsonResponse(['success' => false, 'message' => 'Vui lòng đăng nhập.', 'redirect' => BASE_URL . '/?case=login']);
            exit;
        }
    }
}
