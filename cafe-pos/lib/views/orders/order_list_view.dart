import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'dart:async';
import '../viewmodels/order_viewmodel.dart';
import '../../models/order_model.dart';
import '../../services/api_service.dart';

class OrderListView extends StatefulWidget {
  const OrderListView({super.key});

  @override
  State<OrderListView> createState() => _OrderListViewState();
}

class _OrderListViewState extends State<OrderListView> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  Timer? _pollingTimer;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<OrderViewModel>().loadOrders();
    });
    
    // Polling every 30s silently
    _pollingTimer = Timer.periodic(const Duration(seconds: 30), (timer) {
      if (mounted) {
        context.read<OrderViewModel>().loadOrdersSilently();
      }
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    _pollingTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final viewModel = context.watch<OrderViewModel>();
    final dangPhaOrders = viewModel.dangPhaOrders;
    final choThanhToanOrders = viewModel.pendingPaymentOrders;

    return Scaffold(
      backgroundColor: const Color(0xFFE2E8F0), 
      appBar: AppBar(
        title: const Text('Trạm xử lý đơn', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20)),
        backgroundColor: Colors.white,
        elevation: 0,
        bottom: TabBar(
          controller: _tabController,
          labelColor: const Color(0xFF6E4423),
          unselectedLabelColor: Colors.grey,
          indicatorColor: const Color(0xFF6E4423),
          indicatorWeight: 3,
          labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
          tabs: [
            Tab(text: ' ĐANG PHA CHẾ (${dangPhaOrders.length})'),
            Tab(text: ' CHỜ THANH TOÁN (${choThanhToanOrders.length})'),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: const Color(0xFF6E4423)),
            onPressed: () => context.read<OrderViewModel>().loadOrders(),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: _buildBody(viewModel, dangPhaOrders, choThanhToanOrders),
    );
  }

  Widget _buildBody(OrderViewModel viewModel, List<DonHang> dangPha, List<DonHang> choThanhToan) {
    if (viewModel.isLoading && viewModel.orders.isEmpty) {
      return const Center(child: CircularProgressIndicator(color: const Color(0xFF6E4423)));
    }

    if (viewModel.errorMessage != null && viewModel.orders.isEmpty) {
      return Center(child: Text(viewModel.errorMessage!, style: const TextStyle(color: Colors.red)));
    }

    return TabBarView(
      controller: _tabController,
      children: [
        _buildOrderGrid(dangPha, viewModel, isTab1: true),
        _buildOrderGrid(choThanhToan, viewModel, isTab1: false),
      ],
    );
  }

  Widget _buildOrderGrid(List<DonHang> orders, OrderViewModel viewModel, {required bool isTab1}) {
    if (orders.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(isTab1 ? Icons.coffee_maker : Icons.price_check, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text('Không có đơn hàng nào chờ xử lý.', style: TextStyle(color: Colors.grey[600], fontSize: 16)),
          ],
        ),
      );
    }
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: LayoutBuilder(
        builder: (context, constraints) {
          final double itemWidth = ((constraints.maxWidth - 48) / 3).floorToDouble();
          return Wrap(
            spacing: 24,
            runSpacing: 24,
            children: orders.map((order) {
              return SizedBox(
                width: itemWidth,
                child: SmartOrderCard(order: order, viewModel: viewModel, isTab1: isTab1),
              );
            }).toList(),
          );
        },
      ),
    );
  }
}

class SmartOrderCard extends StatelessWidget {
  final DonHang order;
  final OrderViewModel viewModel;
  final bool isTab1;

  const SmartOrderCard({
    super.key,
    required this.order,
    required this.viewModel,
    required this.isTab1,
  });

