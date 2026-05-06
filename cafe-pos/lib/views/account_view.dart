import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'viewmodels/auth_viewmodel.dart';
import 'viewmodels/cart_viewmodel.dart';

class AccountView extends StatefulWidget {
  const AccountView({Key? key}) : super(key: key);
  @override
  State<AccountView> createState() => _AccountViewState();
}

class _AccountViewState extends State<AccountView> {
  final currencyFormat = NumberFormat('#,###', 'vi_VN');

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AuthViewModel>().fetchCurrentShift();
    });
  }

  String _formatCurrency(dynamic value) {
    final num v = (value is num) ? value : (num.tryParse(value.toString()) ?? 0);
    return '${currencyFormat.format(v)}đ';
  }

  String _getWorkDuration(String startTimeStr) {
    final start = DateTime.parse(startTimeStr);
    final duration = DateTime.now().difference(start);
    final h = duration.inHours;
    final m = duration.inMinutes % 60;
    if (h > 0) return '${h} giờ ${m} phút';
    return '${m} phút';
  }

  @override
  Widget build(BuildContext context) {
    final viewModel = context.watch<AuthViewModel>();
    return Scaffold(
      backgroundColor: const Color(0xFFFDFBF7),
      body: SafeArea(
        child: viewModel.isLoading
            ? const Center(child: CircularProgressIndicator(color: const Color(0xFF6E4423)))
            : Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // HEADER
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Tài Khoản & Ca Làm', style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: Colors.black87)),
                        Row(children: [
                          IconButton(icon: const Icon(Icons.refresh, color: Colors.blue), tooltip: 'Làm mới doanh thu', onPressed: () => viewModel.fetchCurrentShift()),
                          const SizedBox(width: 8),
                          IconButton(icon: const Icon(Icons.logout, color: Colors.red), tooltip: 'Đăng xuất tài khoản', onPressed: () => viewModel.logout()),
                        ]),
                      ],
                    ),
                    const SizedBox(height: 24),
                    if (!viewModel.isShiftActive)
                      Expanded(
                        child: Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Container(
                                padding: const EdgeInsets.all(28),
                                decoration: BoxDecoration(color: Colors.blue[50], shape: BoxShape.circle),
                                child: Icon(Icons.storefront_outlined, size: 70, color: Colors.blue[300]),
                              ),
                              const SizedBox(height: 24),
                              const Text('Chưa mở ca làm việc', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Colors.black54)),
                              const SizedBox(height: 8),
                              Text('Bắt đầu ca làm để bán hàng và theo dõi doanh thu.', style: TextStyle(fontSize: 14, color: Colors.grey[500])),
                              const SizedBox(height: 32),
                              ElevatedButton.icon(
                                onPressed: () => _showOpenShiftDialog(context, viewModel),
                                icon: const Icon(Icons.play_circle_fill, color: Colors.white),
                                label: const Text('Bắt Đầu Ca Làm Mới', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white)),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: Colors.blue[600],
                                  padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                                  elevation: 2,
                                ),
                              )
                            ],
                          ),
                        ),
                      )
                    else ...[
                      // Phần nội dung cuộn được
                      Expanded(
                        child: SingleChildScrollView(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _buildUserInfoCard(viewModel.shiftData!),
                              const SizedBox(height: 24),
                              const Text('Tổng quan ca làm', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
                              const SizedBox(height: 16),
                              Row(children: [
                                Expanded(child: _buildSummaryBox('Tổng doanh thu', _formatCurrency(viewModel.shiftData!['thong_ke']['tong_doanh_thu']), const Color(0xFF6E4423), Icons.attach_money)),
                                const SizedBox(width: 16),
                                Expanded(child: _buildSummaryBox('Tổng số đơn', '${viewModel.shiftData!['thong_ke']['tong_so_don']}', Colors.blue, Icons.shopping_bag_outlined)),
                                const SizedBox(width: 16),
                                Expanded(child: _buildSummaryBox('Trung bình/đơn', _formatCurrency(viewModel.shiftData!['thong_ke']['trung_binh_don']), Colors.green, Icons.credit_card)),
                              ]),
                              const SizedBox(height: 24),
                              const Text('Phương thức thanh toán', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
                              const SizedBox(height: 16),
                              Container(
                                padding: const EdgeInsets.all(20),
                                decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
                                child: Column(children: [
                                  _buildPaymentRow('Tiền mặt bán được', _formatCurrency(viewModel.shiftData!['thong_ke']['tien_mat']), Colors.green),
                                  const Divider(height: 32),
                                  _buildPaymentRow('Chuyển khoản / Thẻ', _formatCurrency(viewModel.shiftData!['thong_ke']['chuyen_khoan']), Colors.blue),
                                  if (viewModel.shiftData!['tien_mat_dau_ca'] != null) ...[
                                    const Divider(height: 32),
                                    _buildPaymentRow('Tiền mặt đầu ca', _formatCurrency(viewModel.shiftData!['tien_mat_dau_ca']), const Color(0xFF6E4423)),
                                  ],
                                  if (viewModel.shiftData!['tien_mat_he_thong'] != null) ...[
                                    const Divider(height: 32),
                                    _buildPaymentRow('Tiền mặt trong két (HT)', _formatCurrency(viewModel.shiftData!['tien_mat_he_thong']), Colors.deepPurple),
                                  ],
                                ]),
                              ),
                              const SizedBox(height: 16),
                            ],
                          ),
                        ),
                      ),
                      // Phần cố định ở dưới cùng
                      // Cảnh báo đơn đang xử lý
                      if ((viewModel.shiftData!['thong_ke']['don_dang_xu_ly'] ?? 0) > 0)
                        Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(color: Colors.amber[50], borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.amber[300]!)),
                          child: Row(children: [
                            const Icon(Icons.warning_amber_rounded, color: const Color(0xFF6E4423)),
                            const SizedBox(width: 12),
                            Expanded(child: Text('Còn ${viewModel.shiftData!['thong_ke']['don_dang_xu_ly']} đơn đang xử lý. Hoàn thành trước khi kết ca.', style: const TextStyle(color: Colors.black87, fontSize: 13))),
                          ]),
                        ),
                      SizedBox(
                        width: double.infinity, height: 56,
                        child: ElevatedButton.icon(
                          onPressed: () => _showCloseShiftDialog(context, viewModel),
                          icon: const Icon(Icons.exit_to_app, color: Colors.white),
                          label: const Text('Kết Ca Làm Việc', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white)),
                          style: ElevatedButton.styleFrom(backgroundColor: Colors.red[600], shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
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
    String workDuration = _getWorkDuration(data['thoi_gian_bat_dau']);

    final nhanVienData = data['nhan_vien'] ?? {};
    String hoTen = nhanVienData['ho_ten'] ?? 'Nhân viên';
    String vaiTroRaw = nhanVienData['vai_tro'] ?? 'nhan_vien';
    String chuCaiDau = hoTen.isNotEmpty ? hoTen[0].toUpperCase() : 'NV';
    String vaiTroHienThi = vaiTroRaw == 'quan_ly' ? 'Quản Lý Cửa Hàng' : 'Nhân Viên Thu Ngân';

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)),
      child: Column(children: [
        Row(children: [
          CircleAvatar(radius: 24, backgroundColor: const Color(0xFFEFE6DD), child: Text(chuCaiDau, style: const TextStyle(color: const Color(0xFF6E4423), fontWeight: FontWeight.bold, fontSize: 20))),
          const SizedBox(width: 16),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(hoTen, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            Text('$vaiTroHienThi - Ca làm việc hiện tại', style: const TextStyle(color: Colors.grey)),
          ])),
        ]),
        const Padding(padding: EdgeInsets.symmetric(vertical: 16), child: Divider()),
        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          _buildTimeInfo(Icons.schedule, 'Giờ bắt đầu', formattedStartTime),
          _buildTimeInfo(Icons.timer_outlined, 'Đã làm được', workDuration),
        ]),
      ]),
    );
  }

  Widget _buildTimeInfo(IconData icon, String label, String time) {
    return Row(children: [
      Icon(icon, color: Colors.grey, size: 20),
      const SizedBox(width: 8),
      Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(label, style: const TextStyle(color: Colors.grey, fontSize: 12)),
        Text(time, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
      ]),
    ]);
  }

  Widget _buildSummaryBox(String title, String value, Color color, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1), 
        borderRadius: BorderRadius.circular(12), 
        border: Border.all(color: color.withOpacity(0.2))
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Container(
          padding: const EdgeInsets.all(6), 
          decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(8)), 
          child: Icon(icon, color: Colors.white, size: 20)
        ),
        const SizedBox(height: 12),
        Text(title, style: TextStyle(color: color, fontSize: 11)),
        const SizedBox(height: 4),
        Text(value, style: TextStyle(color: color, fontSize: 15, fontWeight: FontWeight.bold)),
      ]),
    );
  }

  Widget _buildPaymentRow(String label, String amount, Color dotColor) {
    return Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
      Row(children: [
        CircleAvatar(radius: 4, backgroundColor: dotColor),
        const SizedBox(width: 12),
        Text(label, style: const TextStyle(fontSize: 15)),
      ]),
      Text(amount, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
    ]);
  }

  // ===== DIALOG MỞ CA (Nâng cấp) =====
  void _showOpenShiftDialog(BuildContext context, AuthViewModel viewModel) {
    final cashController = TextEditingController(text: '0');
    final now = DateFormat('dd/MM/yyyy - HH:mm').format(DateTime.now());

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        titlePadding: const EdgeInsets.fromLTRB(24, 24, 24, 0),
        title: Row(children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(color: Colors.blue[50], borderRadius: BorderRadius.circular(12)),
            child: Icon(Icons.wb_sunny_rounded, color: Colors.blue[600], size: 28),
          ),
          const SizedBox(width: 16),
          const Text('Mở Ca Làm Việc', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20)),
        ]),
        content: SingleChildScrollView(
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
            const SizedBox(height: 16),
            // Thông tin nhân viên + thời gian
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: Colors.grey[50], borderRadius: BorderRadius.circular(12)),
              child: Column(children: [
                Row(children: [
                  Icon(Icons.calendar_today, size: 16, color: Colors.grey[600]),
                  const SizedBox(width: 8),
                  Text(now, style: TextStyle(color: Colors.grey[700], fontWeight: FontWeight.w500)),
                ]),
              ]),
            ),
            const SizedBox(height: 20),
            const Text('Nhập số tiền mặt có sẵn trong két:', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w500)),
            const SizedBox(height: 12),
            TextField(
              controller: cashController,
              keyboardType: TextInputType.number,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              decoration: InputDecoration(
                labelText: 'Tiền mặt đầu ca',
                suffixText: 'VNĐ',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                prefixIcon: const Icon(Icons.account_balance_wallet, color: Colors.blue),
                filled: true,
                fillColor: Colors.white,
              ),
            ),
            const SizedBox(height: 12),
            // Gợi ý nhanh
            const Text('Gợi ý nhanh:', style: TextStyle(fontSize: 12, color: Colors.grey)),
            const SizedBox(height: 8),
            Row(children: [
              _quickCashChip('200,000', 200000, cashController),
              const SizedBox(width: 8),
              _quickCashChip('500,000', 500000, cashController),
              const SizedBox(width: 8),
              _quickCashChip('1,000,000', 1000000, cashController),
            ]),
          ]),
        ),
        actionsPadding: const EdgeInsets.fromLTRB(24, 8, 24, 24),
        actions: [
          Row(children: [
            Expanded(
              child: OutlinedButton(
                onPressed: () => Navigator.pop(ctx),
                style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)), side: const BorderSide(color: Colors.grey)),
                child: const Text('Hủy', style: TextStyle(color: Colors.blueGrey, fontSize: 15, fontWeight: FontWeight.bold)),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: ElevatedButton.icon(
                icon: const Icon(Icons.play_circle_fill, color: Colors.white, size: 20),
                label: const Text('Bắt Đầu Ca', style: TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.bold)),
                style: ElevatedButton.styleFrom(backgroundColor: Colors.blue[600], padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
                onPressed: () async {
                  double startingCash = double.tryParse(cashController.text) ?? 0;
                  Navigator.pop(ctx);
                  bool success = await viewModel.openShift(startingCash);
                  if (success && context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Đã mở ca thành công! Chúc bạn buôn bán đắt khách! 🎉'), backgroundColor: Colors.green));
                  } else if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Lỗi khi mở ca. Vui lòng thử lại!'), backgroundColor: Colors.red));
                  }
                },
              ),
            ),
          ]),
        ],
      ),
    );
  }

  Widget _quickCashChip(String label, double value, TextEditingController controller) {
    return Expanded(
      child: OutlinedButton(
        onPressed: () => controller.text = value.toInt().toString(),
        style: OutlinedButton.styleFrom(
          padding: const EdgeInsets.symmetric(vertical: 8),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          side: BorderSide(color: Colors.blue[200]!),
        ),
        child: Text(label, style: TextStyle(fontSize: 11, color: Colors.blue[700], fontWeight: FontWeight.w600)),
      ),
    );
  }

  // ===== DIALOG KẾT CA (Nâng cấp lớn) =====
  void _showCloseShiftDialog(BuildContext context, AuthViewModel viewModel) {
    final data = viewModel.shiftData!;
    final thongKe = data['thong_ke'];
    final tienMatHeThong = (data['tien_mat_he_thong'] as num?)?.toDouble() ?? 0;

    DateTime startTime = DateTime.parse(data['thoi_gian_bat_dau']);
    String formattedStart = DateFormat('HH:mm').format(startTime);
    String formattedNow = DateFormat('HH:mm').format(DateTime.now());
    String shiftDuration = '$formattedStart → $formattedNow (${_getWorkDuration(data['thoi_gian_bat_dau'])})';

    final cashController = TextEditingController();
    final noteController = TextEditingController();

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) {
        return StatefulBuilder(builder: (ctx, setDialogState) {
          double tienThucTe = double.tryParse(cashController.text) ?? 0;
          double chenhLech = tienThucTe - tienMatHeThong;
          bool isMatched = chenhLech == 0 && cashController.text.isNotEmpty;
          bool hasInput = cashController.text.isNotEmpty;

          return AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            titlePadding: const EdgeInsets.fromLTRB(24, 24, 24, 0),
            title: Row(children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(color: Colors.red[50], borderRadius: BorderRadius.circular(12)),
                child: Icon(Icons.assignment_turned_in, color: Colors.red[600], size: 28),
              ),
              const SizedBox(width: 16),
              const Text('Xác Nhận Kết Ca', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20)),
            ]),
            content: SizedBox(
              width: 420,
              child: SingleChildScrollView(
                child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
                  const SizedBox(height: 12),
                  const Text('Kiểm tra thông tin và đối soát két tiền trước khi kết ca.', style: TextStyle(color: Colors.black54, fontSize: 14)),
                  const SizedBox(height: 20),
                  // Khối thống kê
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(color: Colors.grey[50], borderRadius: BorderRadius.circular(12)),
                    child: Column(children: [
                      _buildDialogRow('Thời gian ca:', shiftDuration),
                      const SizedBox(height: 10),
                      _buildDialogRow('Tổng doanh thu:', _formatCurrency(thongKe['tong_doanh_thu'])),
                      const SizedBox(height: 10),
                      _buildDialogRow('Tổng số đơn:', '${thongKe['tong_so_don']}'),
                      const SizedBox(height: 10),
                      _buildDialogRow('Tiền mặt (bán):', _formatCurrency(thongKe['tien_mat'])),
                      const SizedBox(height: 10),
                      _buildDialogRow('Chuyển khoản:', _formatCurrency(thongKe['chuyen_khoan'])),
                    ]),
                  ),
                  const SizedBox(height: 20),
                  // Đối soát két tiền
                  const Text('Đối soát két tiền', style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 4),
                  Text('Tiền mặt hệ thống trong két: ${_formatCurrency(tienMatHeThong)}', style: TextStyle(fontSize: 13, color: Colors.grey[600])),
                  const SizedBox(height: 12),
                  TextField(
                    controller: cashController,
                    keyboardType: TextInputType.number,
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    onChanged: (_) => setDialogState(() {}),
                    decoration: InputDecoration(
                      labelText: 'Tiền mặt thực tế trong két',
                      suffixText: 'VNĐ',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      prefixIcon: const Icon(Icons.account_balance_wallet, color: Colors.deepPurple),
                      filled: true, fillColor: Colors.white,
                    ),
                  ),
                  if (hasInput) ...[
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: isMatched ? Colors.green[50] : Colors.red[50],
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: isMatched ? Colors.green[300]! : Colors.red[300]!),
                      ),
                      child: Row(children: [
                        Icon(isMatched ? Icons.check_circle : Icons.warning_amber_rounded, color: isMatched ? Colors.green : Colors.red, size: 20),
                        const SizedBox(width: 8),
                        Expanded(child: Text(
                          isMatched
                              ? 'Tiền mặt khớp với hệ thống! ✓'
                              : 'Chênh lệch: ${chenhLech > 0 ? "+" : ""}${_formatCurrency(chenhLech)}',
                          style: TextStyle(color: isMatched ? Colors.green[700] : Colors.red[700], fontWeight: FontWeight.w600, fontSize: 13),
                        )),
                      ]),
                    ),
                  ],
                  const SizedBox(height: 16),
                  TextField(
                    controller: noteController,
                    maxLines: 2,
                    decoration: InputDecoration(
                      labelText: 'Ghi chú ca làm (tuỳ chọn)',
                      hintText: 'Ví dụ: Thiếu ly, đổ nước, khách đông...',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      prefixIcon: const Icon(Icons.notes, color: Colors.grey),
                      filled: true, fillColor: Colors.white,
                    ),
                  ),
                ]),
              ),
            ),
            actionsPadding: const EdgeInsets.fromLTRB(24, 8, 24, 24),
            actions: [
              Row(children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.pop(ctx),
                    style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)), side: const BorderSide(color: Colors.grey)),
                    child: const Text('Hủy', style: TextStyle(color: Colors.blueGrey, fontSize: 15, fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: ElevatedButton.icon(
                    icon: const Icon(Icons.exit_to_app, color: Colors.white, size: 20),
                    label: const Text('Kết Ca', style: TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.bold)),
                    style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFF03E3E), padding: const EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
                    onPressed: () async {
                      if (cashController.text.isEmpty) {
                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Vui lòng nhập tiền mặt thực tế!'), backgroundColor: const Color(0xFF6E4423)));
                        return;
                      }
                      Navigator.pop(ctx);
                      final result = await viewModel.closeShift(
                        tienMatThucTe: double.tryParse(cashController.text) ?? 0,
                        ghiChu: noteController.text.isNotEmpty ? noteController.text : null,
                      );
                      if (context.mounted) {
                        if (result['success'] == true) {
                          // Clear giỏ hàng khi kết ca thành công
                          context.read<CartViewModel>().clearCart();
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(result['message']), backgroundColor: Colors.green));
                        } else {
                          ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(result['message'] ?? 'Lỗi kết ca'), backgroundColor: Colors.red, duration: const Duration(seconds: 4)));
                        }
                      }
                    },
                  ),
                ),
              ]),
            ],
          );
        });
      },
    );
  }

  Widget _buildDialogRow(String label, String value) {
    return Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
      Text(label, style: const TextStyle(color: Colors.blueGrey, fontSize: 14)),
      Flexible(child: Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.black87), textAlign: TextAlign.right)),
    ]);
  }
}
