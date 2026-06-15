<?php
// ============================================================
//  Model: CategoryModel
// ============================================================
require_once __DIR__ . '/BaseModel.php';

class CategoryModel extends BaseModel
{
    protected string $table = 'categories';

    public function getMainCategories(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order"
        );
    }

    public function getWithChildren(): array
    {
        $all    = $this->getAll('sort_order');
        $result = [];
        foreach ($all as $cat) {
            if ($cat['parent_id'] === null) {
                $cat['children'] = array_filter($all, fn($c) => $c['parent_id'] == $cat['id']);
                $result[] = $cat;
            }
        }
        return $result;
    }

    public function create(array $data): array
    {
        if (empty($data['name'])) return ['success' => false, 'message' => 'Tên danh mục không được trống.'];
        $slug = strtolower(preg_replace('/\s+/', '-', trim($data['name'])));
        $this->db->query(
            "INSERT INTO categories (name, slug, parent_id, sort_order) VALUES (?, ?, ?, ?)",
            [
                $this->sanitize($data['name']),
                $slug,
                !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
                (int)($data['sort_order'] ?? 0),
            ]
        );
        return ['success' => true];
    }
}


// ============================================================
//  Model: VoucherModel
// ============================================================
class VoucherModel extends BaseModel
{
    protected string $table = 'vouchers';

    public function create(array $data): array
    {
        if (empty($data['code']))          return ['success' => false, 'message' => 'Mã voucher không được trống.'];
        if (empty($data['discount_value'])) return ['success' => false, 'message' => 'Giá trị giảm không hợp lệ.'];
        if (empty($data['expires_at']))     return ['success' => false, 'message' => 'Thời hạn không được trống.'];

        try {
            $this->db->query(
                "INSERT INTO vouchers (code, discount_type, discount_value, min_order_value, max_uses, expires_at)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    strtoupper(trim($data['code'])),
                    $data['discount_type'] ?? 'percent',
                    (float)$data['discount_value'],
                    (int)($data['min_order_value'] ?? 0),
                    (int)($data['max_uses'] ?? 1),
                    $data['expires_at'],
                ]
            );
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Mã voucher đã tồn tại.'];
        }
    }
}


// ============================================================
//  Model: EmployeeModel
// ============================================================
class EmployeeModel extends BaseModel
{
    protected string $table = 'employees';

    public function login(string $email, string $password): array|false
    {
        $emp = $this->db->fetchOne(
            "SELECT * FROM employees WHERE email = ? AND is_locked = 0",
            [$email]
        );
        if (!$emp || !password_verify($password, $emp['password'])) return false;
        return $emp;
    }

    public function create(array $data): array
    {
        $errors = [];
        if (empty($data['name']))     $errors[] = 'Tên không được trống.';
        if (empty($data['email']))    $errors[] = 'Email không được trống.';
        if (empty($data['password'])) $errors[] = 'Mật khẩu không được trống.';
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        try {
            $this->db->query(
                "INSERT INTO employees (name, email, password, phone, position) VALUES (?, ?, ?, ?, ?)",
                [
                    $this->sanitize($data['name']),
                    $data['email'],
                    password_hash($data['password'], PASSWORD_BCRYPT),
                    $this->sanitize($data['phone'] ?? ''),
                    $this->sanitize($data['position'] ?? ''),
                ]
            );
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'errors' => ['Email đã tồn tại.']];
        }
    }

    public function toggleLock(int $id): void
    {
        $this->db->query("UPDATE employees SET is_locked = NOT is_locked WHERE id = ?", [$id]);
    }
}
