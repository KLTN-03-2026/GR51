import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../viewmodels/cart_viewmodel.dart';

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
  String _phuongThuc = 'Tiền mặt';
  String _trangThaiThanhToan = 'đã_thanh_toan';

  @override
  Widget build(BuildContext context) {
    final currencyFormat = NumberFormat.currency(locale: 'vi_VN', symbol: 'đ');
    final cart = context.watch<CartViewModel>();

    return Container(
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        top: 24,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(24),
          topRight: Radius.circular(24),
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Thanh toán đơn hàng',
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
              ),
              IconButton(
                icon: const Icon(Icons.close),
                onPressed: () {
                  if (!cart.isSubmitting) Navigator.pop(context);
                },
              ),
            ],
          ),
          const SizedBox(height: 16),
          Center(
            child: Text(
              currencyFormat.format(widget.totalPrice),
              style: const TextStyle(
                fontSize: 32,
                fontWeight: FontWeight.bold,
                color: Colors.orange,
              ),
            ),
          ),
          const SizedBox(height: 24),
          const Text('Phương thức thanh toán', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey)),
          const SizedBox(height: 8),
          Row(
            children: [
              _buildRadioOption('Tiền mặt', _phuongThuc, (val) => setState(() => _phuongThuc = val)),
              const SizedBox(width: 16),
              _buildRadioOption('Chuyển khoản', _phuongThuc, (val) => setState(() => _phuongThuc = val)),
            ],
          ),
          const SizedBox(height: 16),
          const Text('Trạng thái thanh toán', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey)),
          const SizedBox(height: 8),
          Row(
            children: [
              _buildRadioOption('Đã thanh toán', _trangThaiThanhToan, (val) => setState(() => _trangThaiThanhToan = val), value: 'đã_thanh_toan'),
              const SizedBox(width: 16),
              _buildRadioOption('Chưa thanh toán', _trangThaiThanhToan, (val) => setState(() => _trangThaiThanhToan = val), value: 'chưa_thanh_toan'),
            ],
          ),
          const SizedBox(height: 32),
          ElevatedButton(
            onPressed: cart.isSubmitting
                ? null
                : () async {
                    String trangThaiDon = 'dang_pha'; // Gán mặc định đang pha

                    final success = await cart.submitOrder(
                      loaiDon: widget.loaiDon,
                      phuongThucThanhToan: _phuongThuc,
                      maBan: widget.maBan,
                      trangThaiThanhToan: _trangThaiThanhToan,
                      trangThaiDon: trangThaiDon,
                    );
                    
                    if (context.mounted) {
                      Navigator.pop(context, success);
                    }
                  },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.orange,
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: cart.isSubmitting
                ? const SizedBox(
                    height: 24,
                    width: 24,
                    child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                  )
                : const Text('XÁC NHẬN TẠO ĐƠN', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  Widget _buildRadioOption(String title, String groupValue, Function(String) onChanged, {String? value}) {
    final actualValue = value ?? title;
    final isSelected = groupValue == actualValue;

    return Expanded(
      child: GestureDetector(
        onTap: () => onChanged(actualValue),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 12),
          decoration: BoxDecoration(
            color: isSelected ? Colors.orange[50] : Colors.white,
            border: Border.all(color: isSelected ? Colors.orange : Colors.grey[300]!),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Center(
            child: Text(
              title,
              style: TextStyle(
                color: isSelected ? Colors.orange[800] : Colors.grey[700],
                fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
              ),
            ),
          ),
        ),
      ),
    );
  }
}
