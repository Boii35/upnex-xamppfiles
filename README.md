# 🛒 UPNEX — Web Bán Đồ Công Nghệ

## Thông tin dự án
- **Stack**: PHP 8+, PDO, MySQL, Bootstrap 5, AJAX (Fetch API)
- **Môi trường**: XAMPP (localhost)
- **Kiến trúc**: MVC + OOP (class phân tầng rõ ràng)

---

## 📦 Cài đặt

### Bước 1 — Copy vào XAMPP
```
Sao chép thư mục upnex/ vào: C:\xampp\htdocs\upnex\
```

### Bước 2 — Import Database
1. Mở phpMyAdmin: http://localhost/phpmyadmin
2. Tạo database tên `upnex` (utf8mb4_unicode_ci)
3. Import file `upnex.sql`

### Bước 3 — Cấu hình (nếu cần)
Mở file `config/config.php`, kiểm tra:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'upnex');
define('DB_USER', 'root');
define('DB_PASS', '');           // Mặc định XAMPP không có password
define('BASE_URL', 'http://localhost/upnex');
```

### Bước 4 — Tạo thư mục uploads
```
Đảm bảo thư mục uploads/products/ có quyền ghi (write permission)
```

### Bước 5 — Truy cập
| URL | Mô tả |
|-----|-------|
| http://localhost/upnex/ | Trang chủ user |
| http://localhost/upnex/?case=admin_login | Trang đăng nhập admin |

---

## 🔑 Tài khoản mặc định

| Loại | Email | Mật khẩu |
|------|-------|-----------|
| Admin | admin@upnex.vn | Admin@123 |

---

## 📁 Cấu trúc thư mục

```
upnex/
├── config/
│   └── config.php          ← Cấu hình DB, hằng số
├── controller/
│   ├── UserController.php  ← Router + xử lý request user
│   └── AdminController.php ← Router + xử lý request admin
├── model/
│   ├── Database.php        ← Singleton PDO (không dùng MySQLi)
│   ├── BaseModel.php       ← Lớp cha, sanitize, validate
│   ├── UserModel.php       ← Đăng ký/login, cookie, tier VIP
│   ├── ProductModel.php    ← CRUD, tìm kiếm, phân trang
│   ├── OrderModel.php      ← Giỏ hàng, đặt hàng (Transaction)
│   └── CategoryModel.php   ← Category, Voucher, Employee
├── view/
│   ├── shared/             ← header, footer, product_card
│   ├── user/               ← Giao diện user
│   └── admin/              ← Giao diện admin
├── public/
│   ├── css/upnex.css       ← Custom CSS
│   └── js/upnex.js         ← AJAX utilities (Fetch API)
├── uploads/products/       ← Ảnh sản phẩm upload
├── index.php               ← Entry point (Front Controller)
├── .htaccess               ← Bảo mật Apache
└── upnex.sql               ← Schema + seed data
```

---

## 🔒 Bảo mật đã tích hợp

| Tính năng | Cách thực hiện |
|-----------|----------------|
| SQL Injection | PDO Prepared Statements — toàn bộ query dùng `?` binding |
| XSS | `htmlspecialchars()` ở mọi output, `strip_tags()` ở input |
| CSRF | Token ngẫu nhiên trong session, verify mọi POST request |
| Session Fixation | `session_regenerate_id(true)` sau login, mỗi 30 phút |
| Password | `password_hash(BCRYPT, cost=12)` + `password_verify()` |
| Cookie | HttpOnly, SameSite=Strict, token hash (không lưu password) |
| File Upload | Kiểm tra MIME type, giới hạn 5MB, chỉ allow jpg/png/webp |
| Admin Gate | Session check ở mọi admin route, không thể bypass |

---

## ⚙️ Tính năng chính

### Phía User
- ✅ Đăng ký / Đăng nhập / Đăng xuất
- ✅ Cookie "Nhớ đăng nhập" 30 ngày (HttpOnly, token hash)
- ✅ Tìm kiếm AJAX autocomplete realtime
- ✅ Lọc sản phẩm (danh mục, thương hiệu, giá)
- ✅ Giỏ hàng lưu DB (AJAX update/delete/add)
- ✅ Thanh toán: COD, Chuyển khoản, MoMo, VNPay
- ✅ Áp dụng voucher (kiểm tra ràng buộc đầy đủ)
- ✅ Theo dõi trạng thái đơn hàng (timeline)
- ✅ Hủy đơn khi còn "Chờ xác nhận"
- ✅ Đánh giá sản phẩm (sao + bình luận)
- ✅ Hạng VIP: Silver → Gold → Diamond
- ✅ Đổi mật khẩu (AJAX)
- Xem email do admin gửi tự động 
### Phía Admin (Session bảo vệ)
- ✅ Dashboard thống kê + biểu đồ Chart.js
- ✅ Quản lý sản phẩm (CRUD + upload ảnh multiple)
- ✅ Quản lý danh mục (cha / con)
- ✅ Quản lý đơn hàng (cập nhật trạng thái AJAX + ghi log)
- ✅ Quản lý khách hàng (xem tier, khóa/mở tài khoản)
- ✅ Quản lý nhân viên (thêm, khóa/mở)
- ✅ Quản lý voucher (tạo, xóa, xem progress)
- ✅ Quản lý đánh giá (ẩn/hiện AJAX)
- ✅ Báo cáo doanh thu (tuần/tháng/năm) + biểu đồ dual-axis
- Khuyến mãi sản phẩm
---

## 📧 Cài PHPMailer (Giai đoạn 5)

```bash
# Cách 1: Composer
composer require phpmailer/phpmailer

# Cách 2: Thủ công
# Download từ https://github.com/PHPMailer/PHPMailer
# Giải nén vào includes/PHPMailer/
```

---

*UPNEX — Đồ án môn học Web PHP/PDO*
