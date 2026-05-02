import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'viewmodels/order_viewmodel.dart';
import '../models/order_model.dart';

class OrderHistoryView extends StatefulWidget {
  const OrderHistoryView({super.key});

  @override
  State<OrderHistoryView> createState() => _OrderHistoryViewState();
}

class _OrderHistoryViewState extends State<OrderHistoryView> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<OrderViewModel>().loadOrders();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    var completedOrders = context.watch<OrderViewModel>().completedOrders;
    final currencyFormat = NumberFormat.currency(locale: 'vi_VN', symbol: 'đ');

    if (_searchQuery.isNotEmpty) {
      completedOrders = completedOrders.where((order) {
        return order.maDonHang.toLowerCase().contains(_searchQuery.toLowerCase());
      }).toList();
    }

    return Scaffold(
      backgroundColor: const Color(0xFFFDFBF7),
      body: Padding(
        padding: const EdgeInsets.all(32.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Lịch sử đơn hàng',
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),
            const SizedBox(height: 24),
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  )
                ],
              ),
              child: TextField(
                controller: _searchController,
                onChanged: (value) {
                  setState(() {
                    _searchQuery = value;
                  });
                },
                decoration: InputDecoration(
                  hintText: 'Tìm kiếm theo mã đơn...',
                  hintStyle: TextStyle(color: Colors.grey[400]),
                  prefixIcon: const Icon(Icons.search, color: Colors.grey),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide.none,
                  ),
                  filled: true,
                  fillColor: Colors.white,
                  contentPadding: const EdgeInsets.symmetric(vertical: 16),
                ),
              ),
            ),
            const SizedBox(height: 32),
            
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
              decoration: BoxDecoration(
                color: Colors.grey[200],
                borderRadius: const BorderRadius.only(topLeft: Radius.circular(12), topRight: Radius.circular(12)),
              ),
              child: Row(
                children: [
                  Expanded(flex: 2, child: _buildHeaderCell('MÃ ĐƠN')),
                  Expanded(flex: 2, child: _buildHeaderCell('THỜI GIAN')),
                  Expanded(flex: 2, child: _buildHeaderCell('LOẠI')),
                  Expanded(flex: 1, child: _buildHeaderCell('SỐ LƯỢNG')),
                  Expanded(flex: 2, child: _buildHeaderCell('TỔNG TIỀN')),
                  Expanded(flex: 1, child: _buildHeaderCell('THAO TÁC', alignRight: true)),
                ],
              ),
            ),
            
            Expanded(
              child: context.watch<OrderViewModel>().isLoading && completedOrders.isEmpty
                ? const Center(child: CircularProgressIndicator(color: Colors.orange))
                : completedOrders.isEmpty
                  ? Center(
                      child: Text(
                        _searchQuery.isEmpty ? 'Không có đơn hàng nào trong lịch sử hôm nay.' : 'Không tìm thấy đơn hàng phù hợp.', 
                        style: TextStyle(color: Colors.grey[500], fontSize: 16)
                      )
                    )
                  : Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: const BorderRadius.only(bottomLeft: Radius.circular(12), bottomRight: Radius.circular(12)),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.02),
                            blurRadius: 5,
                            offset: const Offset(0, 2),
                          )
                        ],
                      ),
                      child: ListView.separated(
                        itemCount: completedOrders.length,
                        separatorBuilder: (context, index) => Divider(height: 1, thickness: 1, color: Colors.grey[100]),
                        itemBuilder: (context, index) {
                          final order = completedOrders[index];
                          return _buildOrderRow(context, order, currencyFormat);
                        },
                      ),
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeaderCell(String title, {bool alignRight = false}) {
    return Text(
      title,
      style: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.bold,
        color: Colors.grey[600],
        letterSpacing: 1.2,
      ),
      textAlign: alignRight ? TextAlign.right : TextAlign.left,
    );
  }

  void _showOrderDetails(BuildContext context, DonHang order, NumberFormat format) {
    String durationStr = '--';
    if (order.createdAt != null && order.updatedAt != null) {
      try {
        final start = DateTime.parse(order.createdAt!).toLocal();
        final end = DateTime.parse(order.updatedAt!).toLocal();
        final diff = end.difference(start);
        if (diff.inMinutes > 0) {
          durationStr = '${diff.inMinutes} phút';
        } else {
          durationStr = '${diff.inSeconds} giây';
        }
      } catch (_) {}
    }

    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('Chi tiết Đơn hàng: #${order.maDonHang.substring(0, order.maDonHang.length > 8 ? 8 : order.maDonHang.length).toUpperCase()}', style: const TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(color: Colors.blue.withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
                    child: Text(order.loaiDon == 'tai_ban' ? 'Bàn ${order.ban?.tenBan ?? order.maBan ?? "-"}' : 'Mang đi', style: const TextStyle(color: Colors.blue, fontSize: 13, fontWeight: FontWeight.bold)),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(color: Colors.green.withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
                    child: Text('Thời lượng: $durationStr', style: const TextStyle(color: Colors.green, fontSize: 13, fontWeight: FontWeight.bold)),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              const Divider(),
            ],
          ),
          content: SizedBox(
            width: double.maxFinite,
            height: 300,
            child: ListView.builder(
              shrinkWrap: true,
              itemCount: order.chiTietDonHangs.length,
              itemBuilder: (context, i) {
                final item = order.chiTietDonHangs[i];
                return Padding(
                  padding: const EdgeInsets.only(bottom: 12.0),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.orange.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text('${item.soLuong}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Colors.orange)),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(item.mon?.tenMon ?? 'Món xoá/Ẩn', style: const TextStyle(fontWeight: FontWeight.bold)),
                            if (item.ghiChu != null && item.ghiChu!.trim().isNotEmpty)
                              Text('Ghi chú: ${item.ghiChu}', style: TextStyle(fontSize: 12, color: Colors.grey[600], fontStyle: FontStyle.italic)),
                          ],
                        ),
                      ),
                      Text(format.format(item.donGia * item.soLuong), style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.grey)),
                    ],
                  ),
                );
              },
            ),
          ),
          actions: [
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 4.0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('TỔNG: ${format.format(order.tongTien)}', style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 18, color: Colors.deepOrange)),
                  ElevatedButton(
                    onPressed: () => Navigator.pop(context),
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.orange, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))),
                    child: const Text('ĐÓNG', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
                  ),
                ],
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildOrderRow(BuildContext context, DonHang order, NumberFormat format) {
    int totalItems = 0;
    for (var item in order.chiTietDonHangs) {
      totalItems += item.soLuong;
    }

    String timeStr = '--:--';
    if (order.createdAt != null) {
      try {
        final d = DateTime.parse(order.createdAt!).toLocal();
        timeStr = DateFormat('hh:mm a').format(d);
      } catch (_) {}
    }

    final String shortId = order.maDonHang.substring(0, order.maDonHang.length > 8 ? 8 : order.maDonHang.length).toUpperCase();

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Text(
              '#$shortId',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, fontFamily: 'monospace', color: Colors.black87),
            ),
          ),
          
          Expanded(
            flex: 2,
            child: Text(
              timeStr,
              style: const TextStyle(fontSize: 15, color: Colors.black87),
            ),
          ),
          
          Expanded(
            flex: 2,
            child: Row(
              children: [
                Icon(
                  order.loaiDon == 'tai_ban' ? Icons.table_restaurant : Icons.takeout_dining,
                  size: 18,
                  color: Colors.grey[600],
                ),
                const SizedBox(width: 8),
                Text(
                  order.loaiDon == 'tai_ban' ? 'Bàn ${order.ban?.tenBan ?? order.maBan ?? "-"}' : 'Mang đi',
                  style: const TextStyle(fontSize: 15, color: Colors.black87),
                ),
              ],
            ),
          ),
          
          Expanded(
            flex: 1,
            child: Text(
              '$totalItems món',
              style: const TextStyle(fontSize: 15, color: Colors.black87),
            ),
          ),
          
          Expanded(
            flex: 2,
            child: Text(
              format.format(order.tongTien),
              style: const TextStyle(
                fontWeight: FontWeight.w900,
                fontSize: 16,
                color: Colors.deepOrange,
              ),
            ),
          ),
          
          Expanded(
            flex: 1,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                IconButton(
                  onPressed: () {
                    _showOrderDetails(context, order, format);
                  },
                  icon: const Icon(Icons.remove_red_eye_outlined, size: 20),
                  color: Colors.blue[700],
                  tooltip: 'Xem chi tiết',
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                ),
                const SizedBox(width: 16),
                IconButton(
                  onPressed: () {
                    debugPrint('In hóa đơn đơn ${order.maDonHang}');
                  },
                  icon: const Icon(Icons.print_outlined, size: 20),
                  color: Colors.grey[700],
                  tooltip: 'In hóa đơn',
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
