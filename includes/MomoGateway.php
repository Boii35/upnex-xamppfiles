<?php
// ============================================================
//  MomoGateway — Tích hợp cổng thanh toán MoMo
//  API: MoMo v2 (ATM/QR/App)
//  Docs: https://developers.momo.vn
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

class MomoGateway
{
    // ── Tạo URL thanh toán MoMo ──────────────────────────────

    /**
     * Khởi tạo giao dịch → trả về URL redirect sang MoMo
     *
     * @param int    $orderId    ID đơn hàng trong DB
     * @param string $orderCode  Mã đơn hiển thị (VD: UPX240601XYZAB)
     * @param int    $amount     Số tiền VNĐ (nguyên, không có phần thập phân)
     * @param string $orderInfo  Mô tả giao dịch
     * @return array ['success', 'payUrl' | 'message']
     */
    public static function createPayment(
        int    $orderId,
        string $orderCode,
        int    $amount,
        string $orderInfo = ''
    ): array {
        if (!MOMO_ENABLED) {
            return ['success' => false, 'message' => 'Cổng MoMo chưa được kích hoạt.'];
        }

        $partnerCode = MOMO_PARTNER_CODE;
        $accessKey   = MOMO_ACCESS_KEY;
        $secretKey   = MOMO_SECRET_KEY;
        $requestId   = $partnerCode . '_' . time() . '_' . $orderId;
        $redirectUrl = MOMO_REDIRECT_URL;
        $ipnUrl      = MOMO_IPN_URL;
        $requestType = 'captureWallet';  // Thanh toán qua App MoMo
        $extraData   = base64_encode(json_encode(['order_id' => $orderId]));
        $lang        = 'vi';
        $orderInfo   = $orderInfo ?: "Thanh toan don hang #{$orderCode} tai UPNEX";

        // Tạo chữ ký HMAC-SHA256 theo thứ tự quy định của MoMo
        $rawHash = "accessKey={$accessKey}"
                 . "&amount={$amount}"
                 . "&extraData={$extraData}"
                 . "&ipnUrl={$ipnUrl}"
                 . "&orderId={$orderCode}"
                 . "&orderInfo={$orderInfo}"
                 . "&partnerCode={$partnerCode}"
                 . "&redirectUrl={$redirectUrl}"
                 . "&requestId={$requestId}"
                 . "&requestType={$requestType}";

        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        $payload = [
            'partnerCode' => $partnerCode,
            'accessKey'   => $accessKey,
            'requestId'   => $requestId,
            'amount'      => $amount,
            'orderId'     => $orderCode,
            'orderInfo'   => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl'      => $ipnUrl,
            'lang'        => $lang,
            'extraData'   => $extraData,
            'requestType' => $requestType,
            'signature'   => $signature,
        ];

        $result = self::callApi(MOMO_ENDPOINT, $payload);

        if (!$result) {
            return ['success' => false, 'message' => 'Không thể kết nối MoMo. Thử lại sau.'];
        }

        // resultCode = 0 → thành công
        if (($result['resultCode'] ?? -1) === 0) {
            return [
                'success'   => true,
                'payUrl'    => $result['payUrl'],
                'requestId' => $requestId,
                'deeplink'  => $result['deeplink']  ?? '',
                'qrCodeUrl' => $result['qrCodeUrl'] ?? '',
            ];
        }

        // Log lỗi từ MoMo
        error_log('[MoMo Error] Code: ' . ($result['resultCode'] ?? '?') . ' — ' . ($result['message'] ?? ''));

        return [
            'success' => false,
            'message' => self::translateError($result['resultCode'] ?? -1),
        ];
    }

    // ── Xử lý callback khi MoMo redirect về (Return URL) ────

    /**
     * Xác thực chữ ký callback từ MoMo (GET params)
     * Gọi trong route ?case=momo_return
     */
    public static function handleReturn(array $params): array
    {
        // Xác thực chữ ký
        if (!self::verifyReturnSignature($params)) {
            return ['success' => false, 'message' => 'Chữ ký không hợp lệ.'];
        }

        $resultCode = (int)($params['resultCode'] ?? -1);
        $orderId    = $params['orderId']       ?? '';   // là order_code của UPNEX
        $amount     = (int)($params['amount']  ?? 0);
        $transId    = $params['transId']       ?? '';   // Mã giao dịch MoMo
        $message    = $params['message']       ?? '';

        return [
            'success'    => $resultCode === 0,
            'order_code' => $orderId,
            'amount'     => $amount,
            'trans_id'   => $transId,
            'result_code'=> $resultCode,
            'message'    => $resultCode === 0 ? 'Thanh toán thành công!' : self::translateError($resultCode),
            'raw'        => $params,
        ];
    }

    // ── Xử lý IPN (Instant Payment Notification — POST từ MoMo) ─

