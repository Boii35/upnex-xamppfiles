<?php
// ============================================================
//  Model: ProductModel
//  - CRUD sản phẩm (admin)
//  - Tìm kiếm, lọc, phân trang (user)
//  - Trả về JSON cho AJAX
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class ProductModel extends BaseModel
{
    protected string $table = 'products';

    // ── Lấy danh sách (có lọc + phân trang) ─────────────────

    /**
     * Tìm kiếm + lọc + phân trang
     * Dùng cho trang danh sách sản phẩm & AJAX search
     */
    public function search(array $filters = [], int $page = 1): array
    {
        $conditions = ['p.is_active = 1'];
        $params     = [];

        // Lọc theo từ khóa
        if (!empty($filters['keyword'])) {
            $conditions[] = '(p.name LIKE ? OR p.brand LIKE ?)';
            $kw = '%' . $filters['keyword'] . '%';
            $params[] = $kw;
            $params[] = $kw;
        }

        // Lọc theo danh mục (bao gồm danh mục con)
        if (!empty($filters['category_id'])) {
            $conditions[] = '(p.category_id = ? OR c.parent_id = ?)';
            $params[] = (int)$filters['category_id'];
            $params[] = (int)$filters['category_id'];
        }

        // Lọc theo thương hiệu
        if (!empty($filters['brand'])) {
            $conditions[] = 'p.brand = ?';
            $params[] = $filters['brand'];
        }

        // Lọc giá
        if (!empty($filters['price_min'])) {
            $conditions[] = 'COALESCE(p.sale_price, p.price) >= ?';
            $params[] = (int)$filters['price_min'];
        }
        if (!empty($filters['price_max'])) {
            $conditions[] = 'COALESCE(p.sale_price, p.price) <= ?';
            $params[] = (int)$filters['price_max'];
        }

        $where  = implode(' AND ', $conditions);
        $limit  = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        // Sắp xếp
        $orderMap = [
            'newest'     => 'p.created_at DESC',
            'price_asc'  => 'COALESCE(p.sale_price, p.price) ASC',
            'price_desc' => 'COALESCE(p.sale_price, p.price) DESC',
            'best_sell'  => 'p.sold_count DESC',
        ];
        $order = $orderMap[$filters['sort'] ?? 'newest'] ?? 'p.created_at DESC';

        // Đếm tổng để phân trang
        $countSql = "SELECT COUNT(*) as total FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE {$where}";
        $total = (int)($this->db->fetchOne($countSql, $params)['total'] ?? 0);

        // Query chính
        $sql = "SELECT p.*, c.name AS category_name,
                       COALESCE(p.sale_price, p.price) AS final_price,
                       COALESCE(
                         (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1),
                         (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, sort_order LIMIT 1),
                         ''
                       ) AS main_image,

                       (SELECT ROUND(AVG(rating),1) FROM reviews WHERE product_id = p.id AND is_visible = 1) AS avg_rating
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$where}
                ORDER BY {$order}
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $products = $this->db->fetchAll($sql, $params);

        return [
            'products'    => $products,
            'total'       => $total,
            'total_pages' => (int)ceil($total / $limit),
            'current'     => $page,
        ];
    }

    // ── Chi tiết sản phẩm ────────────────────────────────────

    public function getDetail(int $id): array|false
    {
        $product = $this->db->fetchOne(
            "SELECT p.*, c.name AS category_name,
                    COALESCE(p.sale_price, p.price) AS final_price
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.id = ? AND p.is_active = 1",
            [$id]
        );
        if (!$product) return false;

        $product['images'] = $this->db->fetchAll(
            "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order",
            [$id]
        );
        $product['specs'] = $this->db->fetchAll(
            "SELECT spec_key, spec_value FROM product_specs WHERE product_id = ? ORDER BY sort_order",
            [$id]
        );
        $product['reviews'] = $this->db->fetchAll(
            "SELECT r.*, u.name AS user_name FROM reviews r
             JOIN users u ON r.user_id = u.id
             WHERE r.product_id = ? AND r.is_visible = 1
             ORDER BY r.created_at DESC LIMIT 10",
            [$id]
        );

        return $product;
    }

    // ── Admin CRUD ───────────────────────────────────────────

    public function create(array $data): array
    {
        $errors = $this->validateProduct($data);
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        $slug = $this->generateSlug($data['name']);

        $this->db->query(
            "INSERT INTO products (category_id, name, slug, description, price, sale_price, stock, brand, is_featured)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                (int)$data['category_id'],
                $this->sanitize($data['name']),
                $slug,
                $data['description'] ?? '',
                (int)$data['price'],
                !empty($data['sale_price']) ? (int)$data['sale_price'] : null,
                (int)($data['stock'] ?? 0),
                $this->sanitize($data['brand'] ?? ''),
                (int)($data['is_featured'] ?? 0),
            ]
        );

        return ['success' => true, 'id' => (int)$this->db->lastInsertId()];
    }

    public function update(int $id, array $data): array
    {
        $errors = $this->validateProduct($data);
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        $this->db->query(
            "UPDATE products SET category_id=?, name=?, description=?, price=?,
             sale_price=?, stock=?, brand=?, is_featured=?, is_active=?
             WHERE id=?",
            [
                (int)$data['category_id'],
                $this->sanitize($data['name']),
                $data['description'] ?? '',
                (int)$data['price'],
                !empty($data['sale_price']) ? (int)$data['sale_price'] : null,
                (int)($data['stock'] ?? 0),
                $this->sanitize($data['brand'] ?? ''),
                (int)($data['is_featured'] ?? 0),
                (int)($data['is_active'] ?? 1),
                $id,
            ]
        );

        return ['success' => true];
    }

    public function getAdminList(int $page = 1, string $keyword = ''): array
    {
        $conditions = ['1=1'];
        $params     = [];

        if ($keyword) {
            $conditions[] = '(p.name LIKE ? OR p.brand LIKE ?)';
            $kw = '%' . $keyword . '%';
            $params[] = $kw;
            $params[] = $kw;
        }

        $where  = implode(' AND ', $conditions);
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $total = (int)($this->db->fetchOne(
            "SELECT COUNT(*) as total FROM products p WHERE {$where}",
            $params
        )['total'] ?? 0);

        $params[] = $limit;
        $params[] = $offset;

        $products = $this->db->fetchAll(
            "SELECT p.*, c.name AS category_name,
                    (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS main_image
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE {$where}
             ORDER BY p.created_at DESC LIMIT ? OFFSET ?",
            $params
        );

        return [
            'products'    => $products,
            'total'       => $total,
            'total_pages' => (int)ceil($total / $limit),
            'current'     => $page,
        ];
    }

    // ── Thêm ảnh sản phẩm ───────────────────────────────────

    public function addImage(int $productId, string $path, bool $isPrimary = false): void
    {
        if ($isPrimary) {
            $this->db->query(
                "UPDATE product_images SET is_primary = 0 WHERE product_id = ?",
                [$productId]
            );
        }
        $this->db->query(
            "INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)",
            [$productId, $path, (int)$isPrimary]
        );
    }

    // ── Sản phẩm bán chạy / ế ──────────────────────────────

    public function getBestSellers(int $limit = 5): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, SUM(oi.quantity) AS total_sold
             FROM products p
             JOIN order_items oi ON p.id = oi.product_id
             JOIN orders o ON oi.order_id = o.id
             WHERE o.status = 'completed'
             GROUP BY p.id ORDER BY total_sold DESC LIMIT ?",
            [$limit]
        );
    }

    public function getWorstSellers(int $limit = 5): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, COALESCE(SUM(oi.quantity), 0) AS total_sold
             FROM products p
             LEFT JOIN order_items oi ON p.id = oi.product_id
             LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'completed'
             WHERE p.is_active = 1
             GROUP BY p.id ORDER BY total_sold ASC LIMIT ?",
            [$limit]
        );
    }

    // ── Helper ───────────────────────────────────────────────

    private function generateSlug(string $name): string
    {
        $slug = mb_strtolower($name, 'UTF-8');
        $slug = str_replace(
            ['à','á','ả','ã','ạ','ă','ắ','ằ','ẳ','ẵ','ặ','â','ấ','ầ','ẩ','ẫ','ậ',
             'è','é','ẻ','ẽ','ẹ','ê','ế','ề','ể','ễ','ệ',
             'ì','í','ỉ','ĩ','ị','ò','ó','ỏ','õ','ọ','ô','ố','ồ','ổ','ỗ','ộ','ơ','ớ','ờ','ở','ỡ','ợ',
             'ù','ú','ủ','ũ','ụ','ư','ứ','ừ','ử','ữ','ự',
             'ỳ','ý','ỷ','ỹ','ỵ','đ',' '],
            ['a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
             'e','e','e','e','e','e','e','e','e','e','e',
             'i','i','i','i','i','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
             'u','u','u','u','u','u','u','u','u','u','u',
             'y','y','y','y','y','d','-'],
            $slug
        );
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Đảm bảo slug unique
        $original = $slug;
        $i = 1;
        while ($this->db->fetchOne("SELECT id FROM products WHERE slug = ?", [$slug])) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }

    private function validateProduct(array $data): array
    {
        $errors = [];
        if (empty($data['name'])) $errors['name'] = 'Tên sản phẩm không được trống.';
        if (empty($data['category_id'])) $errors['category_id'] = 'Chọn danh mục.';
        if (empty($data['price']) || $data['price'] <= 0) $errors['price'] = 'Giá phải lớn hơn 0.';
        if (!empty($data['sale_price']) && $data['sale_price'] >= $data['price']) {
            $errors['sale_price'] = 'Giá khuyến mãi phải nhỏ hơn giá gốc.';
        }
        if (!isset($data['stock']) || $data['stock'] < 0) $errors['stock'] = 'Số lượng tồn kho không hợp lệ.';
        return $errors;
    }
}
