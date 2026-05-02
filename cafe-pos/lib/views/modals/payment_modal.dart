import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../viewmodels/cart_viewmodel.dart';
import '../../models/cart_item_model.dart';

class PaymentModal extends StatefulWidget {
  final String loaiDon;
  final String? maBan;
  final double totalPrice;

  const PaymentModal({
    Key? key,
    required this.loaiDon,
    this.maBan,
    required this.totalPrice,
  }) : super(key: key);

  @override
  State<PaymentModal> createState() => _PaymentModalState();
}

class _PaymentModalState extends State<PaymentModal> {
  String _phuongThuc = 'Tiền mặt'; // Giới hạn chỉ có Tiền mặt và Chuyển khoản
  final TextEditingController _tienKhachDuaController = TextEditingController();
  final _currencyFormat = NumberFormat.currency(locale: 'vi_VN', symbol: 'đ');

  @override
  void initState() {
    super.initState();
    // Khởi tạo mặc định Khách đưa đủ
    _tienKhachDuaController.text = widget.totalPrice.toInt().toString();
  }

  @override
  void dispose() {
    _tienKhachDuaController.dispose();
    super.dispose();
  }

  double get _tienKhachDua {
    String text = _tienKhachDuaController.text.replaceAll(RegExp(r'[^0-9]'), '');
    return double.tryParse(text) ?? 0;
  }

  double get _tienThua {
    double thoi = _tienKhachDua - widget.totalPrice;
    return thoi > 0 ? thoi : 0;
  }

  void _onAmountSuggest(double suggest) {
    setState(() {
      _tienKhachDuaController.text = suggest.toInt().toString();
    });
  }

