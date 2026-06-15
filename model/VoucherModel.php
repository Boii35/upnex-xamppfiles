<?php
// ============================================================
//  Model: VoucherModel
//  - Tương tác bảng vouchers
// ============================================================

require_once __DIR__ . '/Database.php';

class VoucherModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Lấy danh sách voucher
     */
    public function getAll(string $orderBy = 'expires_at DESC'): array
    {
        // whitelist đơn giản cho orderBy
        $allowed = ['expires_at DESC', 'expires_at ASC', 'created_at DESC', 'created_at ASC', 'id DESC', 'id ASC'];
        if (!in_array($orderBy, $allowed, true)) {
            $orderBy = 'expires_at DESC';
        }

        return $this->db->fetchAll(
            "SELECT * FROM vouchers ORDER BY {$orderBy}"
        );
    }

    /**
     * Lấy voucher theo id
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM vouchers WHERE id = ?", [$id]) ?: null;
    }

    /**
     * Tạo voucher mới
     * @return ['success'=>bool,'errors'=>array,'id'=>int]
     */
    public function create(array $data): array
    {
        $errors = [];

        $code = trim($data['code'] ?? '');
        $discountType = trim($data['discount_type'] ?? 'percent');
        $discountValue = $data['discount_value'] ?? null;
        $minOrderValue = $data['min_order_value'] ?? 0;
        $maxUses = $data['max_uses'] ?? 1;
        $expiresAt = trim($data['expires_at'] ?? '');

        if ($code === '') $errors[] = 'Mã voucher không được rỗng.';
        if (!in_array($discountType, ['percent', 'fixed'], true)) $errors[] = 'Loại giảm giá không hợp lệ.';

        if ($discountValue === null || $discountValue === '' || !is_numeric($discountValue)) {
            $errors[] = 'Giá trị giảm giá không hợp lệ.';
        } else {
            $discountValue = (float)$discountValue;
            if ($discountType === 'percent' && ($discountValue <= 0 || $discountValue > 100)) {
                $errors[] = 'Giảm theo % phải nằm trong khoảng (0, 100].';
            }
            if ($discountType === 'fixed' && $discountValue < 0) {
                $errors[] = 'Giảm theo số tiền phải >= 0.';
            }
        }

        if (!is_numeric($minOrderValue)) {
            $errors[] = 'Giá trị đơn tối thiểu không hợp lệ.';
        } else {
            $minOrderValue = (int)$minOrderValue;
        }

        if (!is_numeric($maxUses) || (int)$maxUses <= 0) {
            $errors[] = 'Số lần sử dụng tối đa phải là số > 0.';
        } else {
            $maxUses = (int)$maxUses;
        }

        if ($expiresAt === '') {
            $errors[] = 'Ngày hết hạn không được rỗng.';
        } else {
            // chấp nhận dạng datetime-local (YYYY-MM-DDTHH:MM) hoặc YYYY-MM-DD
            $normalized = str_replace('T', ' ', $expiresAt);
            $ts = strtotime($normalized);
            if ($ts === false) {
                $errors[] = 'Ngày hết hạn không hợp lệ.';
            } else {
                $expiresAt = date('Y-m-d H:i:s', $ts);
            }
        }

        // check code unique
        if ($code !== '' && $this->getByCode($code)) {
            $errors[] = 'Mã voucher đã tồn tại.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->db->query(
            "INSERT INTO vouchers (code, discount_type, discount_value, min_order_value, max_uses, used_count, expires_at, is_active)
             VALUES (?, ?, ?, ?, ?, 0, ?, 1)",
            [$code, $discountType, $discountValue, $minOrderValue, $maxUses, $expiresAt]
        );

        return ['success' => true, 'id' => (int)$this->db->lastInsertId(), 'errors' => []];
    }

    /**
     * Xóa voucher
     */
    public function delete(int $id): void
    {
        $this->db->query("DELETE FROM vouchers WHERE id = ?", [$id]);
    }

    /**
     * helper: lấy voucher theo code
     */
    private function getByCode(string $code): ?array
    {
        return $this->db->fetchOne("SELECT * FROM vouchers WHERE code = ?", [$code]) ?: null;
    }
}

