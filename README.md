# ☕ NGHIÊN CỨU VÀ PHÁT TRIỂN NỀN TẢNG SỐ HÓA QUY TRÌNH PHỤC VỤ VÀ THANH TOÁN TRONG QUẢN LÝ QUÁN CAFE TÍCH HỢP AI HỖ TRỢ NGƯỜI QUẢN LÝ - Tổng Quan Hệ Thống

Đây là dự án về một hệ thống quản lý quán cafe và điểm bán hàng (POS) toàn diện được thiết kế hiện đại, bao gồm hai thành phần chính: **Backend API (Laravel)** và **Frontend Client (Flutter)**.

Dưới đây là tổng hợp toàn bộ các tiến độ, kiến trúc và tính năng đã được hoàn thiện cho cả hai nền tảng trong giai đoạn Sprint 1.(bao gồm xây dựng API ở phía backend và frontend cho máy POS)

---

## 🏗️ 1. Hệ Thống Backend (Thư mục `backend/`)

Backend đóng vai trò cốt lõi xử lý nghiệp vụ, quản lý dữ liệu và cung cấp RESTful API cho các client.

- **Công nghệ cốt lõi:** Laravel 12 (PHP 8.2), Eloquent ORM, Laravel Sanctum.
- **Tình trạng:** Đã cấu hình và xử lý các lỗi mapping dữ liệu, thiết lập chuẩn hóa Primary Key để đảm bảo tính toàn vẹn trong các Transaction.

### Các Module đã hoàn thiện:

1. **Quản lý Thực đơn (Menu & Recipe):**
   - Hỗ trợ đa dạng cấu hình sản phẩm như Kích cỡ (Size) và Topping đính kèm.
   - Quản lý chi tiết Thành phần Công thức (Recipe) và hướng dẫn pha chế cho từng món ăn. Mới bổ sung API lấy thông tin chi tiết công thức (`/api/v1/mon-an/{id}/cong-thuc`).

2. **Quản lý Tồn kho (Inventory Management):**
   - Theo dõi tồn kho theo thời gian thực (Real-time).
   - Thiết lập các mức độ cảnh báo tồn kho an toàn/sắp hết/hết hàng qua API (`/api/v1/kho/ton-kho`).
   - **Trừ kho tự động (Auto-deduction):** Sử dụng Database Transaction để tự động tính toán khối lượng nguyên liệu và trừ kho khi trạng thái đơn hàng được chuyển sang "Đã pha chế". Hệ thống tự nhận diện công thức cấu thành để tính đúng khối lượng xuất bán.

3. **Quản lý Đơn hàng & POS:**
   - Xử lý mượt mà luồng tạo đơn hàng, các lựa chọn (Size/Topping), hình thức thanh toán (Tiền mặt/Chuyển khoản).
   - Quản lý trạng thái từng món (Chờ xử lý -> Đang pha chế -> Hoàn thành).

4. **Quản lý Ca làm việc (Shift Management):**
   - Các API Mở ca (`/ca-lam/mo-ca`), Đóng ca (`/ca-lam/ket-ca`) và Lấy thông tin ca hiện tại (`/ca-lam/hien-tai`).
   - Tổng hợp doanh thu, lượng đơn bán trong ca phục vụ đối soát giao ca.

5. **Quản lý Bàn & Khu vực:**
   - Map/Sơ đồ bàn và các khu vực, cập nhật tình trạng bàn trống/đang có khách trực tiếp.

---

## 📱 2. Hệ Thống Frontend POS (Thư mục `pos-fe/`)

Giao diện người dùng cho nhân viên thu ngân và pha chế (Client App) được tổ chức theo kiến trúc Clean Code và **MVVM** chuẩn mực.

- **Công nghệ cốt lõi:** Flutter (SDK 3.10+), Provider (State Management), HTTP Client.
- **Giao diện (UI/UX):** Phong cách Dark/Orange hiện đại, hỗ trợ thanh điều hướng thân thiện (Sidebar Navigation), tối ưu tương tác chạm trên màn hình POS.

### Các Module đã hoàn thiện:

1. **Giao diện Bán hàng (Menu/Sales):**
   - Danh sách món ăn phân theo danh mục (`MenuView`).
   - Tích hợp Modal chọn phân loại Món (Size/Đá/Đường/Topping).
   - Quản lý Giỏ hàng (`CartViewModel`) và Modal Thanh toán/Chọn Bàn (`TableSelectionModal`).
   - Đã fix triệt để lỗi mapping payload khi gửi mã thanh toán và phương thức (chuẩn hóa mã String gửi xuống backend).

2. **Xử lý Đơn pha chế / KDS (Order List):**
   - Lắng nghe đơn đổ về, hiển thị dạng thẻ tập trung.
   - Cập nhật trạng thái từng đơn hàng để kích hoạt luồng "Trừ kho tự động" dưới backend.
   - 🌟 **Tích hợp Popup Công thức:** Nút hiển thị nhanh danh sách nguyên liệu và hướng dẫn pha chế cho Barista ngay tại quầy.

3. **Quản lý Tồn kho (Inventory Tab):**
   - Giao diện trực quan phân loại mức tồn kho (`InventoryView`).
   - Hệ thống thẻ màu thông minh: 🔴 **Đỏ** (Hết hàng), 🟠 **Cam** (Sắp hết), 🟢 **Xanh** (An toàn).
   - Đã tích hợp tính năng tìm kiếm (Search) theo tên nguyên liệu và nút tải lại dữ liệu (Reload) nhanh.

4. **Lịch sử Đơn hàng (Order History):**
   - Bảng tra cứu toàn bộ đơn đã và đang xử lý, xem nhanh tổng thu và phương thức thanh toán.

5. **Tài Khoản & Bàn giao Ca (Account & Shift):**
   - Giao diện mở/kết ca.
   - Thống kê đối soát tiền mặt với hệ thống cho nhân viên trực ca (`ShiftViewModel`).

---

## Hướng Dẫn Nhanh (Quick Start)

### Chạy Backend

```bash
cd backend
# Cài đặt (nếu chưa chạy): composer install && cp .env.example .env && php artisan key:generate
php artisan serve
```

_(Backend sẽ chạy ở `http://127.0.0.1:8000`)_

### Chạy Frontend

```bash
cd pos-fe
flutter pub get
flutter run
```

_(Yêu cầu API backend đang chạy ở `localhost:8000` để các tính năng đồng bộ hoàn hảo)._

---

_Tài liệu này được tổng hợp để theo dõi tiến độ hoàn thành các tính năng kết nối giữa 2 hệ thống Frontend và Backend cho đồ án Smart Cafe POS._
