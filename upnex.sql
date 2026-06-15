-- ============================================================
--  UPNEX — Database Schema HOÀN CHỈNH
--  Engine: MySQL 8.0+  |  Charset: utf8mb4
--  Môi trường: XAMPP localhost
-- ============================================================

CREATE DATABASE IF NOT EXISTS upnex CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE upnex;

SET FOREIGN_KEY_CHECKS = 0;

-- ── 1. USERS ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(100)  NOT NULL,
  email            VARCHAR(150)  NOT NULL UNIQUE,
  password         VARCHAR(255)  NOT NULL,
  phone            VARCHAR(15)   DEFAULT NULL,
  address          TEXT          DEFAULT NULL,
  avatar           VARCHAR(255)  DEFAULT NULL,
  role             ENUM('user','admin') NOT NULL DEFAULT 'user',
  tier             ENUM('Silver','Gold','Diamond') NOT NULL DEFAULT 'Silver',
  total_spent      DECIMAL(15,0) NOT NULL DEFAULT 0,
  is_locked        TINYINT(1)    NOT NULL DEFAULT 0,
  remember_token   VARCHAR(64)   DEFAULT NULL,
  remember_expires DATETIME      DEFAULT NULL,
  created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── 2. EMPLOYEES ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS employees (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  phone      VARCHAR(15)  DEFAULT NULL,
  position   VARCHAR(100) DEFAULT NULL,
  is_locked  TINYINT(1)   NOT NULL DEFAULT 0,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── 3. CATEGORIES ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  slug       VARCHAR(120) NOT NULL UNIQUE,
  parent_id  INT UNSIGNED DEFAULT NULL,
  image      VARCHAR(255) DEFAULT NULL,
  sort_order TINYINT      NOT NULL DEFAULT 0,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── 4. PRODUCTS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED  NOT NULL,
  name        VARCHAR(200)  NOT NULL,
  slug        VARCHAR(220)  NOT NULL UNIQUE,
  description LONGTEXT      DEFAULT NULL,
  price       DECIMAL(15,0) NOT NULL,
  sale_price  DECIMAL(15,0) DEFAULT NULL,
  stock       INT           NOT NULL DEFAULT 0,
  brand       VARCHAR(100)  DEFAULT NULL,
  is_active   TINYINT(1)    NOT NULL DEFAULT 1,
  is_featured TINYINT(1)    NOT NULL DEFAULT 0,
  sold_count  INT           NOT NULL DEFAULT 0,
  created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- ── 5. PRODUCT IMAGES ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_images (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  is_primary TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order TINYINT      NOT NULL DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 6. PRODUCT SPECS ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS product_specs (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  spec_key   VARCHAR(100) NOT NULL,
  spec_value VARCHAR(255) NOT NULL,
  sort_order TINYINT      NOT NULL DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 7. CART ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cart (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity   INT          NOT NULL DEFAULT 1,
  updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cart (user_id, product_id),
  FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 8. VOUCHERS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS vouchers (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code            VARCHAR(50)   NOT NULL UNIQUE,
  discount_type   ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  discount_value  DECIMAL(10,2) NOT NULL,
  min_order_value DECIMAL(15,0) NOT NULL DEFAULT 0,
  max_uses        INT           NOT NULL DEFAULT 1,
  used_count      INT           NOT NULL DEFAULT 0,
  expires_at      DATETIME      NOT NULL,
  is_active       TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── 9. ORDERS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          INT UNSIGNED  NOT NULL,
  employee_id      INT UNSIGNED  DEFAULT NULL,
  order_code       VARCHAR(20)   NOT NULL UNIQUE,
  subtotal         DECIMAL(15,0) NOT NULL,
  discount_amount  DECIMAL(15,0) NOT NULL DEFAULT 0,
  shipping_fee     DECIMAL(15,0) NOT NULL DEFAULT 0,
  total            DECIMAL(15,0) NOT NULL,
  payment_method   ENUM('cod','bank_transfer','momo','vnpay') NOT NULL DEFAULT 'cod',
  payment_status   ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  status           ENUM('pending','confirmed','shipping','delivered','completed','cancelled') NOT NULL DEFAULT 'pending',
  shipping_address TEXT          NOT NULL,
  note             TEXT          DEFAULT NULL,
  created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES users(id),
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── 10. ORDER ITEMS ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id     INT UNSIGNED  NOT NULL,
  product_id   INT UNSIGNED  DEFAULT NULL,
  product_name VARCHAR(200)  NOT NULL,
  unit_price   DECIMAL(15,0) NOT NULL,
  quantity     INT           NOT NULL,
  subtotal     DECIMAL(15,0) NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── 11. ORDER STATUS LOG ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_status_log (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id    INT UNSIGNED NOT NULL,
  employee_id INT UNSIGNED DEFAULT NULL,
  status      ENUM('pending','confirmed','shipping','delivered','completed','cancelled') NOT NULL,
  note        TEXT         DEFAULT NULL,
  changed_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id)    REFERENCES orders(id)    ON DELETE CASCADE,
  FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── 12. VOUCHER USES ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS voucher_uses (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  voucher_id INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  order_id   INT UNSIGNED NOT NULL,
  used_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_voucher_order (voucher_id, order_id),
  FOREIGN KEY (voucher_id) REFERENCES vouchers(id),
  FOREIGN KEY (user_id)    REFERENCES users(id),
  FOREIGN KEY (order_id)   REFERENCES orders(id)
) ENGINE=InnoDB;

-- ── 13. REVIEWS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  order_id   INT UNSIGNED DEFAULT NULL,
  rating     TINYINT      NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment    TEXT         DEFAULT NULL,
  is_visible TINYINT(1)   NOT NULL DEFAULT 1,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review (product_id, user_id, order_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)    REFERENCES users(id),
  FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── 14. PAYMENTS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS payments (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id         INT UNSIGNED NOT NULL UNIQUE,
  method           VARCHAR(50)  NOT NULL,
  transaction_id   VARCHAR(100) DEFAULT NULL,
  amount           DECIMAL(15,0) NOT NULL,
  status           ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
  gateway_response TEXT         DEFAULT NULL,
  paid_at          TIMESTAMP    NULL DEFAULT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  INDEXES — Tối ưu tốc độ truy vấn
-- ============================================================
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_active   ON products(is_active);
CREATE INDEX idx_products_featured ON products(is_featured);
CREATE INDEX idx_orders_user       ON orders(user_id);
CREATE INDEX idx_orders_status     ON orders(status);
CREATE INDEX idx_orders_created    ON orders(created_at);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_reviews_product   ON reviews(product_id);
CREATE INDEX idx_cart_user         ON cart(user_id);

-- ============================================================
--  SEED DATA — Danh mục
-- ============================================================
INSERT INTO categories (name, slug, parent_id, sort_order) VALUES
('Điện thoại',   'dien-thoai',   NULL, 1),
('Laptop',       'laptop',       NULL, 2),
('Tablet',       'tablet',       NULL, 3),
('Phụ kiện',     'phu-kien',     NULL, 4),
('iPhone',       'iphone',       1,    1),
('Samsung',      'samsung',      1,    2),
('Xiaomi',       'xiaomi',       1,    3),
('OPPO',         'oppo',         1,    4),
('MacBook',      'macbook',      2,    1),
('Gaming Laptop','gaming-laptop',2,    2),
('Laptop Văn phòng','laptop-van-phong',2,3),
('iPad',         'ipad',         3,    1),
('Ốp lưng',      'op-lung',      4,    1),
('Sạc & Cáp',    'sac-cap',      4,    2),
('Tai nghe',     'tai-nghe',     4,    3),
('Bàn phím & Chuột','ban-phim-chuot',4,4);

-- ============================================================
--  SEED DATA — Admin mặc định
--  Email: admin@upnex.vn  |  Mật khẩu: Admin@123
-- ============================================================
INSERT INTO employees (name, email, password, position) VALUES
('Admin UPNEX', 'admin@upnex.vn',
 '$2y$12$Kx8e/nMQIFYAq3Vx7kNzpeFvOwHaHAOF9OyBT9oIzZjXGBUSOBQ8O',
 'Quản trị viên');

-- ============================================================
--  SEED DATA — Sản phẩm mẫu (để test giao diện)
-- ============================================================
INSERT INTO products (category_id, name, slug, description, price, sale_price, stock, brand, is_featured) VALUES
(5,  'iPhone 16 Pro Max 256GB Titan Đen',       'iphone-16-pro-max-256gb', 'iPhone 16 Pro Max với chip A18 Pro mạnh mẽ, camera 48MP, màn hình 6.9 inch ProMotion 120Hz.', 34990000, 32990000, 50,  'Apple',   1),
(5,  'iPhone 15 128GB',                          'iphone-15-128gb',         'iPhone 15 với Dynamic Island, camera 48MP, sạc USB-C tiện lợi.',                                  22990000, 20990000, 80,  'Apple',   0),
(6,  'Samsung Galaxy S25 Ultra 512GB',           'samsung-s25-ultra-512gb', 'Samsung Galaxy S25 Ultra với bút S-Pen tích hợp, camera 200MP, chip Snapdragon 8 Elite.',        29990000, 27990000, 40,  'Samsung', 1),
(6,  'Samsung Galaxy A55 5G 128GB',              'samsung-a55-5g-128gb',    'Samsung A55 5G pin 5000mAh, màn hình Super AMOLED 120Hz sắc nét.',                               11990000, 10490000, 100, 'Samsung', 0),
(7,  'Xiaomi 14 Ultra 512GB',                    'xiaomi-14-ultra-512gb',   'Xiaomi 14 Ultra camera Leica, Snapdragon 8 Gen 3, sạc nhanh 90W.',                               19990000, 17990000, 35,  'Xiaomi',  0),
(9,  'MacBook Air M3 13 inch 8GB/256GB',         'macbook-air-m3-13',       'MacBook Air M3 mỏng nhẹ, hiệu năng vượt trội, pin 18 giờ, màn hình Liquid Retina sắc nét.',     28990000, 26990000, 30,  'Apple',   1),
(9,  'MacBook Pro M4 14 inch 16GB/512GB',        'macbook-pro-m4-14',       'MacBook Pro M4 chip Neural Engine, màn hình Liquid Retina XDR, âm thanh sáu loa.',              49990000, NULL,      20,  'Apple',   0),
(10, 'Asus ROG Strix G16 RTX 4060',             'asus-rog-strix-g16',      'Laptop gaming ROG Strix G16 Intel i7-13650HX, RTX 4060 8GB, RAM 16GB, SSD 512GB.',             29990000, 27490000, 25,  'Asus',    1),
(11, 'Dell XPS 13 9340 Intel Core Ultra 7',     'dell-xps-13-9340',        'Dell XPS 13 siêu mỏng nhẹ, chip Intel Core Ultra 7, màn hình OLED 2.8K, RAM 16GB.',            32990000, NULL,      15,  'Dell',    0),
(12, 'iPad Pro M4 11 inch WiFi 256GB',          'ipad-pro-m4-11',          'iPad Pro M4 màn hình OLED Tandem mỏng nhất từ trước, chip M4 siêu nhanh.',                      23990000, 21990000, 20,  'Apple',   1),
(15, 'AirPods Pro 2nd Generation USB-C',        'airpods-pro-2nd-usbc',    'AirPods Pro thế hệ 2 chống ồn chủ động H2, âm thanh Adaptive Audio thích nghi.',                6490000,  5990000,  200, 'Apple',   0),
(14, 'Cáp sạc USB-C 100W PD (2m)',              'cap-sac-usbc-100w',       'Cáp sạc nhanh USB-C 100W Power Delivery, dài 2 mét, tương thích MacBook, iPad, điện thoại.',    290000,   NULL,      500, 'Baseus',  0);

-- Ảnh sản phẩm placeholder
INSERT INTO product_images (product_id, image_path, is_primary) VALUES
(1,  'placeholder.png', 1),
(2,  'placeholder.png', 1),
(3,  'placeholder.png', 1),
(4,  'placeholder.png', 1),
(5,  'placeholder.png', 1),
(6,  'placeholder.png', 1),
(7,  'placeholder.png', 1),
(8,  'placeholder.png', 1),
(9,  'placeholder.png', 1),
(10, 'placeholder.png', 1),
(11, 'placeholder.png', 1),
(12, 'placeholder.png', 1);

-- Thông số kỹ thuật mẫu cho iPhone 16 Pro Max
INSERT INTO product_specs (product_id, spec_key, spec_value, sort_order) VALUES
(1, 'Màn hình',      '6.9 inch Super Retina XDR OLED, 120Hz ProMotion', 1),
(1, 'Chip',          'Apple A18 Pro 3nm',                                 2),
(1, 'RAM',           '8GB',                                               3),
(1, 'Bộ nhớ trong',  '256GB',                                             4),
(1, 'Camera sau',    '48MP + 48MP + 12MP (Tetraprism 5x zoom)',           5),
(1, 'Camera trước',  '12MP TrueDepth',                                    6),
(1, 'Pin',           '4685 mAh, sạc nhanh 25W, MagSafe 25W',             7),
(1, 'Kết nối',       'USB-C USB 3, Wi-Fi 7, Bluetooth 5.3, NFC',         8),
(1, 'Hệ điều hành',  'iOS 18',                                            9),
(1, 'Màu sắc',       'Titan Đen, Titan Trắng, Titan Sa Mạc, Titan Tự Nhiên', 10);

-- Voucher mẫu
INSERT INTO vouchers (code, discount_type, discount_value, min_order_value, max_uses, expires_at) VALUES
('UPNEX10',    'percent', 10,  500000,   100, DATE_ADD(NOW(), INTERVAL 30 DAY)),
('WELCOME50K', 'fixed',   50000, 200000, 500, DATE_ADD(NOW(), INTERVAL 60 DAY)),
('DIAMOND15',  'percent', 15,  1000000,  50,  DATE_ADD(NOW(), INTERVAL 15 DAY));

-- User mẫu để test
-- Email: user@test.com  |  Mật khẩu: User@1234
INSERT INTO users (name, email, password, phone, tier, total_spent) VALUES
('Nguyễn Văn Test', 'user@test.com',
 '$2y$12$LJ3p0H3Z6YWQO7lc3A9U4eKWxNLa5MZu0sK7g1YbP5vRQixoXzQbS',
 '0901234567', 'Gold', 6500000);
