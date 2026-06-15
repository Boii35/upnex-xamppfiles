<?php
// ============================================================
//  Model: OrderModel
//  - Quản lý giỏ hàng (lưu DB, không dùng session)
//  - Đặt hàng với PDO Transaction (an toàn)
//  - Áp dụng voucher + kiểm tra ràng buộc
//  - Cập nhật trạng thái + ghi log
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class OrderModel extends BaseModel
{
    protected string $table = 'orders';

    // ════════════════════════════════════════════════════════
    //  GIỎ HÀNG
    // ════════════════════════════════════════════════════════

    /**
     * Lấy toàn bộ giỏ hàng của user (JOIN product để hiển thị)
     */
    public function getCart(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT c.id, c.quantity, p.id AS product_id, p.name, p.stock,
                    COALESCE(p.sale_price, p.price) AS unit_price,
                    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS image
             FROM cart c
             JOIN products p ON c.product_id = p.id
             WHERE c.user_id = ? AND p.is_active = 1
             ORDER BY c.updated_at DESC",
            [$userId]
        );
    }

    /**
     * Thêm vào giỏ hàng (nếu đã có thì cộng số lượng)
     */
    public function addToCart(int $userId, int $productId, int $qty = 1): array
    {
        // Kiểm tra tồn kho
        $product = $this->db->fetchOne(
            "SELECT stock FROM products WHERE id = ? AND is_active = 1",
            [$productId]
        );
        if (!$product) return ['success' => false, 'message' => 'Sản phẩm không tồn tại.'];

        // Kiểm tra số lượng hiện có trong giỏ
        $existing = $this->db->fetchOne(
            "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?",
            [$userId, $productId]
        );

        $newQty = ($existing['quantity'] ?? 0) + $qty;
        if ($newQty > $product['stock']) {
            return ['success' => false, 'message' => "Chỉ còn {$product['stock']} sản phẩm trong kho."];
        }

        if ($existing) {
            $this->db->query(
                "UPDATE cart SET quantity = ? WHERE id = ?",
                [$newQty, $existing['id']]
            );
        } else {
            $this->db->query(
                "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)",
                [$userId, $productId, $qty]
            );
        }

        return ['success' => true, 'cart_count' => $this->countCartItems($userId)];
    }

    /**
     * Cập nhật số lượng trong giỏ
     */
    public function updateCart(int $userId, int $cartId, int $qty): array
    {
        $item = $this->db->fetchOne(
            "SELECT c.*, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ?",
            [$cartId, $userId]
        );
        if (!$item) return ['success' => false, 'message' => 'Không tìm thấy sản phẩm.'];
        if ($qty > $item['stock']) return ['success' => false, 'message' => "Tồn kho chỉ còn {$item['stock']}."];
        if ($qty < 1) return $this->removeFromCart($userId, $cartId);

        $this->db->query("UPDATE cart SET quantity = ? WHERE id = ?", [$qty, $cartId]);
        return ['success' => true];
    }

    /**
     * Xóa item khỏi giỏ
     */
    public function removeFromCart(int $userId, int $cartId): array
    {
        $this->db->query("DELETE FROM cart WHERE id = ? AND user_id = ?", [$cartId, $userId]);
        return ['success' => true, 'cart_count' => $this->countCartItems($userId)];
    }

    public function clearCart(int $userId): void
    {
        $this->db->query("DELETE FROM cart WHERE user_id = ?", [$userId]);
    }

    public function countCartItems(int $userId): int
    {
        $row = $this->db->fetchOne(
            "SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?",
            [$userId]
        );
        return (int)($row['total'] ?? 0);
    }

    // ════════════════════════════════════════════════════════
    //  VOUCHER
    // ════════════════════════════════════════════════════════

    /**
     * Kiểm tra + áp dụng voucher
     * Trả về số tiền giảm
     */
    public function applyVoucher(string $code, int $userId, float $subtotal): array
    {
        $voucher = $this->db->fetchOne(
            "SELECT * FROM vouchers WHERE code = ? AND is_active = 1 AND expires_at > NOW()",
            [strtoupper(trim($code))]
        );

        if (!$voucher) return ['success' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn.'];
        if ($voucher['used_count'] >= $voucher['max_uses']) return ['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng.'];
        if ($subtotal < $voucher['min_order_value']) {
            return ['success' => false, 'message' => 'Đơn hàng tối thiểu ' . number_format($voucher['min_order_value']) . 'đ để dùng mã này.'];
        }

        // Kiểm tra user đã dùng mã này chưa
        $used = $this->db->fetchOne(
            "SELECT id FROM voucher_uses WHERE voucher_id = ? AND user_id = ?",
            [$voucher['id'], $userId]
        );
        if ($used) return ['success' => false, 'message' => 'Bạn đã sử dụng mã giảm giá này rồi.'];

        $discount = ($voucher['discount_type'] === 'percent')
            ? $subtotal * $voucher['discount_value'] / 100
            : (float)$voucher['discount_value'];

        $discount = min($discount, $subtotal); // Không giảm quá tổng đơn

        return [
            'success'    => true,
            'voucher_id' => $voucher['id'],
            'discount'   => round($discount),
            'message'    => 'Áp dụng mã thành công! Giảm ' . number_format(round($discount)) . 'đ',
        ];
    }

    // ════════════════════════════════════════════════════════
    //  ĐẶT HÀNG (PDO Transaction)
    // ════════════════════════════════════════════════════════

    /**
     * Tạo đơn hàng — dùng Transaction để đảm bảo toàn vẹn dữ liệu
     */
    public function placeOrder(int $userId, array $data): array
    {
        $cartItems = $this->getCart($userId);
        if (empty($cartItems)) return ['success' => false, 'message' => 'Giỏ hàng trống.'];

        // Tính tiền
        $subtotal = array_sum(array_map(fn($i) => $i['unit_price'] * $i['quantity'], $cartItems));
        $discount = 0;
        $voucherId = null;

        if (!empty($data['voucher_code'])) {
            $v = $this->applyVoucher($data['voucher_code'], $userId, $subtotal);
            if ($v['success']) {
                $discount  = $v['discount'];
                $voucherId = $v['voucher_id'];
            }
        }

        $shippingFee = 30_000; // Phí ship cố định, sau này có thể tính động
        $total = $subtotal - $discount + $shippingFee;
        $orderCode = 'UPX' . date('ymd') . strtoupper(substr(uniqid(), -5));

        try {
            $this->db->beginTransaction();

            // 1. Tạo đơn hàng
            $this->db->query(
                "INSERT INTO orders (user_id, order_code, subtotal, discount_amount, shipping_fee, total, payment_method, status, shipping_address, note)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)",
                [
                    $userId, $orderCode, $subtotal, $discount,
                    $shippingFee, $total,
                    $data['payment_method'] ?? 'cod',
                    $this->sanitize($data['shipping_address']),
                    $this->sanitize($data['note'] ?? ''),
                ]
            );
            $orderId = (int)$this->db->lastInsertId();

            // 2. Tạo order_items + trừ tồn kho
            foreach ($cartItems as $item) {
                // Kiểm tra lại tồn kho (lần 2 trong transaction)
                $stock = $this->db->fetchOne(
                    "SELECT stock FROM products WHERE id = ? FOR UPDATE",
                    [$item['product_id']]
                )['stock'] ?? 0;

                if ($stock < $item['quantity']) {
                    throw new \RuntimeException("Sản phẩm \"{$item['name']}\" không đủ hàng.");
                }

                $this->db->query(
                    "INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$orderId, $item['product_id'], $item['name'], $item['unit_price'], $item['quantity'], $item['unit_price'] * $item['quantity']]
                );

                // Trừ tồn kho, cộng sold_count
                $this->db->query(
                    "UPDATE products SET stock = stock - ?, sold_count = sold_count + ? WHERE id = ?",
                    [$item['quantity'], $item['quantity'], $item['product_id']]
                );
            }

            // 3. Ghi log trạng thái ban đầu
            $this->db->query(
                "INSERT INTO order_status_log (order_id, status, note) VALUES (?, 'pending', 'Đơn hàng mới được tạo')",
                [$orderId]
            );

            // 4. Đánh dấu voucher đã dùng
            if ($voucherId) {
                $this->db->query(
                    "INSERT INTO voucher_uses (voucher_id, user_id, order_id) VALUES (?, ?, ?)",
                    [$voucherId, $userId, $orderId]
                );
                $this->db->query(
                    "UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?",
                    [$voucherId]
                );
            }

            // 5. Tạo bản ghi payment
            $this->db->query(
                "INSERT INTO payments (order_id, method, amount, status) VALUES (?, ?, ?, ?)",
                [$orderId, $data['payment_method'] ?? 'cod', $total,
                 ($data['payment_method'] ?? 'cod') === 'cod' ? 'pending' : 'pending']
            );

            // 6. Xóa giỏ hàng
            $this->clearCart($userId);

            $this->db->commit();

            // 7. Gửi email xác nhận đặt hàng (sau commit để không block transaction)
            $this->sendOrderConfirmEmail($userId, $orderId, $cartItems);

            return ['success' => true, 'order_id' => $orderId, 'order_code' => $orderCode, 'total' => $total,
                    'need_payment' => in_array($data['payment_method'] ?? 'cod', ['momo'])];

        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ════════════════════════════════════════════════════════
    //  THEO DÕI ĐƠN HÀNG
    // ════════════════════════════════════════════════════════

    public function getOrdersByUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    public function getOrderDetail(int $orderId, ?int $userId = null): array|false
    {
        $sql    = "SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?";
        $params = [$orderId];
        if ($userId) { $sql .= " AND o.user_id = ?"; $params[] = $userId; }

        $order = $this->db->fetchOne($sql, $params);
        if (!$order) return false;

        $order['items'] = $this->db->fetchAll(
            "SELECT oi.*, (SELECT image_path FROM product_images WHERE product_id = oi.product_id AND is_primary = 1 LIMIT 1) AS image
             FROM order_items oi WHERE oi.order_id = ?",
            [$orderId]
        );
        $order['status_log'] = $this->db->fetchAll(
            "SELECT * FROM order_status_log WHERE order_id = ? ORDER BY changed_at",
            [$orderId]
        );

        return $order;
    }

    /**
     * Hủy đơn — chỉ được hủy khi status = pending
     */
    public function cancelOrder(int $orderId, int $userId): array
    {
        $order = $this->db->fetchOne(
            "SELECT * FROM orders WHERE id = ? AND user_id = ?",
            [$orderId, $userId]
        );

        if (!$order) return ['success' => false, 'message' => 'Không tìm thấy đơn hàng.'];
        if ($order['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Chỉ có thể hủy đơn khi chưa xác nhận.'];
        }

        try {
            $this->db->beginTransaction();

            // Hoàn lại tồn kho
            $items = $this->db->fetchAll(
                "SELECT product_id, quantity FROM order_items WHERE order_id = ?",
                [$orderId]
            );
            foreach ($items as $item) {
                $this->db->query(
                    "UPDATE products SET stock = stock + ?, sold_count = sold_count - ? WHERE id = ?",
                    [$item['quantity'], $item['quantity'], $item['product_id']]
                );
            }

            $this->db->query(
                "UPDATE orders SET status = 'cancelled' WHERE id = ?",
                [$orderId]
            );
            $this->db->query(
                "INSERT INTO order_status_log (order_id, status, note) VALUES (?, 'cancelled', 'Khách hàng hủy đơn')",
                [$orderId]
            );

            $this->db->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Lỗi hệ thống khi hủy đơn.'];
        }
    }

    // ── Admin: cập nhật trạng thái ───────────────────────────

    public function updateStatus(int $orderId, string $status, int $employeeId, string $note = ''): array
    {
        $allowed = ['confirmed', 'shipping', 'delivered', 'completed', 'cancelled'];
        if (!in_array($status, $allowed)) return ['success' => false, 'message' => 'Trạng thái không hợp lệ.'];

        $this->db->query(
            "UPDATE orders SET status = ?, employee_id = ? WHERE id = ?",
            [$status, $employeeId, $orderId]
        );
        $this->db->query(
            "INSERT INTO order_status_log (order_id, employee_id, status, note) VALUES (?, ?, ?, ?)",
            [$orderId, $employeeId, $status, $this->sanitize($note)]
        );

        // Nếu hoàn thành → cộng điểm tier cho user
        if ($status === 'completed') {
            $order = $this->db->fetchOne("SELECT user_id, total FROM orders WHERE id = ?", [$orderId]);
            if ($order) {
                require_once __DIR__ . '/UserModel.php';
                (new UserModel())->addSpent($order['user_id'], $order['total']);
            }
        }

        return ['success' => true];
    }

    // ── Admin: thống kê doanh thu ────────────────────────────

    public function getRevenueStats(string $period = 'month'): array
    {
        $groupFormat = match($period) {
            'week'  => '%Y-%u',
            'year'  => '%Y',
            default => '%Y-%m',  // month
        };

        return $this->db->fetchAll(
            "SELECT DATE_FORMAT(created_at, ?) AS period, COUNT(*) AS order_count, SUM(total) AS revenue
             FROM orders WHERE status = 'completed'
             GROUP BY period ORDER BY period DESC LIMIT 12",
            [$groupFormat]
        );
    }

    public function getAdminList(int $page = 1, string $status = ''): array
    {
        $where  = $status ? "WHERE o.status = ?" : "WHERE 1=1";
        $params = $status ? [$status] : [];
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $total = (int)($this->db->fetchOne(
            "SELECT COUNT(*) AS total FROM orders o {$where}",
            $params
        )['total'] ?? 0);

        $params[] = $limit;
        $params[] = $offset;

        $orders = $this->db->fetchAll(
            "SELECT o.*, u.name AS user_name FROM orders o
             JOIN users u ON o.user_id = u.id
             {$where} ORDER BY o.created_at DESC LIMIT ? OFFSET ?",
            $params
        );

        return ['orders' => $orders, 'total' => $total, 'total_pages' => (int)ceil($total / $limit)];
    }

    // ── Gửi email xác nhận đặt hàng (private helper) ────────
    private function sendOrderConfirmEmail(int $userId, int $orderId, array $cartItems): void
    {
        try {
            require_once __DIR__ . '/../includes/MailService.php';
            $user  = $this->db->fetchOne("SELECT name, email FROM users WHERE id = ?", [$userId]);
            $order = $this->db->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
            if ($user && $order) {
                MailService::sendOrderConfirm($order, $user, $cartItems);
                // Gửi welcome email nếu đây là đơn đầu tiên
                $orderCount = $this->db->fetchOne(
                    "SELECT COUNT(*) AS cnt FROM orders WHERE user_id = ?", [$userId]
                )['cnt'] ?? 0;
                if ((int)$orderCount === 1) {
                    MailService::sendWelcome($user);
                }
            }
        } catch (\Exception $e) {
            error_log('[OrderModel Mail Error] ' . $e->getMessage());
        }
    }
}
