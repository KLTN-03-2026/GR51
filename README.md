# Smart Cafe - Backend API

## Giới thiệu (Introduction)
Smart Cafe Backend là hệ thống API được xây dựng bằng Laravel 12, cung cấp các nền tảng nghiệp vụ cho ứng dụng quản lý quán cafe và điểm bán hàng (POS). Hệ thống quản lý toàn diện quy trình từ gọi món, thanh toán, quản lý ca làm việc, đến kiểm kho và công thức pha chế.

## Công nghệ sử dụng (Tech Stack)
- **Framework:** Laravel 12 (PHP ^8.2)
- **Authentication:** Laravel Sanctum
- **Database:** Hỗ trợ đa cơ sở dữ liệu qua Eloquent ORM (Mặc định thiết lập tương thích tốt với MySQL và SQLite)

## Chức năng chính (Key Features)

### 1. Quản lý Bán hàng & Đơn hàng (POS & Order Management)
- Tạo và xử lý luồng đơn hàng trực tiếp (POS).
- Hệ thống KDS (Kitchen Display System) hỗ trợ trạm pha chế.
- Quản lý chặt chẽ trạng thái đơn hàng (chờ xử lý, đang pha chế, đã hoàn thành) và thanh toán.

### 2. Quản lý Thực đơn (Menu Management)
- Cung cấp danh sách các sản phẩm/món ăn, chia theo danh mục (Danh Mục).
- Hỗ trợ đa dạng cấu hình sản phẩm như Kích cỡ (Size) và Topping đính kèm.
- Quản lý công thức chi tiết cho từng món ăn (Recipe).

### 3. Quản lý Tồn kho (Inventory Management)
- Tra cứu tồn kho theo thời gian thực các nguyên liệu.
- Thiết lập mức cảnh báo hết hàng/tồn kho thấp.
- Trừ kho tự động chính xác dựa trên công thức cấu thành khi pha chế hoàn tất.

### 4. Quản lý Ca làm việc (Shift Management)
- Tính năng mở ca, đóng ca dành cho nhân sự.
- Tổng hợp báo cáo, bàn giao ca đầy đủ với thông tin doanh thu, số đơn hàng.

### 5. Quản lý Bàn & Khu vực (Table/Area Management)
- Quản lý sơ đồ quán qua khái niệm Khu vực và Bàn.
- Theo dõi tình trạng của từng bàn theo thời gian thực.

## Cấu trúc API chính (API Endpoints)
Hệ thống sử dụng các chuẩn RESTful API, có tiền tố `/api/v1/`:

- **Auth:** `/login`, `/logout` (Yêu cầu xác thực `auth:sanctum` cho đa số route)
- **Ca làm:** `/ca-lam/hien-tai`, `/ca-lam/mo-ca`, `/ca-lam/ket-ca`
- **Đơn hàng:** `/don-hang`, `/don-hang/kds`, `/don-hang/{id}/status`
- **Thực đơn:** `/menu`, `/mon-an/{id}/cong-thuc`
- **Tồn kho:** `/kho/ton-kho`
- **Giao diện/Khu vực:** `/tables`

## Hướng dẫn cài đặt (Installation)

1. **Clone dự án lệnh thư mục:**
   ```bash
   git clone <repository_url>
   cd cafe-booking-be
   ```

2. **Chạy script thiết lập tự động:**
   ```bash
   composer setup
   ```
   *Bao gồm các tự động hóa: `composer install`, copy `.env`, tạo app key, chạy migrate cơ sở dữ liệu, `npm install` và `npm run build`.*

3. **Khởi động Local Server:**
   ```bash
   composer dev
   # Hoặc lệnh truyền thống: php artisan serve
   ```

## Đóng góp và Phát triển (Development)
Dự án được xây dựng với cấu trúc chặt chẽ của Laravel.
- **Models:** `app/Models` 
- **Controllers:** `app/Http/Controllers/Api/V1`
- **Routes:** `routes/api.php`
Dự án trực thuộc KLTN-03-2026/GR51.
