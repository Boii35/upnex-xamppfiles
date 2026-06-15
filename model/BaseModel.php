<?php
// ============================================================
//  Model: BaseModel — Lớp cha cho tất cả Model
//  Mọi Model đều extends BaseModel để dùng chung $db
// ============================================================

require_once __DIR__ . '/Database.php';

abstract class BaseModel
{
    protected Database $db;
    protected PDO $pdo;
    protected string $table = '';  // Mỗi Model tự khai báo

    public function __construct()
    {
        $this->db  = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    // ── CRUD chung ───────────────────────────────────────────

    /**
     * Lấy tất cả bản ghi của bảng
     */
    public function getAll(string $orderBy = 'id DESC'): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY {$orderBy}"
        );
    }

    /**
     * Lấy một bản ghi theo ID
     */
    public function getById(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * Xóa bản ghi theo ID
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->query(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        );
        return $stmt->rowCount() > 0;
    }

    /**
     * Đếm tổng số bản ghi (dùng cho phân trang)
     */
    public function count(string $condition = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if ($condition) $sql .= " WHERE {$condition}";
        $result = $this->db->fetchOne($sql, $params);
        return (int)($result['COUNT(*)'] ?? 0);
    }

    // ── Validation ───────────────────────────────────────────

    /**
     * Làm sạch chuỗi đầu vào — chống XSS
     */
    protected function sanitize(string $input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email
     */
    protected function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate số điện thoại VN (10 số, bắt đầu 0)
     */
    protected function isValidPhone(string $phone): bool
    {
        return (bool)preg_match('/^0[0-9]{9}$/', $phone);
    }
}
