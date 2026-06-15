<?php
// ============================================================
//  MailService — Gửi email tự động với PHPMailer + Gmail SMTP
//  Sử dụng: MailService::sendOrderConfirm($order, $user)
//           MailService::sendWelcome($user)
//           MailService::sendStatusUpdate($order, $user)
//           MailService::sendPasswordReset($user, $token)
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/../config/config.php';

class MailService
{
    // ── Factory: tạo PHPMailer đã cấu hình ─────────────────
    private static function make(): PHPMailer
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);

        return $mail;
    }

    // ── Gửi email (wrapper chung) ───────────────────────────
    private static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        // Nếu chưa cấu hình email thì bỏ qua (không crash app)
        if (!MAIL_ENABLED) {
            error_log("[UPNEX Mail] MAIL_ENABLED=false — Bỏ qua gửi email đến: {$toEmail}");
            return true;
        }

        try {
            $mail = self::make();
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $htmlBody));
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("[UPNEX Mail Error] {$e->getMessage()} — To: {$toEmail}");
            return false;
        }
    }

    // ════════════════════════════════════════════════════════
    //  1. EMAIL CHÀO MỪNG (Đăng ký thành công)
    // ════════════════════════════════════════════════════════
    public static function sendWelcome(array $user): bool
    {
        $name    = htmlspecialchars($user['name']);
        $email   = $user['email'];
        $subject = '🎉 Chào mừng bạn đến với UPNEX!';

        $body = self::wrap("Chào mừng, {$name}!", "
            <p style='color:#555;font-size:15px;line-height:1.7'>
                Cảm ơn bạn đã đăng ký tài khoản tại <strong>UPNEX</strong> — cửa hàng công nghệ uy tín hàng đầu!
            </p>
            <p style='color:#555;font-size:15px;line-height:1.7'>
                Với tài khoản UPNEX, bạn có thể:
            </p>
            <ul style='color:#555;font-size:14px;line-height:2'>
                <li>🛒 Mua sắm hàng ngàn sản phẩm công nghệ chính hãng</li>
                <li>⭐ Tích điểm, nâng hạng <strong>Silver → Gold → Diamond</strong></li>
                <li>🎟️ Nhận voucher giảm giá độc quyền</li>
                <li>📦 Theo dõi đơn hàng real-time</li>
            </ul>
            " . self::button(BASE_URL . '/?case=products', '🛍️ Mua sắm ngay') . "
            <p style='color:#aaa;font-size:12px;margin-top:24px'>
                Email đăng nhập của bạn: <strong>{$email}</strong>
            </p>
        ");

        return self::send($email, $name, $subject, $body);
    }

    // ════════════════════════════════════════════════════════
    //  2. XÁC NHẬN ĐƠN HÀNG
    // ════════════════════════════════════════════════════════
    public static function sendOrderConfirm(array $order, array $user, array $items): bool
    {
        $name      = htmlspecialchars($user['name']);
        $email     = $user['email'];
        $orderCode = htmlspecialchars($order['order_code']);
        $subject   = "✅ Đơn hàng #{$orderCode} đã được đặt thành công";

        // Build danh sách sản phẩm
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "
                <tr>
                    <td style='padding:10px 8px;border-bottom:1px solid #f0f0f0;font-size:13px'>
                        " . htmlspecialchars($item['product_name']) . "
                    </td>
                    <td style='padding:10px 8px;border-bottom:1px solid #f0f0f0;font-size:13px;text-align:center'>
                        x{$item['quantity']}
                    </td>
                    <td style='padding:10px 8px;border-bottom:1px solid #f0f0f0;font-size:13px;text-align:right;color:#dc3545;font-weight:700'>
                        " . number_format($item['subtotal']) . "đ
                    </td>
                </tr>
            ";
        }

        $payMethod = match($order['payment_method']) {
            'cod'           => '💵 Thanh toán khi nhận hàng (COD)',
            'bank_transfer' => '🏦 Chuyển khoản ngân hàng',
            'momo'          => '📱 Ví MoMo',
            'vnpay'         => '💳 VNPay',
            default         => $order['payment_method'],
        };

        $body = self::wrap("Đơn hàng đã được đặt! 🎉", "
            <p style='color:#555;font-size:15px'>Xin chào <strong>{$name}</strong>,</p>
            <p style='color:#555;font-size:14px'>
                Cảm ơn bạn đã đặt hàng tại UPNEX! Đơn hàng của bạn đã được xác nhận và đang được xử lý.
            </p>

            <!-- Order Info -->
            <div style='background:#f8f9ff;border-radius:10px;padding:16px;margin:20px 0;border-left:4px solid #0d6efd'>
                <table width='100%' style='font-size:13px'>
                    <tr>
                        <td style='color:#888;padding:4px 0'>Mã đơn hàng:</td>
                        <td style='font-weight:700;text-align:right;color:#0d6efd'>#{$orderCode}</td>
                    </tr>
                    <tr>
                        <td style='color:#888;padding:4px 0'>Ngày đặt:</td>
                        <td style='font-weight:600;text-align:right'>" . date('d/m/Y H:i', strtotime($order['created_at'])) . "</td>
                    </tr>
                    <tr>
                        <td style='color:#888;padding:4px 0'>Phương thức:</td>
                        <td style='font-weight:600;text-align:right'>{$payMethod}</td>
                    </tr>
                    <tr>
                        <td style='color:#888;padding:4px 0'>Địa chỉ giao:</td>
                        <td style='font-weight:600;text-align:right;max-width:200px'>" . htmlspecialchars($order['shipping_address']) . "</td>
                    </tr>
                </table>
            </div>

            <!-- Items -->
            <p style='font-weight:700;font-size:14px;margin-bottom:8px'>Sản phẩm đã đặt:</p>
            <table width='100%' style='border-collapse:collapse'>
                <thead>
                    <tr style='background:#f0f4ff'>
                        <th style='padding:8px;font-size:12px;text-align:left;color:#555'>Sản phẩm</th>
                        <th style='padding:8px;font-size:12px;text-align:center;color:#555'>SL</th>
                        <th style='padding:8px;font-size:12px;text-align:right;color:#555'>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>{$itemsHtml}</tbody>
            </table>

            <!-- Total -->
            <table width='100%' style='margin-top:12px;font-size:13px'>
                <tr>
                    <td style='color:#888;padding:3px 8px'>Tạm tính:</td>
                    <td style='text-align:right;padding:3px 8px'>" . number_format($order['subtotal']) . "đ</td>
                </tr>
                " . ($order['discount_amount'] > 0 ? "
                <tr>
                    <td style='color:#198754;padding:3px 8px'>Giảm giá:</td>
                    <td style='text-align:right;color:#198754;padding:3px 8px'>-" . number_format($order['discount_amount']) . "đ</td>
                </tr>" : "") . "
                <tr>
                    <td style='color:#888;padding:3px 8px'>Phí vận chuyển:</td>
                    <td style='text-align:right;padding:3px 8px'>" . number_format($order['shipping_fee']) . "đ</td>
                </tr>
                <tr style='border-top:2px solid #dee2e6'>
                    <td style='font-weight:700;font-size:15px;padding:8px 8px 0'>Tổng cộng:</td>
                    <td style='text-align:right;font-weight:700;font-size:15px;color:#dc3545;padding:8px 8px 0'>" . number_format($order['total']) . "đ</td>
                </tr>
            </table>

            " . self::button(BASE_URL . '/?case=order_detail&id=' . $order['id'], '📦 Theo dõi đơn hàng') . "
        ");

        return self::send($email, $name, $subject, $body);
    }

    // ════════════════════════════════════════════════════════
    //  3. CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
    // ════════════════════════════════════════════════════════
    public static function sendStatusUpdate(array $order, array $user): bool
    {
        $name      = htmlspecialchars($user['name']);
        $email     = $user['email'];
        $orderCode = htmlspecialchars($order['order_code']);

        $statusInfo = match($order['status']) {
            'confirmed' => ['✅ Đơn hàng đã được xác nhận', '#198754', '📋 Đơn hàng của bạn đã được xác nhận và đang chuẩn bị.'],
            'shipping'  => ['🚚 Đơn hàng đang trên đường giao', '#0d6efd', '🛵 Đơn hàng đang được giao đến bạn, vui lòng để ý điện thoại.'],
            'delivered' => ['📬 Đơn hàng đã giao thành công', '#198754', '🎉 Đơn hàng đã được giao thành công! Cảm ơn bạn đã mua sắm.'],
            'completed' => ['🏆 Đơn hàng hoàn thành', '#6610f2', '⭐ Cảm ơn bạn! Hãy để lại đánh giá để giúp những khách hàng khác.'],
            'cancelled' => ['❌ Đơn hàng đã bị hủy', '#dc3545', '😔 Đơn hàng của bạn đã bị hủy. Nếu cần hỗ trợ, liên hệ 1800 6868.'],
            default     => ['📋 Cập nhật đơn hàng', '#6c757d', 'Trạng thái đơn hàng của bạn đã được cập nhật.'],
        };

        [$subject, $color, $message] = $statusInfo;
        $subject = $subject . " — #{$orderCode}";

        $body = self::wrap("Cập nhật đơn hàng #{$orderCode}", "
            <p style='color:#555;font-size:15px'>Xin chào <strong>{$name}</strong>,</p>
            <div style='background:{$color}15;border-left:4px solid {$color};border-radius:8px;padding:16px;margin:20px 0'>
                <p style='color:{$color};font-weight:700;font-size:15px;margin:0 0 6px'>{$subject}</p>
                <p style='color:#555;font-size:14px;margin:0'>{$message}</p>
            </div>
            <table width='100%' style='font-size:13px;background:#f8f9fa;border-radius:8px;padding:12px'>
                <tr>
                    <td style='color:#888;padding:4px 0'>Mã đơn hàng:</td>
                    <td style='font-weight:700;text-align:right;color:#0d6efd'>#{$orderCode}</td>
                </tr>
                <tr>
                    <td style='color:#888;padding:4px 0'>Tổng tiền:</td>
                    <td style='font-weight:700;text-align:right;color:#dc3545'>" . number_format($order['total']) . "đ</td>
                </tr>
            </table>
            " . self::button(BASE_URL . '/?case=order_detail&id=' . $order['id'], '🔍 Xem chi tiết đơn') . "
        ");

        return self::send($email, $name, $subject, $body);
    }

    // ════════════════════════════════════════════════════════
    //  4. THANH TOÁN THÀNH CÔNG (MoMo / VNPay)
    // ════════════════════════════════════════════════════════
    public static function sendPaymentSuccess(array $order, array $user, string $transactionId): bool
    {
        $name      = htmlspecialchars($user['name']);
        $email     = $user['email'];
        $orderCode = htmlspecialchars($order['order_code']);
        $subject   = "💳 Thanh toán thành công — Đơn #{$orderCode}";

        $body = self::wrap('Thanh toán thành công! 🎉', "
            <p style='color:#555;font-size:15px'>Xin chào <strong>{$name}</strong>,</p>
            <div style='background:#d1fae5;border-left:4px solid #10b981;border-radius:8px;padding:16px;margin:20px 0;text-align:center'>
                <div style='font-size:40px'>✅</div>
                <p style='color:#065f46;font-weight:700;font-size:16px;margin:8px 0'>Thanh toán thành công</p>
                <p style='color:#047857;font-size:22px;font-weight:800;margin:0'>" . number_format($order['total']) . "đ</p>
            </div>
            <table width='100%' style='font-size:13px;border-radius:8px;overflow:hidden'>
                <tr style='background:#f8f9fa'>
                    <td style='padding:8px 12px;color:#888'>Mã đơn hàng</td>
                    <td style='padding:8px 12px;font-weight:700;text-align:right;color:#0d6efd'>#{$orderCode}</td>
                </tr>
                <tr>
                    <td style='padding:8px 12px;color:#888'>Mã giao dịch</td>
                    <td style='padding:8px 12px;font-weight:600;text-align:right;font-family:monospace'>{$transactionId}</td>
                </tr>
                <tr style='background:#f8f9fa'>
                    <td style='padding:8px 12px;color:#888'>Thời gian</td>
                    <td style='padding:8px 12px;font-weight:600;text-align:right'>" . date('d/m/Y H:i:s') . "</td>
                </tr>
                <tr>
                    <td style='padding:8px 12px;color:#888'>Phương thức</td>
                    <td style='padding:8px 12px;font-weight:600;text-align:right'>" . strtoupper($order['payment_method']) . "</td>
                </tr>
            </table>
            " . self::button(BASE_URL . '/?case=order_detail&id=' . $order['id'], '📦 Theo dõi đơn hàng') . "
        ");

        return self::send($email, $name, $subject, $body);
    }

    // ════════════════════════════════════════════════════════
    //  5. VOUCHER TẶNG (Admin gửi cho VIP)
    // ════════════════════════════════════════════════════════
    public static function sendVoucherGift(array $user, array $voucher): bool
    {
        $name    = htmlspecialchars($user['name']);
        $email   = $user['email'];
        $code    = htmlspecialchars($voucher['code']);
        $subject = "🎁 UPNEX tặng bạn voucher giảm giá đặc biệt!";

        $discountText = $voucher['discount_type'] === 'percent'
            ? $voucher['discount_value'] . '%'
            : number_format($voucher['discount_value']) . 'đ';

        $body = self::wrap('Quà tặng từ UPNEX 🎁', "
            <p style='color:#555;font-size:15px'>Xin chào <strong>{$name}</strong>,</p>
            <p style='color:#555;font-size:14px'>
                Cảm ơn bạn đã là khách hàng thân thiết! UPNEX xin gửi tặng bạn voucher giảm giá đặc biệt:
            </p>
            <div style='background:linear-gradient(135deg,#667eea,#764ba2);border-radius:16px;padding:32px;text-align:center;margin:20px 0'>
                <p style='color:#fff;font-size:13px;margin:0 0 8px;opacity:.85'>Mã voucher của bạn</p>
                <div style='background:rgba(255,255,255,.15);border:2px dashed rgba(255,255,255,.5);border-radius:10px;padding:16px;display:inline-block'>
                    <span style='color:#fff;font-size:28px;font-weight:900;letter-spacing:4px;font-family:monospace'>{$code}</span>
                </div>
                <p style='color:#fff;font-size:20px;font-weight:800;margin:16px 0 4px'>Giảm {$discountText}</p>
                <p style='color:rgba(255,255,255,.8);font-size:12px;margin:0'>
                    Đơn tối thiểu " . number_format($voucher['min_order_value']) . "đ
                    | Hết hạn: " . date('d/m/Y', strtotime($voucher['expires_at'])) . "
                </p>
            </div>
            " . self::button(BASE_URL . '/?case=products', '🛍️ Dùng ngay') . "
            <p style='color:#aaa;font-size:12px;text-align:center;margin-top:16px'>
                * Voucher chỉ dùng được một lần, không áp dụng cùng chương trình khác.
            </p>
        ");

        return self::send($email, $name, $subject, $body);
    }

    // ════════════════════════════════════════════════════════
    //  TEMPLATE HELPERS
    // ════════════════════════════════════════════════════════

    /** Bọc nội dung vào layout email UPNEX */
    private static function wrap(string $title, string $content): string
    {
        return '<!DOCTYPE html>
<html lang="vi">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f5f6fa;font-family:Segoe UI,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6fa;padding:32px 16px">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#0f0f1a,#1a1a3e);border-radius:14px 14px 0 0;padding:28px 32px;text-align:center">
            <table cellpadding="0" cellspacing="0" style="margin:0 auto">
              <tr>
                <td style="background:linear-gradient(135deg,#0d6efd,#6610f2);border-radius:10px;width:40px;height:40px;text-align:center;vertical-align:middle">
                  <span style="color:#fff;font-weight:900;font-size:18px">U</span>
                </td>
                <td style="color:#fff;font-weight:800;font-size:22px;letter-spacing:-0.5px;padding-left:10px">UPNEX</td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Title bar -->
        <tr>
          <td style="background:#0d6efd;padding:16px 32px">
            <h2 style="color:#fff;margin:0;font-size:18px;font-weight:700">' . $title . '</h2>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="background:#fff;padding:32px;border-radius:0 0 0 0">
            ' . $content . '
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#12121f;border-radius:0 0 14px 14px;padding:20px 32px;text-align:center">
            <p style="color:#666;font-size:12px;margin:0 0 8px">
              © ' . date('Y') . ' UPNEX. Tất cả quyền được bảo lưu.
            </p>
            <p style="color:#444;font-size:11px;margin:0">
              📞 1800 6868 &nbsp;|&nbsp; ✉️ support@upnex.vn &nbsp;|&nbsp; TP. Hồ Chí Minh
            </p>
            <p style="color:#333;font-size:11px;margin:8px 0 0">
              <a href="' . BASE_URL . '/?case=home" style="color:#0d6efd;text-decoration:none">Trang chủ</a>
              &nbsp;|&nbsp;
              <a href="' . BASE_URL . '/?case=order_history" style="color:#0d6efd;text-decoration:none">Đơn hàng của tôi</a>
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>';
    }

    /** Nút CTA trong email */
    private static function button(string $url, string $text): string
    {
        return "
        <div style='text-align:center;margin:28px 0'>
            <a href='{$url}'
               style='background:linear-gradient(135deg,#0d6efd,#6610f2);color:#fff;text-decoration:none;
                      padding:14px 36px;border-radius:50px;font-weight:700;font-size:15px;
                      display:inline-block;letter-spacing:0.3px'>
                {$text}
            </a>
        </div>";
    }
}
