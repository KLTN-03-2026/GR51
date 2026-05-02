# GUNPLA CAFE - Nền Tảng Số Hóa Quản Lý Quán Cafe Tích Hợp AI

Gunpla Cafe là một hệ sinh thái quản lý quán cafe và điểm bán hàng (POS) toàn diện, hiện đại. Dự án cung cấp giải pháp chuyển đổi số sâu rộng từ khâu gọi món, pha chế, thanh toán, quản lý kho tự động đến phân tích dữ liệu thông minh bằng Trợ lý AI.

Hệ thống được thiết kế theo kiến trúc Client-Server, bao gồm **4 phân hệ chính**:

1. **Backend API** (Laravel)
2. **Frontend POS App** (Flutter)
3. **Web Admin Dashboard** (Vue 3)
4. **Customer QR Web Order** (Vue 3)

---

## 1. Hệ Thống Backend API (`/backend`)

Đóng vai trò cốt lõi xử lý nghiệp vụ, kết nối cơ sở dữ liệu và cung cấp RESTful API bảo mật cho toàn bộ các client.

- **Công nghệ:** Laravel 12 (PHP 8.x), Eloquent ORM, MySQL, Laravel Sanctum.
- **Tính năng nổi bật:**
  - **Cấu hình Thực đơn (Menu):** Quản lý chi tiết Món ăn, Kích cỡ (Size), Topping và Công thức pha chế (Recipe).
  - **Hệ thống Thuế (Tax System):** Tính toán thuế linh hoạt, cập nhật giá trị thuế cho từng đơn hàng nhằm phục vụ báo cáo tài chính chính xác.
  - **Trừ Kho Tự Động & Chống Âm Kho:** Tự động tính toán và trừ nguyên liệu dựa trên công thức khi đơn hàng "Đã pha chế". Tích hợp validation nghiêm ngặt ngăn chặn triệt để tình trạng tồn kho âm.
  - **Xử lý Luồng Đơn hàng:** Xử lý đa dạng các nguồn đơn (tại quầy, QR code), hỗ trợ thanh toán tiền mặt/chuyển khoản ngân hàng, tính toán chính xác giá tiền bao gồm cả topping.
  - **AI Integration Logic:** Cung cấp dải ngữ cảnh dữ liệu toàn diện (bao gồm giao dịch chi tiết, doanh thu theo ngày/tháng) cho Google Gemini API để thực hiện các phân tích kinh doanh chuyên sâu.
  - **Quản lý Ca làm việc:** Theo dõi mở ca, kết ca, đối soát doanh thu và tối ưu hóa luồng giao ca.
  - **Hệ thống Đánh giá:** Xử lý và lưu trữ thông tin phản hồi, đánh giá dịch vụ (1-5 sao) từ khách hàng.

## 2. Phần Mềm Máy Bán Hàng POS (`/pos-fe`)

Giao diện ứng dụng dành cho nhân viên thu ngân và Barista, tối ưu hóa cho màn hình cảm ứng POS tại quầy.

- **Công nghệ:** Flutter (SDK 3.10+), Provider (State Management), Dio/HTTP Client.
- **Tính năng nổi bật:**
  - **Giao diện Bán hàng (Sales):** Thao tác gọi món nhanh chóng, chọn size/topping trực quan. Hiển thị rõ ràng thông tin Thuế và cung cấp Popup mã QR chuyển khoản ngay trên màn hình thanh toán.
  - **Màn hình Pha chế KDS (Kitchen Display System):** Giúp Barista theo dõi đơn hàng theo thời gian thực. Tích hợp Popup xem nhanh **Công thức pha chế** ngay tại quầy.
  - **Theo dõi Tồn kho (Inventory):** Giám sát tình trạng nguyên liệu qua hệ thống thẻ màu trực quan (Đỏ - Hết hàng, Cam - Sắp hết, Xanh - An toàn), đảm bảo tính chính xác của dữ liệu kho.
  - **Lịch sử & Quản lý Ca (Shift Management):** Quản lý lịch sử giao dịch chi tiết, tối ưu hóa quy trình chốt ca, đối soát tiền mặt cuối ngày và theo dõi ca làm việc chuyên sâu.

