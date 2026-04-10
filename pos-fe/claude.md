# THÔNG TIN DỰ ÁN & QUY CHUẨN LÀM VIỆC (PROJECT CONTEXT)

## 1. Tổng quan dự án (Project Overview)

- **Tên dự án:** Hệ thống quản lý quán Cafe All-in-one & Đặt bàn QR.
- **Mục tiêu:** Xây dựng một hệ thống hoàn chỉnh phục vụ 3 đối tượng: Khách hàng (Web Order qua QR), Nhân viên (App POS All-in-one gộp chung Thu ngân & Pha chế), và Quản lý (Admin Dashboard).
- **Tính năng cốt lõi:** Quét QR tại bàn, quản lý giỏ hàng, đặt đơn, xử lý trạng thái đơn hàng thời gian thực (Real-time), trừ kho nguyên liệu tự động, và đặc biệt là tích hợp Trợ lý AI phân tích dữ liệu kinh doanh.
- **Tính chất:** Đây là một dự án đồ án khóa luận học thuật, yêu cầu code sạch, logic chặt chẽ và bám sát các luồng nghiệp vụ thực tế.

## 2. Tech Stack (Công nghệ sử dụng)

- **Backend:** PHP / Laravel Framework. Đóng vai trò cung cấp RESTful API thuần túy, không render view (không dùng Blade).
- **Frontend:** Flutter. Biên dịch ra Web cho Khách hàng và Mobile/Tablet App cho Nhân viên (POS).
- **Cơ sở dữ liệu:** RDBMS (MySQL) với 15 bảng đã được thiết kế tối ưu (nhóm Nhân sự, Khu vực, Thực đơn, Đơn hàng, Kho).

## 3. Kiến trúc phần mềm (Software Architecture)

Agent phải tuân thủ nghiêm ngặt các kiến trúc sau:

- **Frontend (Flutter):** BẮT BUỘC áp dụng mô hình **MVVM (Model - View - ViewModel)**.
  - `Model`: Chứa các data class (như NhanSu, MonAn, DonHang) và logic parsing JSON.
  - `View`: Chỉ chứa UI (Widgets), KHÔNG chứa logic gọi API hay xử lý nghiệp vụ tại đây.
  - `ViewModel`: Xử lý logic, gọi API, quản lý trạng thái (State Management) và cập nhật UI.
- **Backend (Laravel):** Áp dụng mô hình **Repository Pattern** hoặc **Service Pattern** để tách biệt logic nghiệp vụ khỏi Controller. Controller chỉ làm nhiệm vụ nhận Request và trả về Response.

## 4. Quy trình làm việc (Workflow & SCRUM)

- **Làm việc theo Sprint:** Agent phải giải quyết công việc theo từng chức năng nhỏ gọn được giao trong mỗi Prompt, tuyệt đối KHÔNG tự ý "nhảy cóc" code lan man sang các tính năng chưa được yêu cầu.
- **Chờ phê duyệt (Approval):** Sau khi sinh code cho một module, agent phải dừng lại, giải thích ngắn gọn và chờ người dùng kiểm tra, xác nhận chạy thành công mới được chuyển sang task tiếp theo.
- **Báo cáo lỗi:** Nếu có lỗi xảy ra (Exception/Red screen), agent cần phân tích log lỗi từng bước thay vì đập đi viết lại toàn bộ file một cách mù quáng.

## 5. Quy chuẩn Code (Coding Conventions)

### 5.1. Naming Convention (Đặt tên)

- **Dart/Flutter:** Tên biến/hàm dùng `camelCase`. Tên Class/File dùng `PascalCase` và `snake_case`.
- **Laravel/Database:** Tên bảng, tên cột trong DB bắt buộc dùng tiếng Việt không dấu, định dạng `snake_case` (ví dụ: `chi_tiet_don_hang`, `ten_mon`).
- **API Route:** Đặt theo chuẩn RESTful, dùng danh từ số nhiều (ví dụ: `GET /api/v1/mon-an`, `POST /api/v1/don-hang`).

### 5.2. Định dạng dữ liệu trả về (JSON Standard Response)

Tất cả các API từ Laravel phải trả về một định dạng JSON thống nhất như sau:

```json
{
  "success": true,           // true hoặc false
  "message": "Mô tả kết quả",// Ví dụ: "Lấy danh sách thành công"
  "data": { ... }            // Payload dữ liệu chính (Object hoặc Array)
}
```

### 5.3. Xử lý lỗi (Error Handling)

- **Backend:** Sử dụng khối try-catch ở mọi Service/Controller. Trả về đúng mã HTTP Status Code: 200 (OK), 201 (Created), 400 (Bad Request), 401 (Unauthorized), 404 (Not Found), 500 (Internal Server Error).
- **Frontend:** Bắt buộc có trạng thái Loading, Empty State (khi không có dữ liệu), và hiển thị SnackBar/Dialog báo lỗi rõ ràng cho người dùng khi API gọi thất bại.

flutter run -d chrome --web-browser-flag "--disable-web-security"
http://10.0.2.2:8000/api/v1
