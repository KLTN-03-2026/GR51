# TÀI LIỆU CHI TIẾT HỆ THỐNG QUẢN LÝ CỬA HÀNG GUNPLA COFFEE

## 1. TỔNG QUAN HỆ THỐNG

Dự án **Gunpla Coffee** là một hệ sinh thái quản lý cửa hàng cà phê hiện đại, bao gồm 4 thành phần chính hoạt động đồng bộ:

- **Backend**: Trung tâm xử lý dữ liệu (Laravel RESTful API).
- **Cafe Admin**: Nền tảng quản trị dành cho chủ cửa hàng (Vue.js).
- **Cafe POS**: Ứng dụng dành cho nhân viên tại quầy và pha chế (Flutter).
- **Cafe Web Order**: Nền tảng gọi món tại bàn dành cho khách hàng (Vue.js).

---

## 2. CHI TIẾT CÁC CHỨC NĂNG THEO THÀNH PHẦN

### 2.1 Backend (Laravel) - "Trái tim" của hệ thống

- **Xác thực & Bảo mật**: Sử dụng Laravel Sanctum để quản lý Token. Phân quyền chặt chẽ giữa Admin và Nhân viên.
- **Quản lý Đơn hàng (Order Logic)**:
  - Xử lý đơn hàng từ hai nguồn: Trực tiếp tại quầy (POS) và Gọi món tại bàn (Web Order).
  - Tự động hóa quy trình: Chờ xác nhận -> Đang pha chế -> Hoàn thành/Đã giao.
- **Quản lý Kho & Công thức (Inventory & Recipe)**:
  - Định lượng nguyên liệu cho từng món ăn/đồ uống (Công thức).
  - Tự động trừ kho khi món ăn được chuyển sang trạng thái "Đang pha chế".
  - Cảnh báo nguyên liệu sắp hết.
- **Xử lý Thời gian thực (Real-time)**: Sử dụng Laravel Reverb (WebSocket) để đẩy thông báo gọi nhân viên, cập nhật đơn hàng KDS ngay lập tức mà không cần tải lại trang.
- **Tích hợp Thanh toán**: Kết nối với cổng thanh toán PayOS.
- **AI Integration**: Tích hợp Google Gemini API để phân tích dữ liệu kinh doanh và hỗ trợ chủ cửa hàng.

### 2.2 Cafe Admin (Quản trị viên)

- **Dashboard (Bảng điều khiển)**: Theo dõi doanh thu theo giờ, số bàn đang có khách, đơn hàng mới nhất.
- **Quản lý Thực đơn**: Thêm/Sửa/Xóa món ăn, danh mục, kích cỡ (S/M/L) và Topping.
- **Quản lý Công thức**: Thiết lập định lượng nguyên liệu (ví dụ: 1 cốc cà phê sữa cần 20g cà phê hạt, 30ml sữa đặc).
- **Quản lý Kho**: Nhập kho, kiểm kho, xem lịch sử biến động kho.
- **Báo cáo & Thống kê**: Biểu đồ doanh thu theo ngày/tháng/năm, thống kê món bán chạy, hiệu suất đơn hàng.
- **Trợ lý AI**: Chatbot hỗ trợ chủ quán giải đáp các câu hỏi như "Món nào bán chạy nhất tháng này?", "Cần nhập thêm nguyên liệu gì?".

### 2.3 Cafe POS (Nhân viên & Pha chế)

- **Quản lý Bàn**: Xem trực quan sơ đồ bàn, trạng thái bàn (Trống/Có khách/Chờ thanh toán).
- **Giao diện Bán hàng**: Chọn món nhanh, tùy chỉnh topping/size, ghi chú cho bếp.
- **Màn hình KDS (Kitchen Display System)**: Hiển thị danh sách món cần làm cho bộ phận pha chế, cập nhật trạng thái món đã xong.
- **Quản lý Ca làm**: Mở ca (nhập tiền đầu ca), Kết ca (tổng hợp doanh thu tiền mặt/chuyển khoản).

### 2.4 Cafe Web Order (Khách hàng)

