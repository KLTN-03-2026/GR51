# ☕ GUNPLA COFFEE - Hệ Sinh Thái Quản Lý Thông Minh & Tích Hợp AI

[![Laravel](https://img.shields.io/badge/Backend-Laravel%2012-red?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Vue3](https://img.shields.io/badge/Admin%20%26%20Order-Vue%203-green?style=for-the-badge&logo=vuedotjs)](https://vuejs.org)
[![Flutter](https://img.shields.io/badge/POS%20App-Flutter-blue?style=for-the-badge&logo=flutter)](https://flutter.dev)
[![AI](https://img.shields.io/badge/AI%20Power-Google%20Gemini-orange?style=for-the-badge&logo=googlegemini)](https://deepmind.google/technologies/gemini/)

**Gunpla Coffee** là đồ án Khóa luận tốt nghiệp (KLTN) năm 2026 của **Nhóm 51**. Đây là một hệ sinh thái quản lý quán cafe và điểm bán hàng (POS) toàn diện, ứng dụng trí tuệ nhân tạo (AI) để tối ưu hóa quy trình vận hành và phân tích kinh doanh.

---

## Kiến Trúc Hệ Thống

Hệ thống được xây dựng theo mô hình **Client-Server** hiện đại với 4 phân hệ chính hoạt động đồng bộ qua Real-time WebSockets:

| Module           | Thư mục           | Công nghệ                 | Vai trò                               |
| :--------------- | :---------------- | :------------------------ | :------------------------------------ |
| **Backend API**  | `/backend`        | Laravel 12, MySQL, Reverb | Trung tâm xử lý dữ liệu & Real-time   |
| **POS Frontend** | `/cafe-pos`       | Flutter (Dart)            | Ứng dụng bán hàng & Pha chế (KDS)     |
| **Web Admin**    | `/cafe-admin`     | Vue 3, Vite, Chart.js     | Quản trị, Báo cáo & Trợ lý AI         |
| **Web Order**    | `/cafe-web-order` | Vue 3, Vite               | Khách hàng quét mã QR gọi món tại bàn |

---

## Tính Năng Cốt Lõi

### 1. Trợ Lý Quản Lý AI (Gemini AI Integration)

- **Conversational BI:** Truy vấn doanh thu, đơn hàng bằng ngôn ngữ tự nhiên (Tiếng Việt).
- **Dự đoán tồn kho:** Tự động phân tích tốc độ tiêu thụ và cảnh báo thời điểm hết nguyên liệu.
- **Menu Engineering:** Phân tích hiệu suất món ăn (Star, Cash Cow, Puzzle, Dog) và đưa ra gợi ý tối ưu menu.

### 2. Quản Lý Kho & Công Thức (Advanced Inventory)

- **Trừ kho tự động:** Nguyên liệu được trừ ngay khi Barista xác nhận hoàn thành món dựa trên định mức công thức (Recipe).
- **Chống âm kho:** Hệ thống kiểm tra tồn kho theo thời gian thực trước khi cho phép đặt món.
- **Cảnh báo thông minh:** Hệ thống thẻ màu trực quan theo dõi mức độ an toàn của nguyên liệu.

### 3. Vận Hành Real-time & Thanh Toán

- **Real-time Order:** Đơn hàng từ QR Web Order gửi trực tiếp đến POS và màn hình Barista (KDS) ngay lập tức qua Laravel Reverb.
- **Thanh toán linh hoạt:** Hỗ trợ Tiền mặt và Chuyển khoản (tích hợp tạo mã QR thanh toán nhanh).
- **Quản lý ca làm việc:** Quy trình mở ca/kết ca chặt chẽ, tự động đối soát doanh thu thực tế.

### 4. Trải Nghiệm Khách Hàng (UX)

- **QR Order:** Không cần cài đặt app, quét mã tại bàn để xem menu và gọi món.
- **Hệ thống đánh giá:** Thu thập phản hồi khách hàng ngay sau khi hoàn thành đơn hàng để cải thiện dịch vụ.

---

## Công Nghệ Sử Dụng

### Backend

- **Framework:** Laravel 12 (PHP 8.2+)
- **Database:** MySQL
- **Real-time:** Laravel Reverb (WebSockets)
- **Auth:** Laravel Sanctum
- **AI:** Google Gemini Pro API

### Mobile/Desktop App (POS)

- **Framework:** Flutter 3.x
- **State Management:** Provider
- **Local Storage:** Shared Preferences

### Web (Admin & Order)

- **Framework:** Vue 3 (Composition API)
- **Build Tool:** Vite
- **Styling:** Vanilla CSS, Lucide Icons
- **Analytics:** Chart.js

---

## Hướng Dẫn Cài Đặt (Local Development)

### 1. Cấu hình Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Cấu hình DB_DATABASE, DB_USERNAME, DB_PASSWORD trong .env
# Thêm GEMINI_API_KEY từ Google AI Studio
php artisan migrate --seed
php artisan serve
```

### 2. Chạy POS App

```bash
cd cafe-pos
flutter pub get
flutter run
```

### 3. Khởi chạy Web Admin

```bash
cd cafe-admin
npm install
npm run dev
```

### 4. Khởi chạy Web Order

```bash
cd cafe-web-order
npm install
npm run dev
```

---

## Tài Liệu Dự Án

Toàn bộ hồ sơ thiết kế, kế hoạch dự án và báo cáo kiểm thử được lưu trữ tại thư mục `/docs`:

- `SCA_KLTN_Nhom51_5.ProjectUserInterfaceDesign.docx`: Thiết kế UI/UX.
- `SCA_KLTN_Nhom51_6.ProjectDatabase.docx`: Thiết kế CSDL chi tiết.
- `SCA_KLTN_Nhom51_TÓM TẮT BÁO CÁO.docx`: Tổng quan dự án.

---

## Đội Ngũ Phát Triển - Nhóm 51

Dự án được thực hiện trong khuôn khổ Khóa luận tốt nghiệp năm 2026.

---

_© 2026 Gunpla Coffee Team - All Rights Reserved._
