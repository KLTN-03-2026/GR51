import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'viewmodels/inventory_viewmodel.dart';
import '../models/inventory_model.dart';

class InventoryView extends StatefulWidget {
  const InventoryView({Key? key}) : super(key: key);

  @override
  State<InventoryView> createState() => _InventoryViewState();
}

class _InventoryViewState extends State<InventoryView> {
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<InventoryViewModel>().fetchInventory();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final viewModel = context.watch<InventoryViewModel>();

    return Scaffold(
      backgroundColor: const Color(0xFFFDFBF7),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // HEADER
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Quản lý Tồn Kho',
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                      color: Colors.black87,
                    ),
                  ),
                  // Nút Reload xịn xò
                  Container(
                    decoration: BoxDecoration(
                      color: Colors.orange.withOpacity(0.1), // Nền cam nhạt cho tone-sur-tone với app
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: IconButton(
                      icon: const Icon(Icons.refresh, color: Colors.orange),
                      tooltip: 'Làm mới kho',
                      onPressed: () {
                        // Gọi lại hàm fetchInventory trong ViewModel để kéo data mới từ DB
                        context.read<InventoryViewModel>().fetchInventory();
                      },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              TextField(
                controller: _searchController,
                onChanged: (value) => viewModel.search(value),
                decoration: InputDecoration(
                  hintText: 'Tìm kiếm nguyên liệu...',
                  prefixIcon: const Icon(Icons.search, color: Colors.grey),
                  filled: true,
                  fillColor: Colors.white,
                  contentPadding: const EdgeInsets.symmetric(vertical: 0, horizontal: 16),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide.none,
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide(color: Colors.grey[200]!, width: 1),
                  ),
                ),
              ),
              const SizedBox(height: 24),
              // BODY
              Expanded(
                child: _buildBody(viewModel),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildBody(InventoryViewModel viewModel) {
    if (viewModel.isLoading) {
      return const Center(child: CircularProgressIndicator(color: Colors.orange));
    }

    if (viewModel.errorMessage != null && viewModel.filteredItems.isEmpty) {
      return Center(
        child: Text(
          viewModel.errorMessage!,
          style: const TextStyle(color: Colors.red, fontSize: 16),
        ),
      );
    }

    if (viewModel.filteredItems.isEmpty) {
      return const Center(
        child: Text(
          'Không tìm thấy nguyên liệu nào.',
          style: TextStyle(color: Colors.grey, fontSize: 16),
        ),
      );
    }

    return ListView.builder(
      itemCount: viewModel.filteredItems.length,
      itemBuilder: (context, index) {
        final item = viewModel.filteredItems[index];
        return _buildInventoryCard(item);
      },
    );
  }

  Widget _buildInventoryCard(InventoryItem item) {
    // Logic tô màu số lượng (State Colors)
    Color bgColor;
    Color textColor;
    String iconString;

    if (item.tonKho <= 0) {
      // Hết hàng
      bgColor = Colors.red[600]!;
      textColor = Colors.white;
      iconString = '';
    } else if (item.tonKho <= item.mucCanhBao) {
      // Sắp hết
      bgColor = Colors.orange[400]!;
      textColor = Colors.black87;
      iconString = '';
    } else {
      // An toàn
      bgColor = Colors.green[600]!;
      textColor = Colors.white;
      iconString = '';
    }
    
    // Xử lý số thập phân: nếu chẵn thì không hiện phần thập phân
    final String tonKhoStr = item.tonKho == item.tonKho.toInt() ? item.tonKho.toInt().toString() : item.tonKho.toStringAsFixed(2);
    final String mucCanhBaoStr = item.mucCanhBao == item.mucCanhBao.toInt() ? item.mucCanhBao.toInt().toString() : item.mucCanhBao.toStringAsFixed(2);

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 10,
            offset: const Offset(0, 4),
          )
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item.tenNguyenLieu,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.black87,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Mức cảnh báo: $mucCanhBaoStr ${item.donViTinh}',
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey[600],
                    ),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: bgColor,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    iconString,
                    style: const TextStyle(fontSize: 16),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    '$tonKhoStr ${item.donViTinh}',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: textColor,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
