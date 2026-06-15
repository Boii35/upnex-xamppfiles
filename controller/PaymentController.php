<?php
// ============================================================
//  PaymentController — Xử lý thanh toán
//  Luồng MoMo: placeOrder → momo_pay → [MoMo] → momo_return / momo_ipn
//  Luồng COD / bank: placeOrder → success trực tiếp
// ============================================================

require_once __DIR__ . '/../model/OrderModel.php';
require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../model/Database.php';
require_once __DIR__ . '/../includes/MomoGateway.php';
require_once __DIR__ . '/../includes/MailService.php';
require_once __DIR__ . '/../config/config.php';

class PaymentController
{
    private OrderModel $orderModel;
    private UserModel  $userModel;
    private Database   $db;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
        $this->userModel  = new UserModel();
        $this->db         = Database::getInstance();
    }

    // ── Router ───────────────────────────────────────────────
    public function dispatch(string $case): void
    {
        match($case) {
            'momo_pay'    => $this->initMomoPay(),
            'momo_return' => $this->momoReturn(),
            'momo_ipn'    => $this->momoIpn(),
            default       => $this->redirect('?case=home'),
        };
    }

    // ════════════════════════════════════════════════════════
    //  BƯỚC 1: Khởi tạo thanh toán MoMo
    //  Gọi sau khi placeOrder() tạo đơn, payment_method=momo
    // ════════════════════════════════════════════════════════
    private function initMomoPay(): void
    {
        $this->requireLogin();
        $orderId = (int)($_GET['order_id'] ?? 0);
        $userId  = $_SESSION[SESSION_USER]['id'];

        // Lấy đơn hàng — verify đúng user
        $order = $this->db->fetchOne(
            "SELECT * FROM orders WHERE id = ? AND user_id = ? AND payment_method = 'momo'",
            [$orderId, $userId]
        );

        if (!$order) {
            $this->redirect('?case=order_history');
            return;
        }

        // Nếu đã thanh toán rồi thì không tạo lại
        if ($order['payment_status'] === 'paid') {
            $this->redirect('?case=order_detail&id=' . $orderId);
            return;
        }

        // Gọi MoMo API tạo giao dịch
        $result = MomoGateway::createPayment(
            orderId:   $orderId,
            orderCode: $order['order_code'],
            amount:    (int)$order['total'],
            orderInfo: 'Thanh toan don hang #' . $order['order_code'] . ' tai UPNEX'
        );

        if (!$result['success']) {
            // Lưu lỗi vào payments
            $this->updatePayment($orderId, 'failed', '', json_encode($result));
            $this->redirect('?case=order_detail&id=' . $orderId . '&pay_error=' . urlencode($result['message']));
            return;
        }

        // Lưu requestId để đối chiếu khi callback
        $this->db->query(
            "UPDATE payments SET transaction_id = ?, status = 'pending' WHERE order_id = ?",
            [$result['requestId'], $orderId]
        );

        // Redirect sang trang thanh toán MoMo
        header('Location: ' . $result['payUrl']);
        exit;
    }

    // ════════════════════════════════════════════════════════
    //  BƯỚC 2A: MoMo redirect về (Return URL — người dùng thấy)
    // ════════════════════════════════════════════════════════
    private function momoReturn(): void
    {
        $params = $_GET;
        $result = MomoGateway::handleReturn($params);

        if (!$result['success']) {
            // Chữ ký sai hoặc thanh toán thất bại
            error_log('[MoMo Return] Thất bại: ' . $result['message'] . ' | Code: ' . $result['result_code']);
            $this->handlePaymentFailed($result['order_code'], $result['trans_id'], $result['message']);
            return;
        }

        $this->handlePaymentSuccess(
            orderCode: $result['order_code'],
            transId:   $result['trans_id'],
            amount:    $result['amount'],
            raw:       $params
        );
    }

    // ════════════════════════════════════════════════════════
    //  BƯỚC 2B: MoMo IPN (Server-to-server POST — nên dùng để confirm)
    // ════════════════════════════════════════════════════════
    private function momoIpn(): void
    {
        // Đọc raw POST body
        $rawBody = file_get_contents('php://input');
        $params  = json_decode($rawBody, true) ?? [];

        $result = MomoGateway::handleIPN($params);

        if (!$result['valid']) {
            http_response_code(400);
            echo json_encode(['status' => 1, 'message' => 'Invalid signature']);
            exit;
        }

        if ($result['success']) {
            $this->handlePaymentSuccess(
                orderCode: $result['order_code'],
                transId:   $result['trans_id'],
                amount:    $result['amount'],
                raw:       $params,
                fromIpn:   true
            );
        } else {
            $order = $this->db->fetchOne(
                "SELECT id FROM orders WHERE order_code = ?",
                [$result['order_code']]
            );
            if ($order) {
                $this->updatePayment($order['id'], 'failed', $result['trans_id'], json_encode($params));
            }
        }

        // MoMo yêu cầu trả về HTTP 204 hoặc JSON status=0
        http_response_code(204);
        exit;
    }

    // ════════════════════════════════════════════════════════
    //  XỬ LÝ THANH TOÁN THÀNH CÔNG (dùng chung Return + IPN)
    // ════════════════════════════════════════════════════════
    private function handlePaymentSuccess(
        string $orderCode,
        string $transId,
        int    $amount,
        array  $raw = [],
        bool   $fromIpn = false
    ): void {
        $order = $this->db->fetchOne(
            "SELECT o.*, u.name AS user_name, u.email AS user_email
             FROM orders o JOIN users u ON o.user_id = u.id
             WHERE o.order_code = ?",
            [$orderCode]
        );

        if (!$order) {
            if (!$fromIpn) $this->redirect('?case=order_history');
            return;
        }

        // Idempotent — nếu đã xử lý rồi thì bỏ qua
        if ($order['payment_status'] === 'paid') {
            if (!$fromIpn) $this->redirect('?case=order_detail&id=' . $order['id'] . '&success=1');
            return;
        }

        // Xác minh số tiền khớp (bảo mật — tránh giả mạo)
        if ((int)$order['total'] !== $amount) {
            error_log('[MoMo] Số tiền không khớp: order=' . $order['total'] . ' momo=' . $amount);
            if (!$fromIpn) $this->redirect('?case=order_detail&id=' . $order['id'] . '&pay_error=amount_mismatch');
            return;
        }

        try {
            $this->db->beginTransaction();

            // 1. Cập nhật trạng thái payment
            $this->updatePayment($order['id'], 'success', $transId, json_encode($raw));

            // 2. Cập nhật payment_status và status đơn hàng
            $this->db->query(
                "UPDATE orders SET payment_status = 'paid', status = 'confirmed' WHERE id = ?",
                [$order['id']]
            );

            // 3. Ghi log trạng thái đơn hàng
            $this->db->query(
                "INSERT INTO order_status_log (order_id, status, note) VALUES (?, 'confirmed', ?)",
                [$order['id'], 'Thanh toán MoMo thành công — TransID: ' . $transId]
            );

            $this->db->commit();

        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('[Payment Success Error] ' . $e->getMessage());
            if (!$fromIpn) $this->redirect('?case=order_detail&id=' . $order['id']);
            return;
        }

        // 4. Gửi email xác nhận thanh toán (async-like, không block)
        $this->sendPaymentEmails($order, $transId);

        if (!$fromIpn) {
            $this->redirect('?case=order_detail&id=' . $order['id'] . '&success=1&paid=1');
        }
    }

    // ── Thanh toán thất bại ───────────────────────────────────
    private function handlePaymentFailed(string $orderCode, string $transId, string $reason): void
    {
        $order = $this->db->fetchOne(
            "SELECT id FROM orders WHERE order_code = ?",
            [$orderCode]
        );

        if ($order) {
            $this->updatePayment($order['id'], 'failed', $transId, $reason);
            $this->redirect('?case=order_detail&id=' . $order['id'] . '&pay_error=' . urlencode($reason));
        } else {
            $this->redirect('?case=order_history&pay_error=1');
        }
    }

    // ── Cập nhật bảng payments ────────────────────────────────
    private function updatePayment(int $orderId, string $status, string $transId, string $response = ''): void
    {
        $this->db->query(
            "UPDATE payments
             SET status = ?, transaction_id = ?, gateway_response = ?,
                 paid_at = " . ($status === 'success' ? 'NOW()' : 'NULL') . "
             WHERE order_id = ?",
            [$status, $transId ?: null, $response, $orderId]
        );
    }

    // ── Gửi email sau thanh toán ──────────────────────────────
    private function sendPaymentEmails(array $order, string $transId): void
    {
        try {
            // Email xác nhận thanh toán
            MailService::sendPaymentSuccess(
                order: $order,
                user: [
                    'name'  => $order['user_name'],
                    'email' => $order['user_email'],
                ],
                transactionId: $transId
            );

            // Email trạng thái đơn hàng (xác nhận)
            MailService::sendStatusUpdate(
                order: $order,
                user: [
                    'name'  => $order['user_name'],
                    'email' => $order['user_email'],
                ]
            );
        } catch (\Exception $e) {
            error_log('[Payment Email Error] ' . $e->getMessage());
        }
    }

    // ── Helpers ──────────────────────────────────────────────
    private function requireLogin(): void
    {
        if (empty($_SESSION[SESSION_USER])) {
            $this->redirect('?case=login');
        }
    }

    private function redirect(string $url): void
    {
        header('Location: ' . BASE_URL . '/' . $url);
        exit;
    }
}
