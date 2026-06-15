<?php
// ============================================================
//  Model: EmployeeModel
//  - Tương tác bảng employees
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class EmployeeModel extends BaseModel
{
    protected string $table = 'employees';

    public function getAll(string $orderBy = 'id DESC'): array
    {
        // override BaseModel::getAll (signature must match)
        $orderBy = preg_replace('/[^a-zA-Z0-9_\s,\.\-]/', '', $orderBy);
        return $this->db->fetchAll(
            "SELECT * FROM employees ORDER BY {$orderBy}"
        );
    }


    public function login(string $email, string $password): array|false
    {
        $emp = $this->db->fetchOne(
            "SELECT * FROM employees WHERE email = ?",
            [$email]
        );

        if (!$emp) return false;
        if (!password_verify($password, $emp['password'])) return false;

        return $emp;
    }

    public function create(array $data): array
    {
        $errors = [];

        $name     = trim($data['name'] ?? '');
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $phone    = trim($data['phone'] ?? '');
        $position = trim($data['position'] ?? '');

        if ($name === '') $errors[] = 'Tên không được trống.';
        if ($email === '') $errors[] = 'Email không được trống.';
        if ($password === '') $errors[] = 'Mật khẩu không được trống.';

        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        $exists = $this->db->fetchOne("SELECT id FROM employees WHERE email = ?", [$email]);
        if ($exists) return ['success' => false, 'errors' => ['Email đã tồn tại.']];

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->db->query(
            "INSERT INTO employees (name, email, password, phone, position, is_locked)
             VALUES (?, ?, ?, ?, ?, 0)",
            [
                $this->sanitize($name),
                $email,
                $hash,
                $this->sanitize($phone),
                $this->sanitize($position),
            ]
        );

        return ['success' => true];
    }

    public function toggleLock(int $id): void
    {
        $this->db->query(
            "UPDATE employees SET is_locked = NOT is_locked WHERE id = ?",
            [$id]
        );
    }
}
