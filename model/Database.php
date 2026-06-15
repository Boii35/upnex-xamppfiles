<?php
// ============================================================
//  Model: Database — Singleton PDO
//  - Chỉ tạo 1 kết nối duy nhất (Singleton pattern)
//  - Dùng PDO + Prepared Statements → chống SQL Injection
//  - KHÔNG dùng MySQLi
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    // Constructor private → bên ngoài không thể new Database()
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Ném Exception khi lỗi
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Trả về mảng kết hợp
            PDO::ATTR_EMULATE_PREPARES   => false,                    // Dùng prepared stmt thực sự
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Ghi log lỗi, không hiển thị thông tin nhạy cảm ra ngoài
            error_log('[UPNEX DB Error] ' . $e->getMessage());
            die(json_encode(['error' => 'Không thể kết nối cơ sở dữ liệu.']));
        }
    }

    // Lấy instance duy nhất
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Trả về đối tượng PDO để dùng trong Model
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    // ── Hàm tiện ích ─────────────────────────────────────────

    /**
     * Thực thi query có tham số (Prepared Statement)
     * Dùng cho SELECT, INSERT, UPDATE, DELETE
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Lấy một hàng duy nhất
     */
    public function fetchOne(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Lấy tất cả hàng
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Lấy ID vừa INSERT
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Transaction helpers
     */
    public function beginTransaction(): void   { $this->pdo->beginTransaction(); }
    public function commit(): void             { $this->pdo->commit(); }
    public function rollback(): void           { $this->pdo->rollBack(); }

    // Ngăn clone và unserialize (bảo vệ Singleton)
    private function __clone() {}
    public function __wakeup(): void
    {
        throw new \RuntimeException('Không thể unserialize Singleton Database.');
    }
}