  @override
  Widget build(BuildContext context) {
    final currencyFormat = NumberFormat.currency(locale: 'vi_VN', symbol: 'đ');
    final bool isPaid = order.trangThaiThanhToan == 1;

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.06),
            blurRadius: 15,
            offset: const Offset(0, 5),
          )
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isTab1 ? Colors.grey[50] : const Color(0xFFF0FDF4),
              borderRadius: const BorderRadius.only(topLeft: Radius.circular(16), topRight: Radius.circular(16)),
              border: Border(bottom: BorderSide(color: Colors.grey[200]!)),
            ),
            child: isTab1 ? _buildTab1Header() : _buildTab2Header(currencyFormat),
          ),
          
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: order.chiTietDonHangs.length,
              itemBuilder: (context, i) {
                final item = order.chiTietDonHangs[i];
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12.0),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: const Color(0xFF6E4423).withOpacity(0.1),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text('${item.soLuong}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: const Color(0xFF6E4423))),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Expanded(
                                    child: Text(item.mon?.tenMon ?? 'Món xoá/Ẩn', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.black87)),
                                  ),
                                  if (isTab1 && item.monId > 0)
                                    InkWell(
                                      onTap: () => _showRecipeDialog(context, item.monId, item.mon?.tenMon ?? 'Công thức'),
                                      borderRadius: BorderRadius.circular(4),
                                      child: const Padding(
                                        padding: EdgeInsets.symmetric(horizontal: 4.0),
                                        child: Icon(Icons.menu_book, color: const Color(0xFF6E4423), size: 20),
                                      ),
                                    ),
                                ],
                              ),
                              if (item.ghiChu != null && item.ghiChu!.trim().isNotEmpty)
                                Padding(
                                  padding: const EdgeInsets.only(top: 4.0),
                                  child: Text('Ghi chú: ${item.ghiChu}', style: TextStyle(fontSize: 13, color: Colors.grey[600], fontStyle: FontStyle.italic)),
                                ),
                            ],
                          ),
                        ),
                        if (!isTab1)
                          Text(currencyFormat.format(item.donGia * item.soLuong), style: const TextStyle(color: Colors.grey, fontSize: 13, fontWeight: FontWeight.w600)),
                      ],
                    ),
                  );
                 },
              ),
            ),
          
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Align(
              alignment: Alignment.centerRight,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: isPaid ? Colors.green.withOpacity(0.15) : Colors.red.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: isPaid ? Colors.green.withOpacity(0.3) : Colors.red.withOpacity(0.3)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(isPaid ? Icons.check_circle : Icons.warning_rounded, size: 14, color: isPaid ? Colors.green[700] : Colors.red[700]),
                    const SizedBox(width: 6),
                    Text(
                      isPaid ? 'Đã thanh toán' : 'Chưa thu tiền',
                      style: TextStyle(
                        color: isPaid ? Colors.green[800] : Colors.red[800],
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),
          
          Padding(
            padding: const EdgeInsets.all(16),
            child: isTab1 ? _buildTab1Footer(context) : _buildTab2Footer(context),
          ),
        ],
      ),
    );
  }

  void _showRecipeDialog(BuildContext context, int monId, String tenMon) {
    showDialog(
      context: context,
      builder: (ctx) {
        return _RecipeDialog(monId: monId, tenMon: tenMon);
      },
    );
  }

  Widget _buildTab1Header() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              'ĐH #${order.maDonHang.substring(0, order.maDonHang.length > 8 ? 8 : order.maDonHang.length).toUpperCase()}',
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, fontFamily: 'monospace', color: Colors.black87),
            ),
            OrderTimer(createdAt: order.createdAt),
          ],
        ),
        const SizedBox(height: 8),
        Row(
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: order.loaiDon == 'tai_ban' ? Colors.blue.withOpacity(0.1) : Colors.green.withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(order.loaiDon == 'tai_ban' ? Icons.table_restaurant : Icons.takeout_dining, color: order.loaiDon == 'tai_ban' ? Colors.blue : Colors.green, size: 16),
            ),
            const SizedBox(width: 8),
            Text(
              order.loaiDon == 'tai_ban' ? 'Bàn: ${order.ban?.tenBan ?? order.maBan ?? "N/A"}' : 'Mang đi',
              style: const TextStyle(color: Colors.black87, fontWeight: FontWeight.bold, fontSize: 15),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildTab2Header(NumberFormat format) {
    return Column(
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(order.loaiDon == 'tai_ban' ? 'SỐ BÀN' : 'LOẠI ĐƠN', style: const TextStyle(color: Colors.grey, fontSize: 12, fontWeight: FontWeight.bold)),
                Text(
                  order.loaiDon == 'tai_ban' ? (order.ban?.tenBan ?? order.maBan ?? "N/A") : 'Mang đi',
                  style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: Colors.black87),
                ),
              ],
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                 const Text('TỔNG TIỀN', style: TextStyle(color: Colors.grey, fontSize: 12, fontWeight: FontWeight.bold)),
                 Text(
                  format.format(order.tongTien),
                  style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: Colors.green),
                ),
              ],
            )
          ],
        ),
      ],
    );
  }

  Widget _buildTab1Footer(BuildContext context) {
    final format = NumberFormat.currency(locale: 'vi_VN', symbol: 'đ');
    return Column(
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('TỔNG TIỀN:', style: TextStyle(color: Colors.grey, fontSize: 12, fontWeight: FontWeight.bold)),
            Text(
              format.format(order.tongTien),
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: const Color(0xFF6E4423)),
            ),
          ],
        ),
        const SizedBox(height: 12),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: () async {
              final isPaid = order.trangThaiThanhToan == 1;
              
              final success = await viewModel.completePreparation(order.id);
              
              if (success && context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(
                      isPaid ? 'Đơn hàng đã hoàn tất và được lưu vào Lịch sử' : 'Đã pha xong. Đơn chờ thu tiền tại mục Chờ thanh toán',
                      style: const TextStyle(fontWeight: FontWeight.bold)
                    ),
                    backgroundColor: isPaid ? Colors.green : const Color(0xFF6E4423),
                    duration: const Duration(seconds: 2),
                  ),
                );
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF6E4423),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              elevation: 0,
            ),
            child: const Text('HOÀN THÀNH PHA CHẾ', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.1)),
          ),
        ),
        const SizedBox(height: 8),
        SizedBox(
          width: double.infinity,
          child: OutlinedButton(
            onPressed: () => _showCancelDialog(context),
            style: OutlinedButton.styleFrom(
              foregroundColor: Colors.red,
              padding: const EdgeInsets.symmetric(vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              side: const BorderSide(color: Colors.red, width: 1.5),
            ),
            child: const Text('HUỶ ĐƠN HÀNG', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.1, fontSize: 13)),
          ),
        ),
      ],
    );
  }

  Widget _buildTab2Footer(BuildContext context) {
    return Column(
      children: [
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: () async {
              final success = await viewModel.confirmPayment(order.id);
              if (success && context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text(
                      'Thanh toán thành công! Đơn hàng đã được chuyển vào Lịch sử.',
                      style: TextStyle(fontWeight: FontWeight.bold)
                    ),
                    backgroundColor: Colors.green,
                    duration: Duration(seconds: 2),
                  ),
                );
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.green,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              elevation: 0,
            ),
            child: const Text('XÁC NHẬN THU TIỀN', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.1)),
          ),
        ),
        const SizedBox(height: 8),
        SizedBox(
          width: double.infinity,
          child: OutlinedButton(
            onPressed: () => _showCancelDialog(context),
            style: OutlinedButton.styleFrom(
              foregroundColor: Colors.red,
              padding: const EdgeInsets.symmetric(vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              side: const BorderSide(color: Colors.red, width: 1.5),
            ),
            child: const Text('HUỶ ĐƠN HÀNG', style: TextStyle(fontWeight: FontWeight.bold, letterSpacing: 1.1, fontSize: 13)),
          ),
        ),
      ],
    );
  }

  void _showCancelDialog(BuildContext context) {
    final reasonController = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(color: Colors.red[50], borderRadius: BorderRadius.circular(10)),
            child: Icon(Icons.cancel_outlined, color: Colors.red[600], size: 24),
          ),
          const SizedBox(width: 12),
          const Expanded(child: Text('Xác nhận huỷ đơn', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18))),
        ]),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: Colors.amber[50], borderRadius: BorderRadius.circular(10), border: Border.all(color: Colors.amber[300]!)),
              child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Icon(Icons.warning_amber_rounded, color: const Color(0xFF6E4423), size: 20),
                const SizedBox(width: 8),
                Expanded(child: Text(
                  order.trangThaiDon == 2
                    ? 'Đơn này đã pha chế xong. Nguyên liệu sẽ được hoàn trả về kho.'
                    : 'Đơn này đang pha chế. Huỷ sẽ không ảnh hưởng tồn kho.',
                  style: const TextStyle(fontSize: 13, color: Colors.black87),
                )),
              ]),
            ),
            const SizedBox(height: 16),
            const Text('Lý do huỷ *', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
            const SizedBox(height: 8),
            TextField(
              controller: reasonController,
              maxLines: 2,
              decoration: InputDecoration(
                hintText: 'VD: Khách đổi ý, hết nguyên liệu...',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                filled: true,
                fillColor: Colors.grey[50],
              ),
            ),
          ],
        ),
        actionsPadding: const EdgeInsets.fromLTRB(24, 0, 24, 20),
        actions: [
          Row(children: [
            Expanded(
              child: OutlinedButton(
                onPressed: () => Navigator.pop(ctx),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                child: const Text('Quay lại', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blueGrey)),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: ElevatedButton.icon(
                icon: const Icon(Icons.cancel, color: Colors.white, size: 18),
                label: const Text('Xác nhận huỷ', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                onPressed: () async {
                  if (reasonController.text.trim().isEmpty) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Vui lòng nhập lý do huỷ đơn!'), backgroundColor: const Color(0xFF6E4423)),
                    );
                    return;
                  }
                  Navigator.pop(ctx);
                  final result = await viewModel.cancelOrder(order.id, reasonController.text.trim());
                  if (context.mounted) {
                    final success = result['success'] == true;
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(
                          success ? (result['message'] ?? 'Huỷ đơn thành công') : (result['message'] ?? 'Lỗi huỷ đơn'),
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                        backgroundColor: success ? Colors.green : Colors.red,
                        duration: const Duration(seconds: 3),
                      ),
                    );
                  }
                },
              ),
            ),
          ]),
        ],
      ),
    );
  }
}