  @override
  Widget build(BuildContext context) {
    final cart = context.watch<CartViewModel>();

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        width: 900,
        height: 600,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          children: [
            // Header
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      const Text('Thanh toán đơn hàng', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                      const SizedBox(width: 12),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.orange[100],
                          borderRadius: BorderRadius.circular(16),
                        ),
                        child: Text(widget.loaiDon == 'mang_di' ? 'Mang đi' : 'Tại bàn', style: TextStyle(color: Colors.orange[800], fontWeight: FontWeight.bold)),
                      )
                    ],
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.grey),
                    onPressed: () {
                      if (!cart.isSubmitting) Navigator.pop(context);
                    },
                  )
                ],
              ),
            ),
            const Divider(height: 1),

            // Body
            Expanded(
              child: Row(
                children: [
                  // CỘT TRÁI - Tóm tắt đơn hàng
                  Expanded(
                    flex: 4,
                    child: Container(
                      decoration: BoxDecoration(
                        border: Border(right: BorderSide(color: Colors.grey[200]!)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(16),
                            child: Row(
                              children: [
                                const Icon(Icons.receipt_long, color: Colors.grey),
                                const SizedBox(width: 8),
                                Text('Tóm tắt món (${cart.cartItems.length})', style: const TextStyle(fontWeight: FontWeight.bold)),
                              ],
                            ),
                          ),
                          const Divider(height: 1, thickness: 1),
                          Expanded(
                            child: ListView.separated(
                              padding: const EdgeInsets.all(16),
                              itemCount: cart.cartItems.length,
                              separatorBuilder: (context, index) => const Padding(
                                padding: EdgeInsets.symmetric(vertical: 8.0),
                                child: Divider(height: 1),
                              ),
                              itemBuilder: (context, index) {
                                final item = cart.cartItems[index];
                                return Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(color: Colors.grey[100], borderRadius: BorderRadius.circular(4)),
                                      child: Text('${item.soLuong}'),
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(item.mon.tenMon, style: const TextStyle(fontWeight: FontWeight.bold)),
                                          if (item.selectedSize != null) 
                                            Text('Size ${item.selectedSize!['ten_kich_co']}', style: const TextStyle(color: Colors.grey, fontSize: 13)),
                                          ...item.selectedToppings.map((t) => Text('+ ${t['ten_topping']}', style: const TextStyle(color: Colors.grey, fontSize: 13))),
                                          if (item.ghiChu != null && item.ghiChu!.isNotEmpty)
                                            Text(item.ghiChu!, style: const TextStyle(color: Colors.blueGrey, fontStyle: FontStyle.italic, fontSize: 13)),
                                        ],
                                      ),
                                    ),
                                    Text(_currencyFormat.format(item.thanhTien), style: const TextStyle(fontWeight: FontWeight.bold)),
                                  ],
                                );
                              },
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.all(16),
                            color: Colors.white,
                            child: Column(
                              children: [
                                _buildSummaryRow('Tổng tiền hàng', _currencyFormat.format(widget.totalPrice)),
                                const SizedBox(height: 8),
                                _buildSummaryRow('Giảm giá', '0 đ'),
                                const SizedBox(height: 12),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    const Text('Khách cần trả', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.orange)),
                                    Text(
                                      _currencyFormat.format(widget.totalPrice),
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 20, color: Colors.orange),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          )
                        ],
                      ),
                    ),
                  ),

                  // CỘT PHẢI - Chọn phương thức và tính tiền
                  Expanded(
                    flex: 5,
                    child: Container(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Phương thức thanh toán', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blueGrey)),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              _buildPaymentMethodOption('Tiền mặt', Icons.money),
                              const SizedBox(width: 16),
                              _buildPaymentMethodOption('Chuyển khoản', Icons.qr_code),
                            ],
                          ),
                          const SizedBox(height: 24),

                          Expanded(
                            child: SingleChildScrollView(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  if (_phuongThuc == 'Tiền mặt') ...[
                                    const Text('Tiền khách đưa', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blueGrey)),
                                    const SizedBox(height: 12),
                                    TextField(
                                      controller: _tienKhachDuaController,
                                      keyboardType: TextInputType.number,
                                      onChanged: (v) => setState(() {}),
                                      style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                                      textAlign: TextAlign.right,
                                      decoration: InputDecoration(
                                        suffixText: 'đ',
                                        suffixStyle: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.grey),
                                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
                                      ),
                                    ),
                                    const SizedBox(height: 12),
                                    Row(
                                      children: [
                                        _buildSuggestAmountChip('Khách đưa đủ', widget.totalPrice),
                                        const SizedBox(width: 8),
                                        _buildSuggestAmountChip('50.000', 50000),
                                        const SizedBox(width: 8),
                                        _buildSuggestAmountChip('100.000', 100000),
                                        const SizedBox(width: 8),
                                        _buildSuggestAmountChip('200.000', 200000),
                                        const SizedBox(width: 8),
                                        _buildSuggestAmountChip('500.000', 500000),
                                      ],
                                    ),
                                    const SizedBox(height: 32),
                                    Container(
                                      padding: const EdgeInsets.all(16),
                                      decoration: BoxDecoration(
                                        color: Colors.grey[50],
                                        borderRadius: BorderRadius.circular(8),
                                        border: Border.all(color: Colors.grey[200]!)
                                      ),
                                      child: Row(
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          const Text('Tiền thừa trả khách:', style: TextStyle(fontSize: 16, color: Colors.blueGrey)),
                                          Text(
                                            _currencyFormat.format(_tienThua),
                                            style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.green),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ] else if (_phuongThuc == 'Chuyển khoản') ...[
                                    Center(
                                      child: Container(
                                        padding: const EdgeInsets.all(24),
                                        decoration: BoxDecoration(
                                          color: Colors.white,
                                          borderRadius: BorderRadius.circular(16),
                                          border: Border.all(color: Colors.blue[200]!, width: 2),
                                          boxShadow: [
                                            BoxShadow(
                                              color: Colors.blue.withOpacity(0.05),
                                              blurRadius: 15,
                                              offset: const Offset(0, 5),
                                            )
                                          ]
                                        ),
                                        child: Column(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            const Icon(Icons.qr_code_2, size: 140, color: Colors.black87),
                                            const SizedBox(height: 16),
                                            const Text('Vui lòng quét mã QR để thanh toán', style: TextStyle(color: Colors.blueGrey, fontSize: 13)),
                                            const SizedBox(height: 8),
                                            Text(
                                              _currencyFormat.format(widget.totalPrice),
                                              style: TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: Colors.blue[700]),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ),
                          const Divider(height: 1),
                          const SizedBox(height: 16),
                          Row(
                            children: [
                              Expanded(
                                flex: 2,
                                child: OutlinedButton(
                                  onPressed: () {},
                                  style: OutlinedButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(vertical: 20),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                    side: BorderSide(color: Colors.grey[300]!)
                                  ),
                                  child: const Text('In tạm tính', style: TextStyle(color: Colors.black87, fontSize: 16)),
                                ),
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                flex: 3,
                                child: ElevatedButton.icon(
                                  onPressed: cart.isSubmitting 
                                    ? null 
                                    : () async {
                                        final success = await cart.submitOrder(
                                          loaiDon: widget.loaiDon,
                                          phuongThucThanhToan: _phuongThuc == 'Tiền mặt' ? 'tien_mat' : 'chuyen_khoan',
                                          maBan: widget.maBan,
                                          trangThaiThanhToan: 'da_thanh_toan',
                                          trangThaiDon: 'dang_pha',
                                        );
                                        if (context.mounted) {
                                          Navigator.pop(context, success);
                                        }
                                      },
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFF00C853), // Xanh lá giống thiết kế
                                    padding: const EdgeInsets.symmetric(vertical: 20),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                  ),
                                  icon: cart.isSubmitting 
                                      ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                                      : const Icon(Icons.check_circle_outline, color: Colors.white),
                                  label: const Text('Xác nhận thanh toán', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                                ),
                              )
                            ],
                          )
                        ],
                      ),
                    ),
                  )
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: Colors.blueGrey)),
        Text(value, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.blueGrey)),
      ],
    );
  }

  Widget _buildSuggestAmountChip(String label, double amount) {
    return Expanded(
      child: GestureDetector(
        onTap: () => _onAmountSuggest(amount),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: Colors.grey[100],
            borderRadius: BorderRadius.circular(8),
          ),
          child: Center(
            child: Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500)),
          ),
        ),
      ),
    );
  }

  Widget _buildPaymentMethodOption(String title, IconData icon) {
    bool isSelected = _phuongThuc == title;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _phuongThuc = title),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 16),
          decoration: BoxDecoration(
            color: isSelected ? Colors.orange[50] : Colors.white,
            border: Border.all(color: isSelected ? Colors.orange : Colors.grey[300]!, width: isSelected ? 2 : 1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Column(
            children: [
              Icon(icon, color: isSelected ? Colors.orange : Colors.grey[600], size: 32),
              const SizedBox(height: 8),
              Text(
                title,
                style: TextStyle(
                  color: isSelected ? Colors.orange[800] : Colors.grey[800],
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