- **Quét mã QR**: Khách hàng quét mã tại bàn để truy cập menu mà không cần tải app.
- **Gọi món & Thanh toán**: Chọn món, xem giỏ hàng và thanh toán trực tuyến qua QR Code.
- **Gọi nhân viên**: Nút bấm hỗ trợ khách hàng gọi nhân viên ngay trên web (thông báo đẩy về POS).
- **Đánh giá**: Khách hàng để lại phản hồi về chất lượng món ăn và dịch vụ.

---

## 3. DANH SÁCH CÂU HỎI BẢO VỆ (DỰ KIẾN)

### Nhóm 1: Câu hỏi về Kỹ thuật & Công nghệ

1. **Tại sao bạn chọn kiến trúc Microservices/Decoupled thay vì Monolith?**
   - _Gợi ý trả lời:_ Giúp hệ thống linh hoạt, dễ mở rộng. POS cần hiệu năng cao (Flutter), Web Order cần nhẹ nhàng, dễ tiếp cận (Vue), Backend tập trung xử lý logic nghiệp vụ và bảo mật.
2. **Cơ chế Real-time trong hệ thống được thực hiện như thế nào?**
   - _Gợi ý trả lời:_ Sử dụng Laravel Reverb (WebSocket). Khi có sự kiện (ví dụ: khách gọi nhân viên), Backend phát tín hiệu qua Channel, các Client (POS) đăng ký Channel đó sẽ nhận được thông báo ngay lập tức.
3. **Làm thế nào để đảm bảo tính nhất quán của kho hàng khi có nhiều đơn hàng cùng lúc?**
   - _Gợi ý trả lời:_ Sử dụng Database Transaction trong Laravel và cơ chế Row Locking để tránh tình trạng "Race Condition" khi trừ kho.

### Nhóm 2: Câu hỏi về Nghiệp vụ & Logic

1. **Quy trình trừ kho diễn ra ở bước nào? Tại sao?**
   - _Gợi ý trả lời:_ Trừ kho khi đơn hàng chuyển sang "Đang pha chế" (Processing). Tránh trừ kho khi khách mới chỉ đặt món (chờ xác nhận) vì đơn có thể bị hủy, và tránh trừ khi đã xong (Finished) vì lúc đó nguyên liệu đã thực tế được dùng rồi.
2. **Hệ thống xử lý thế nào nếu khách hàng thanh toán online nhưng sau đó muốn hủy món?**
   - _Gợi ý trả lời:_ Hệ thống có quy trình hoàn tiền (Refund) thông qua API của PayOS hoặc ghi nhận đơn hủy để kế toán đối soát thủ công tùy cấu hình.
3. **AI Trợ lý lấy dữ liệu từ đâu để tư vấn cho chủ quán?**
   - _Gợi ý trả lời:_ Backend tổng hợp dữ liệu từ các bảng `don_hang`, `nguyen_lieu`, `danh_gia` sau đó gửi kèm prompt context cho Gemini API để đưa ra nhận xét chính xác nhất.

### Nhóm 3: Câu hỏi về Tính ứng dụng & Mở rộng

1. **Điểm mạnh nhất của dự án so với các phần mềm POS có sẵn trên thị trường?**
   - _Gợi ý trả lời:_ Tích hợp sâu AI hỗ trợ quản lý, Web Order QR tối ưu nhân sự, và quản lý kho chi tiết đến từng gram nguyên liệu (thay vì chỉ quản lý số lượng thành phẩm).
2. **Nếu cửa hàng mất kết nối Internet, hệ thống có hoạt động được không?**
   - _Gợi ý trả lời:_ Hiện tại hệ thống chạy trên nền tảng Cloud/Network. Hướng phát triển là triển khai Local Server tại quán để hoạt động Offline và đồng bộ khi có mạng lại.

---

## 4. CÁC ĐIỂM SÁNG TRONG CODE CẦN NHẤN MẠNH

- **Middleware Phân quyền**: Check role `admin` cho các tác vụ nhạy cảm.
- **Event-Driven Architecture**: Sử dụng Laravel Events/Listeners (như `OrderCancelled`, `OrderPlaced`) để tách biệt các tác vụ như gửi thông báo, trừ kho, ghi log.
- **Clean Architecture trong Flutter**: Sử dụng Provider/State Management để quản lý logic UI tách biệt với dữ liệu.
- **Responsive Design**: Cafe Admin và Web Order hiển thị tốt trên mọi thiết bị.