## 3. Web Admin Dashboard & Trợ Lý AI (`/cafe-admin`)

Công cụ quản trị cấp cao dành cho Chủ quán / Người quản lý với giao diện Web Dark Mode hiện đại, sang trọng, mang lại trải nghiệm tối ưu cùng sức mạnh của trí tuệ nhân tạo.

- **Công nghệ:** Vue 3 (Composition API), Vite, Pinia, Vue Router, Chart.js, Axios.
- **Tính năng nổi bật:**
  - **Dashboard Thống kê:** Báo cáo trực quan về doanh thu, lượng đơn hàng, biểu đồ tăng trưởng và top món bán chạy qua Chart.js.
  - **Quản lý Toàn diện:** Cung cấp CRUD đầy đủ cho Thực đơn, Kho hàng, Bàn & Khu vực, Nhân sự và hệ thống Review của khách. Giao diện thân thiện (sử dụng Toast và Custom Dialogs thay vì Alert truyền thống).
  - ** Trợ Lý Quản Lý AI (Cafe AI):** Chatbot nổi (Floating Widget) tích hợp Google Gemini API ngay trên màn hình Admin, đóng vai trò như một chuyên gia dữ liệu, cung cấp:
    - _Predictive Inventory (Dự đoán Kho):_ Tính toán tốc độ tiêu thụ, tự động dự đoán thời gian hết nguyên liệu (vd: "Cà phê sẽ hết trong 2 ngày tới").
    - _Conversational BI:_ Cho phép người quản lý truy vấn doanh thu, đơn hàng bằng ngôn ngữ tự nhiên tiếng Việt.
    - _Menu Engineering & Analytics:_ Tự động phân loại món (Ngôi sao, Bò sữa, Đố đánh, Chó mực) dựa trên hiệu suất kinh doanh, đồng thời phát hiện bất thường và đưa ra lời khuyên tối ưu lợi nhuận.

## 4. Web Đặt Món Mã QR Khách Hàng (`/cafe-web-order`)

Ứng dụng Web App siêu nhẹ dành riêng cho khách hàng, cho phép tự order tại bàn cực nhanh mà không cần cài đặt App.

- **Công nghệ:** Vue 3, Vite, Axios.
- **Tính năng nổi bật:**
  - **Quét mã QR gọi món:** Khách hàng dùng điện thoại quét mã QR tại bàn để xem Menu thực tế của quán.
  - **Tự động đặt món:** Khách tự chọn món, tuỳ chỉnh Size, Topping và gửi đơn thẳng xuống hệ thống KDS/POS của nhân viên mà không cần chờ đợi.
  - **Hệ thống Feedback:** Khách hàng có thể chấm điểm (1-5 sao) và để lại bình luận về chất lượng đồ uống/dịch vụ trên giao diện theo dõi trạng thái đơn hàng ngay sau khi đơn được thanh toán.

---

## Hướng Dẫn Khởi Chạy (Quick Start)

### 1. Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Đảm bảo cấu hình CSDL MySQL và GEMINI_API_KEY trong file .env
php artisan migrate
php artisan serve
```

_(API Server sẽ chạy tại `http://127.0.0.1:8000`)_

### 2. POS Frontend (Flutter)

```bash
cd pos-fe
flutter pub get
flutter run
```

### 3. Web Admin (Vue 3)

```bash
cd cafe-admin
npm install
npm run dev
```

_(Dashboard chạy tại `http://localhost:5174`)_

### 4. Customer Web QR Order (Vue 3)

```bash
cd cafe-web-order
npm install
npm run dev
```

---

_Dự án là nền tảng quản lý được chuẩn hóa kiến trúc với định hướng tích hợp AI làm cốt lõi nhằm nâng cao năng lực vận hành và tối ưu hóa lợi nhuận._