class OrderTimer extends StatefulWidget {
  final String? createdAt;
  const OrderTimer({super.key, required this.createdAt});
  
  @override
  State<OrderTimer> createState() => _OrderTimerState();
}

class _OrderTimerState extends State<OrderTimer> {
  late Timer _timer;
  Duration _duration = Duration.zero;

  @override
  void initState() {
    super.initState();
    _calculateDuration();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) {
        _calculateDuration();
      }
    });
  }
  
  @override
  void dispose() {
    _timer.cancel();
    super.dispose();
  }

  void _calculateDuration() {
    if (widget.createdAt == null) return;
    try {
      final created = DateTime.parse(widget.createdAt!).toLocal();
      setState(() {
        _duration = DateTime.now().difference(created);
        if (_duration.isNegative) _duration = Duration.zero;
      });
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    if (widget.createdAt == null) return const SizedBox();
    
    final minutes = _duration.inMinutes;
    final seconds = _duration.inSeconds % 60;
    final isLate = minutes >= 15;
    
    final String minStr = minutes.toString().padLeft(2, '0');
    final String secStr = seconds.toString().padLeft(2, '0');
    
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: isLate ? Colors.red.withOpacity(0.15) : const Color(0xFF6E4423).withOpacity(0.15),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: isLate ? Colors.red.withOpacity(0.3) : const Color(0xFF6E4423).withOpacity(0.3)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.timer_outlined, size: 14, color: isLate ? Colors.red[700] : const Color(0xFF6E4423)),
          const SizedBox(width: 4),
          Text(
            '$minStr:$secStr',
            style: TextStyle(
              color: isLate ? Colors.red[800] : const Color(0xFF6E4423),
              fontWeight: FontWeight.bold,
              fontSize: 13,
            ),
          ),
        ],
      ),
    );
  }
}

