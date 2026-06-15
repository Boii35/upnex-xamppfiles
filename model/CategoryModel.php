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



