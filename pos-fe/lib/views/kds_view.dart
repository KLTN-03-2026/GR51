import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'viewmodels/kds_viewmodel.dart';
import '../models/order_model.dart';

class KdsView extends StatefulWidget {
  const KdsView({super.key});

  @override
  State<KdsView> createState() => _KdsViewState();
}

class _KdsViewState extends State<KdsView> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<KdsViewModel>().startPolling();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      appBar: AppBar(
        title: const Text('Màn hình Bếp / Pha chế', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.white,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.orange),
            tooltip: 'Làm mới',
            onPressed: () {
              context.read<KdsViewModel>().fetchOrders(showLoading: true);
            },
          ),
          const SizedBox(width: 16),
        ],
      ),
      body: Consumer<KdsViewModel>(
        builder: (context, viewModel, child) {
          if (viewModel.isLoading) {
            return const Center(child: CircularProgressIndicator(color: Colors.orange));
          }

          if (viewModel.kdsOrders.isEmpty) {
            return const Center(
              child: Text(
                'Không có đơn hàng nào cần pha chế', 
                style: TextStyle(fontSize: 18, color: Colors.grey)
              )
            );
          }

          // Thay vì childAspectRatio cố định thì ta có thể dùng GridView dạng responsive hơn
          return GridView.builder(
            padding: const EdgeInsets.all(16),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 3,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
              childAspectRatio: 0.8, // Tùy chỉnh để vừa nội dung
            ),
            itemCount: viewModel.kdsOrders.length,
            itemBuilder: (context, index) {
              final order = viewModel.kdsOrders[index];
              return _buildKdsCard(context, order, viewModel);
            },
          );
        },
      ),
    );
  }

  Widget _buildKdsCard(BuildContext context, DonHang order, KdsViewModel viewModel) {
    final priorityScore = order.priorityScore ?? 0;
    final isHighPriority = priorityScore >= 15;
    final headerColor = isHighPriority ? Colors.red : Colors.orange;
    
    final minutesWaiting = order.minutesWaiting ?? 0;
    final loaiDonText = order.loaiDon == 'mang_di' 
        ? 'Mang đi' 
        : '${order.ban?.tenBan ?? order.maBan ?? '?'}';

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          // Header Card
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: headerColor,
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(12),
                topRight: Radius.circular(12),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    '#${order.maDonHang.length > 5 ? order.maDonHang.substring(order.maDonHang.length - 5) : order.maDonHang}',
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Text(
                  '$minutesWaiting phút',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                ),
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.3),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    loaiDonText,
                    style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
          ),
          
          // Body Card
          Expanded(
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: order.chiTietDonHangs.length,
              separatorBuilder: (context, index) => const Divider(),
              itemBuilder: (context, index) {
                final chiTiet = order.chiTietDonHangs[index];
                final tenMon = chiTiet.mon?.tenMon ?? 'Món ${chiTiet.maMon}';
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${chiTiet.soLuong}x',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            tenMon,
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
                          ),
                        ),
                      ],
                    ),
                    if (chiTiet.ghiChu != null && chiTiet.ghiChu!.trim().isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(left: 28, top: 4),
                        child: Text(
                          chiTiet.ghiChu!,
                          style: const TextStyle(
                            fontSize: 14,
                            fontStyle: FontStyle.italic,
                            color: Colors.grey,
                          ),
                        ),
                      ),
                  ],
                );
              },
            ),
          ),
          
          // Footer Card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            child: ElevatedButton(
              onPressed: () => viewModel.completeOrder(order.maDonHang),
              style: ElevatedButton.styleFrom(
                backgroundColor: headerColor,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                elevation: 0,
              ),
              child: const Text('HOÀN THÀNH', 
                style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)
              ),
            ),
          ),
        ],
      ),
    );
  }
}
