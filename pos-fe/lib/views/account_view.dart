import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'viewmodels/auth_viewmodel.dart';

class AccountView extends StatefulWidget {
  const AccountView({Key? key}) : super(key: key);

  @override
  State<AccountView> createState() => _AccountViewState();
}

class _AccountViewState extends State<AccountView> {
  @override
  void initState() {
    super.initState();
    // Tự động kéo dữ liệu ca làm ngay khi vừa mở Tab này
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AuthViewModel>().fetchCurrentShift();
    });
  }

  @override
  Widget build(BuildContext context) {
    final viewModel = context.watch<AuthViewModel>();

    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      body: SafeArea(
        child: viewModel.isLoading
            ? const Center(child: CircularProgressIndicator(color: Colors.blue))
            : Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // HEADER CÓ CHỨA NÚT ĐĂNG XUẤT CỦA BẠN
                  // HEADER CÓ NÚT REFRESH VÀ ĐĂNG XUẤT
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Tài Khoản & Ca Làm',
                          style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Colors.black87),
                        ),
                        Row(
                          children: [
                            // Nút Làm mới dữ liệu
                            IconButton(
                              icon: const Icon(Icons.refresh, color: Colors.blue),
                              tooltip: 'Làm mới doanh thu',
                              onPressed: () => viewModel.fetchCurrentShift(), 
                            ),
                            const SizedBox(width: 8),
                            // Nút Đăng xuất
                            IconButton(
                              icon: const Icon(Icons.logout, color: Colors.red),
                              tooltip: 'Đăng xuất tài khoản',
                              onPressed: () => viewModel.logout(), 
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),
                    
                    if (!viewModel.isShiftActive)
                      Expanded(
                        child: Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.storefront_outlined, size: 80, color: Colors.grey[400]),
                              const SizedBox(height: 16),
                              const Text('Bạn chưa mở ca làm việc.', style: TextStyle(fontSize: 18, color: Colors.grey)),
                              const SizedBox(height: 24),
                              ElevatedButton.icon(
                                onPressed: () => _showOpenShiftDialog(context, viewModel),
                                icon: const Icon(Icons.play_circle_fill, color: Colors.white),
                                label: const Text('Bắt Đầu Ca Làm Mới', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white)),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.blue[600],
                                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                ),
                              )
                            ],
                          ),
                        ),
                      )
                    else ...[
                      // 1. Thẻ Thông tin nhân viên
                      _buildUserInfoCard(viewModel.shiftData!),
                      const SizedBox(height: 24),
                      
                      // 2. Thẻ Tổng quan (3 ô màu)
                      const Text('Tổng quan hôm nay', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(child: _buildSummaryBox('Tổng doanh thu', '${viewModel.shiftData!['thong_ke']['tong_doanh_thu']}đ', Colors.orange, Icons.attach_money)),
                          const SizedBox(width: 16),
                          Expanded(child: _buildSummaryBox('Tổng số đơn', '${viewModel.shiftData!['thong_ke']['tong_so_don']}', Colors.blue, Icons.shopping_bag_outlined)),
                          const SizedBox(width: 16),
                          Expanded(child: _buildSummaryBox('Trung bình', '${viewModel.shiftData!['thong_ke']['trung_binh_don']}đ', Colors.green, Icons.credit_card)),
                        ],
                      ),
                      const SizedBox(height: 24),
                      
                      // 3. Phương thức thanh toán
                      const Text('Phương thức thanh toán', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
                      const SizedBox(height: 16),
                      Container(
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
                        child: Column(
                          children: [
                            _buildPaymentRow('Tiền mặt', '${viewModel.shiftData!['thong_ke']['tien_mat']}đ', Colors.green),
                            const Divider(height: 32),
                            _buildPaymentRow('Chuyển khoản / Thẻ', '${viewModel.shiftData!['thong_ke']['chuyen_khoan']}đ', Colors.blue),
                          ],
                        ),
                      ),
                      
                      const Spacer(),
                      
                      // 4. Nút Kết ca (Đỏ)
                      SizedBox(
                        width: double.infinity,
                        height: 56,
                        child: ElevatedButton.icon(
                          onPressed: () => _showCloseShiftDialog(context, viewModel),
                          icon: const Icon(Icons.exit_to_app, color: Colors.white),
                          label: const Text('Kết Ca Làm Việc', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.red[600],
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                        ),
                      ),
                    ]
                  ],
                ),
              ),
      ),
    );
  }

 Widget _buildUserInfoCard(Map<String, dynamic> data) {
    DateTime startTime = DateTime.parse(data['thoi_gian_bat_dau']);
    String formattedStartTime = DateFormat('HH:mm').format(startTime);
    DateTime endTime = startTime.add(const Duration(hours: 8));
String formattedEndTime = DateFormat('HH:mm a').format(endTime);

    // --- LẤY THÔNG TIN NHÂN VIÊN THẬT TỪ BACKEND ---
    final nhanVienData = data['nhan_vien'] ?? {};
    String hoTen = nhanVienData['ho_ten'] ?? 'Nhân viên';
    String vaiTroRaw = nhanVienData['vai_tro'] ?? 'nhan_vien';
    
    // Viết hoa chữ cái đầu cho avatar (ví dụ: "John" -> "J")
    String chuCaiDau = hoTen.isNotEmpty ? hoTen[0].toUpperCase() : 'NV';
    
    // Dịch vai trò ra tiếng Việt cho đẹp
    String vaiTroHienThi = vaiTroRaw == 'quan_ly' ? 'Quản Lý Cửa Hàng' : 'Nhân Viên Thu Ngân';

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
      child: Column(
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 24, 
                backgroundColor: Colors.orange[100], 
                child: Text(chuCaiDau, style: const TextStyle(color: Colors.orange, fontWeight: FontWeight.bold, fontSize: 20))
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start, 
                  children: [
                    // --- HIỂN THỊ TÊN THẬT VÀ VAI TRÒ ---
                    Text(hoTen, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    Text('$vaiTroHienThi - Ca làm việc hiện tại', style: const TextStyle(color: Colors.grey)),
                  ]
                ),
              ),
            ],
          ),
          const Padding(padding: EdgeInsets.symmetric(vertical: 16), child: Divider()),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _buildTimeInfo(Icons.schedule, 'Giờ bắt đầu', formattedStartTime),
              _buildTimeInfo(Icons.access_time, 'Giờ kết thúc dự kiến', formattedEndTime),
            ],
          )
        ],
      ),
    );
  }

  Widget _buildTimeInfo(IconData icon, String label, String time) {
    return Row(
      children: [
        Icon(icon, color: Colors.grey, size: 20),
        const SizedBox(width: 8),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(color: Colors.grey, fontSize: 12)),
            Text(time, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          ],
        )
      ],
    );
  }

  Widget _buildSummaryBox(String title, String value, MaterialColor color, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color[50],
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color[100]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(8)),
            child: Icon(icon, color: Colors.white, size: 20),
          ),
          const SizedBox(height: 12),
          Text(title, style: TextStyle(color: color[700], fontSize: 11)),
          const SizedBox(height: 4),
          Text(value, style: TextStyle(color: color[900], fontSize: 15, fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }

  Widget _buildPaymentRow(String label, String amount, Color dotColor) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Row(
          children: [
            CircleAvatar(radius: 4, backgroundColor: dotColor),
            const SizedBox(width: 12),
            Text(label, style: const TextStyle(fontSize: 16)),
          ],
        ),
        Text(amount, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
      ],
    );
  }

  // Hàm hiển thị Popup Kết ca chuẩn thiết kế mới
  void _showCloseShiftDialog(BuildContext context, AuthViewModel viewModel) {
    // 1. Lấy dữ liệu từ ViewModel
    final data = viewModel.shiftData!;
    final thongKe = data['thong_ke'];
    final tongDoanhThu = thongKe['tong_doanh_thu'] ?? 0;
    final tongSoDon = thongKe['tong_so_don'] ?? 0;

    // 2. Xử lý logic cộng thêm 8 tiếng cho ca làm
    DateTime startTime = DateTime.parse(data['thoi_gian_bat_dau']);
    DateTime endTime = startTime.add(const Duration(hours: 8)); // Cộng thẳng 8 tiếng
    
    String formattedStart = DateFormat('HH:mm').format(startTime);
    String formattedEnd = DateFormat('HH:mm a').format(endTime); // Hiện thêm AM/PM cho giống ảnh
    String shiftDuration = '$formattedStart - $formattedEnd';

    // 3. Vẽ Giao diện Popup
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Xác nhận Kết Ca', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 22)),
        content: Column(
          mainAxisSize: MainAxisSize.min, // Tự động co giãn theo nội dung
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Bạn có chắc chắn muốn kết thúc ca làm việc? Thao tác này sẽ chốt sổ và kết thúc phiên làm hiện tại.',
              style: TextStyle(color: Colors.black87, fontSize: 16, height: 1.5),
            ),
            const SizedBox(height: 24),
            
            // Khối thông tin thống kê nền xám
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.grey[50], // Màu xám cực nhạt giống thiết kế
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                children: [
                  _buildDialogRow('Tổng doanh thu:', '${tongDoanhThu}đ'),
                  const SizedBox(height: 12),
                  _buildDialogRow('Tổng số đơn:', '$tongSoDon'),
                  const SizedBox(height: 12),
                  _buildDialogRow('Thời gian ca làm:', shiftDuration),
                ],
              ),
            ),
          ],
        ),
        actionsPadding: const EdgeInsets.fromLTRB(24, 0, 24, 24),
        actions: [
          // Row chứa 2 nút nằm ngang bằng nhau
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    side: const BorderSide(color: Colors.grey),
                  ),
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Hủy', style: TextStyle(color: Colors.blueGrey, fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFF03E3E), // Mã màu đỏ tươi giống hệt ảnh
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  onPressed: () async {
                    Navigator.pop(context); // Đóng popup
                    bool success = await viewModel.closeShift();
                    if (success && context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Kết ca thành công!')));
                    }
                  },
                  child: const Text('Kết Ca', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // Hàm hỗ trợ vẽ từng dòng trong thẻ xám cho gọn code
  Widget _buildDialogRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: Colors.blueGrey, fontSize: 15)),
        Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Colors.black87)),
      ],
    );
  }
  void _showOpenShiftDialog(BuildContext context, AuthViewModel viewModel) {
    final TextEditingController cashController = TextEditingController(text: "0"); // Mặc định là 0đ
    
    showDialog(
      context: context,
      barrierDismissible: false, // Không cho bấm ra ngoài để tắt
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Mở Ca Làm Việc', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Vui lòng kiểm tra két sắt và nhập số tiền lẻ có sẵn đầu ca:'),
            const SizedBox(height: 16),
            TextField(
              controller: cashController,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                labelText: 'Tiền mặt đầu ca',
                suffixText: 'VNĐ',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                prefixIcon: const Icon(Icons.account_balance_wallet, color: Colors.grey),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Hủy', style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.blue[600]),
            onPressed: () async {
              double startingCash = double.tryParse(cashController.text) ?? 0;
              Navigator.pop(context); // Tắt popup
              
              bool success = await viewModel.openShift(startingCash);
              if (success && context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Đã mở ca thành công! Chúc bạn một ngày buôn bán đắt khách! 🎉')));
              } else if (context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Lỗi khi mở ca. Vui lòng thử lại!'), backgroundColor: Colors.red));
              }
            },
            child: const Text('Xác nhận Mở Ca', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }
}