    /**
     * MoMo POST tới IPN URL để thông báo kết quả giao dịch
     * Gọi trong route ?case=momo_ipn
     * PHẢI trả về JSON {"status":0} nhanh (dưới 10 giây)
     */
    public static function handleIPN(array $params): array
    {
        if (!self::verifyIpnSignature($params)) {
            return ['valid' => false, 'message' => 'Invalid signature'];
        }

        return [
            'valid'      => true,
            'success'    => (int)($params['resultCode'] ?? -1) === 0,
            'order_code' => $params['orderId']  ?? '',
            'amount'     => (int)($params['amount'] ?? 0),
            'trans_id'   => $params['transId']  ?? '',
            'result_code'=> (int)($params['resultCode'] ?? -1),
        ];
    }

    // ── Kiểm tra trạng thái giao dịch (query API) ────────────

    /**
     * Truy vấn trạng thái một giao dịch đã tạo
     */
    public static function queryTransaction(string $orderCode, string $requestId): array
    {
        $partnerCode = MOMO_PARTNER_CODE;
        $accessKey   = MOMO_ACCESS_KEY;
        $secretKey   = MOMO_SECRET_KEY;
        $endpoint    = str_replace('/create', '/query', MOMO_ENDPOINT);

        $rawHash = "accessKey={$accessKey}"
                 . "&orderId={$orderCode}"
                 . "&partnerCode={$partnerCode}"
                 . "&requestId={$requestId}";

        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        $payload = [
            'partnerCode' => $partnerCode,
            'accessKey'   => $accessKey,
            'requestId'   => $requestId,
            'orderId'     => $orderCode,
            'signature'   => $signature,
            'lang'        => 'vi',
        ];

        $result = self::callApi($endpoint, $payload);
        return $result ?: ['resultCode' => -1, 'message' => 'Lỗi kết nối'];
    }

    // ── Private helpers ──────────────────────────────────────

    /**
     * Gọi API MoMo qua cURL
     */
    private static function callApi(string $url, array $payload): array|false
    {
        $json = json_encode($payload);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json),
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,  // Bật true khi production
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('[MoMo cURL Error] ' . $error);
            return false;
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[MoMo JSON Error] ' . $response);
            return false;
        }

        return $decoded;
    }

    /**
     * Xác thực chữ ký Return URL (GET params từ redirect MoMo)
     */
    private static function verifyReturnSignature(array $p): bool
    {
        $secretKey = MOMO_SECRET_KEY;
        $accessKey = MOMO_ACCESS_KEY;

        $rawHash = "accessKey={$accessKey}"
                 . "&amount={$p['amount']}"
                 . "&extraData={$p['extraData']}"
                 . "&message={$p['message']}"
                 . "&orderId={$p['orderId']}"
                 . "&orderInfo={$p['orderInfo']}"
                 . "&orderType={$p['orderType']}"
                 . "&partnerCode={$p['partnerCode']}"
                 . "&payType={$p['payType']}"
                 . "&requestId={$p['requestId']}"
                 . "&responseTime={$p['responseTime']}"
                 . "&resultCode={$p['resultCode']}"
                 . "&transId={$p['transId']}";

        $expected = hash_hmac('sha256', $rawHash, $secretKey);
        return hash_equals($expected, $p['signature'] ?? '');
    }

    /**
     * Xác thực chữ ký IPN (POST params từ MoMo server)
     */
    private static function verifyIpnSignature(array $p): bool
    {
        // IPN dùng cùng chuỗi ký như return
        return self::verifyReturnSignature($p);
    }

    /**
     * Dịch mã lỗi MoMo sang tiếng Việt
     */
    private static function translateError(int $code): string
    {
        return match($code) {
            0     => 'Giao dịch thành công.',
            1     => 'Giao dịch đang chờ xử lý.',
            2     => 'Giao dịch thất bại.',
            3     => 'Giao dịch bị hủy.',
            4     => 'Bị từ chối do quá số lần thử.',
            5     => 'Hệ thống đang bảo trì.',
            7     => 'Giao dịch bị từ chối (nghi ngờ gian lận).',
            9     => 'Giao dịch bị hủy bởi người dùng.',
            10    => 'Hết thời gian xử lý giao dịch.',
            11    => 'Tài khoản MoMo chưa đủ số dư.',
            12    => 'Vượt hạn mức giao dịch trong ngày.',
            13    => 'Giao dịch bị từ chối bởi ngân hàng.',
            20    => 'Số tiền không hợp lệ.',
            21    => 'Thông tin đơn hàng không hợp lệ.',
            40    => 'Mã đơn hàng đã tồn tại.',
            41    => 'Quá số lượng giao dịch cho phép.',
            42    => 'API thông tin không hợp lệ.',
            43    => 'Yêu cầu bị từ chối.',
            default => "Lỗi không xác định (mã: {$code}). Vui lòng thử lại.",
        };
    }
}
