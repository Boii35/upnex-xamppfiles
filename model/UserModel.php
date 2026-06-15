<?php
// ============================================================
//  Model: UserModel
//  - Đăng ký / Đăng nhập (password_hash/verify)
//  - Cookie "remember me" phía User
//  - Tự động nâng tier theo total_spent
//  - Validate dữ liệu đầu vào
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class UserModel extends BaseModel
{
    protected string $table = 'users';

    // ── Đăng ký ──────────────────────────────────────────────

    public function register(array $data): array
    {
        $errors = $this->validateRegister($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Kiểm tra email tồn tại
        if ($this->findByEmail($data['email'])) {
            return ['success' => false, 'errors' => ['email' => 'Email đã được sử dụng.']];
        }

        $this->db->query(
            "INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)",
            [
                $this->sanitize($data['name']),
                $data['email'],
                password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                $this->sanitize($data['phone'] ?? ''),
            ]
        );

        return ['success' => true, 'id' => (int)$this->db->lastInsertId()];
    }

    // ── Đăng nhập ────────────────────────────────────────────

    public function login(string $email, string $password): array|false
    {
        $user = $this->findByEmail($email);
        if (!$user) return false;
        if ($user['is_locked']) return ['locked' => true];
        if (!password_verify($password, $user['password'])) return false;

        return $user;
    }

    // ── Cookie remember me ───────────────────────────────────

    /**
     * Tạo cookie "nhớ đăng nhập" — lưu token hash, không lưu password
     */
    public function setRememberCookie(int $userId): void
    {
        $token = bin2hex(random_bytes(32));  // Token ngẫu nhiên 64 ký tự
        $hash  = hash('sha256', $token);

        // Lưu hash vào DB (không lưu token thô)
        $this->db->query(
            "UPDATE users SET remember_token = ?, remember_expires = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?",
            [$hash, $userId]
        );

        // Gửi cookie về browser (HttpOnly + SameSite = bảo mật)
        setcookie(
            COOKIE_NAME,
            $userId . ':' . $token,
            [
                'expires'  => time() + COOKIE_LIFETIME,
                'path'     => '/',
                'secure'   => false,        // Bật true khi dùng HTTPS
                'httponly' => true,          // JS không đọc được cookie
                'samesite' => 'Strict',
            ]
        );
    }

    /**
     * Kiểm tra cookie còn hợp lệ → trả về user nếu đúng
     */
    public function loginByCookie(): array|false
    {
        if (empty($_COOKIE[COOKIE_NAME])) return false;

        [$userId, $token] = explode(':', $_COOKIE[COOKIE_NAME], 2) + [null, null];
        if (!$userId || !$token) return false;

        $hash = hash('sha256', $token);
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ? AND remember_token = ? AND remember_expires > NOW() AND is_locked = 0",
            [(int)$userId, $hash]
        );

        return $user ?: false;
    }

    /**
     * Xóa cookie khi logout
     */
    public function clearRememberCookie(int $userId): void
    {
        $this->db->query(
            "UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE id = ?",
            [$userId]
        );
        setcookie(COOKIE_NAME, '', time() - 3600, '/');
    }

    // ── Tier VIP ─────────────────────────────────────────────

    /**
     * Cập nhật tier dựa trên tổng chi tiêu
     * Gọi sau mỗi đơn hàng hoàn thành
     */
    public function updateTier(int $userId): void
    {
        $user = $this->getById($userId);
        if (!$user) return;

        $spent = (float)$user['total_spent'];
        $tier  = 'Silver';
        if ($spent >= TIER_DIAMOND) $tier = 'Diamond';
        elseif ($spent >= TIER_GOLD) $tier = 'Gold';

        $this->db->query(
            "UPDATE users SET tier = ? WHERE id = ?",
            [$tier, $userId]
        );
    }

    /**
     * Cộng tổng chi tiêu sau khi đơn hoàn thành
     */
    public function addSpent(int $userId, float $amount): void
    {
        $this->db->query(
            "UPDATE users SET total_spent = total_spent + ? WHERE id = ?",
            [$amount, $userId]
        );
        $this->updateTier($userId);
    }

    // ── Admin quản lý user ───────────────────────────────────

    public function toggleLock(int $userId): bool
    {
        $this->db->query(
            "UPDATE users SET is_locked = NOT is_locked WHERE id = ?",
            [$userId]
        );
        return true;
    }

    public function getWithPagination(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        return $this->db->fetchAll(
            "SELECT id, name, email, phone, tier, total_spent, is_locked, created_at
             FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    // ── Helper ───────────────────────────────────────────────

    public function findByEmail(string $email): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }

    public function updateProfile(int $userId, array $data): array
    {
        $errors = [];
        if (empty($data['name'])) $errors['name'] = 'Tên không được trống.';
        if (!empty($data['phone']) && !$this->isValidPhone($data['phone'])) {
            $errors['phone'] = 'Số điện thoại không hợp lệ (10 số, bắt đầu 0).';
        }
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        $this->db->query(
            "UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?",
            [
                $this->sanitize($data['name']),
                $this->sanitize($data['phone'] ?? ''),
                $this->sanitize($data['address'] ?? ''),
                $userId,
            ]
        );
        return ['success' => true];
    }

    // ── Validation ───────────────────────────────────────────

    private function validateRegister(array $data): array
    {
        $errors = [];
        if (empty($data['name'])) {
            $errors['name'] = 'Họ tên không được trống.';
        }
        if (empty($data['email']) || !$this->isValidEmail($data['email'])) {
            $errors['email'] = 'Email không hợp lệ.';
        }
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors['password'] = 'Mật khẩu ít nhất 8 ký tự.';
        }
        if (($data['password'] ?? '') !== ($data['confirm_password'] ?? '')) {
            $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp.';
        }
        if (!empty($data['phone']) && !$this->isValidPhone($data['phone'])) {
            $errors['phone'] = 'Số điện thoại không hợp lệ.';
        }
        return $errors;
    }
}