class _RecipeDialog extends StatelessWidget {
  final int monId;
  final String tenMon;

  const _RecipeDialog({required this.monId, required this.tenMon});

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      title: Text('Công thức: $tenMon', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 20)),
      content: SizedBox(
        width: 500, // Make it reasonably wide
        child: FutureBuilder<Map<String, dynamic>>(
          future: ApiService().fetchRecipe(monId),
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const SizedBox(
                height: 150,
                child: Center(child: CircularProgressIndicator(color: const Color(0xFF6E4423))),
              );
            }
            if (snapshot.hasError) {
              return SizedBox(
                height: 100,
                child: Center(
                  child: Text(
                    snapshot.error.toString().replaceAll('Exception: ', ''),
                    style: const TextStyle(color: Colors.red),
                    textAlign: TextAlign.center,
                  ),
                ),
              );
            }
            if (!snapshot.hasData || snapshot.data!.isEmpty) {
              return const SizedBox(
                height: 100,
                child: Center(child: Text('Không có dữ liệu công thức')),
              );
            }

            final data = snapshot.data!;
            print('Debug Recipe Data từ API: $data');
            final huongDan = data['huong_dan']?.toString() ?? '(Không tìm thấy hướng dẫn từ dữ liệu)';
            final nguyenLieuList = data['danh_sach_nguyen_lieu'] as List? ?? [];

            return SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Nguyên liệu cần thiết', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  const SizedBox(height: 8),
                  if (nguyenLieuList.isEmpty)
                    const Text('Không có thông tin nguyên liệu.', style: TextStyle(fontStyle: FontStyle.italic, color: Colors.grey))
                  else
                    ...nguyenLieuList.map((item) {
                      final ten = item['ten_nguyen_lieu'] ?? 'Nguyên liệu';
                      final sl = item['so_luong_can'] ?? '';
                      final dvt = item['don_vi_tinh'] ?? '';
                      return Padding(
                        padding: const EdgeInsets.only(bottom: 6.0),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('• ', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                            Expanded(child: Text('$ten', style: const TextStyle(fontSize: 15))),
                            const SizedBox(width: 8),
                            Text('$sl $dvt', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
                          ],
                        ),
                      );
                    }).toList(),
                  const SizedBox(height: 20),
                  const Text('Hướng dẫn pha chế', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  const SizedBox(height: 8),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.grey[100],
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.grey[300]!),
                    ),
                    child: Text(
                      huongDan,
                      style: const TextStyle(fontSize: 15, height: 1.5, color: Colors.black87),
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ),
      actions: [
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: () => Navigator.of(context).pop(),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.grey[200],
              foregroundColor: Colors.black87,
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              elevation: 0,
            ),
            child: const Text('ĐÓNG', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          ),
        ),
      ],
    );
  }
}
