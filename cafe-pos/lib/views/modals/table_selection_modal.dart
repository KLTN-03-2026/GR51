import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../viewmodels/table_selection_viewmodel.dart';
import '../../models/ban_model.dart';
import '../../models/khu_vuc_model.dart';

class TableSelectionModal extends StatefulWidget {
  final Function(String, {Ban? ban}) onSelected;

  const TableSelectionModal({Key? key, required this.onSelected}) : super(key: key);

  @override
  State<TableSelectionModal> createState() => _TableSelectionModalState();
}

class _TableSelectionModalState extends State<TableSelectionModal> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<TableSelectionViewModel>().loadData();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.85,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(20),
          topRight: Radius.circular(20),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Chọn vị trí / Bàn',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.pop(context),
                ),
              ],
            ),
          ),
          const Divider(height: 1),
          
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // MANG ĐI & GIAO HÀNG
                  const Text(
                    'MANG ĐI & GIAO HÀNG',
                    style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.grey),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      _buildDeliveryOption('Mang đi', Icons.directions_walk),
                      const SizedBox(width: 12),
                      _buildDeliveryOption('ShopeeFood', Icons.motorcycle),
                      const SizedBox(width: 12),
                      _buildDeliveryOption('GrabFood', Icons.motorcycle),
                    ],
                  ),
                  const SizedBox(height: 24),
                  
                  // SƠ ĐỒ BÀN
                  Consumer<TableSelectionViewModel>(
                    builder: (context, viewModel, child) {
                      if (viewModel.isLoading && viewModel.khuVucs.isEmpty) {
                        return const Padding(
                          padding: EdgeInsets.all(40.0),
                          child: Center(child: CircularProgressIndicator(color: Colors.orange)),
                        );
                      }
                      
                      if (viewModel.errorMessage != null && viewModel.khuVucs.isEmpty) {
                        return Padding(
                          padding: const EdgeInsets.all(40.0),
                          child: Center(
                            child: Column(
                              children: [
                                const Icon(Icons.error_outline, color: Colors.red, size: 48),
                                const SizedBox(height: 16),
                                Text('Lỗi: ${viewModel.errorMessage}', style: const TextStyle(color: Colors.red)),
                                const SizedBox(height: 16),
                                ElevatedButton(
                                  onPressed: () => viewModel.loadData(),
                                  style: ElevatedButton.styleFrom(backgroundColor: Colors.orange),
                                  child: const Text('Thử lại', style: TextStyle(color: Colors.white)),
                                )
                              ],
                            ),
                          ),
                        );
                      }

                      final khuVucName = viewModel.selectedKhuVuc?.tenKhuVuc ?? 'TẦNG 1';

                      return Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'SƠ ĐỒ BÀN (${khuVucName.toUpperCase()})',
                                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.grey),
                              ),
                              if (viewModel.khuVucs.isNotEmpty)
                                DropdownButton<KhuVuc>(
                                  value: viewModel.selectedKhuVuc,
                                  underline: const SizedBox(),
                                  icon: const Icon(Icons.arrow_drop_down, color: Colors.orange),
                                  items: viewModel.khuVucs.map((kv) {
                                    return DropdownMenuItem(
                                      value: kv,
                                      child: Text(kv.tenKhuVuc, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.orange)),
                                    );
                                  }).toList(),
                                  onChanged: (KhuVuc? newValue) {
                                    if (newValue != null) {
                                      viewModel.selectKhuVuc(newValue);
                                    }
                                  },
                                ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          if (viewModel.selectedKhuVuc != null && viewModel.selectedKhuVuc!.bans.isNotEmpty)
                            GridView.builder(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: 6,
                                crossAxisSpacing: 16,
                                mainAxisSpacing: 16,
                                childAspectRatio: 1.0,
                              ),
                              itemCount: viewModel.selectedKhuVuc!.bans.length,
                              itemBuilder: (context, index) {
                                final ban = viewModel.selectedKhuVuc!.bans[index];
                                return _buildTableCard(ban);
                              },
                            )
                          else
                            const Padding(
                              padding: EdgeInsets.all(40),
                              child: Center(
                                child: Text('Không có bàn nào trong khu vực này', style: TextStyle(color: Colors.grey)),
                              ),
                            ),
                        ],
                      );
                    },
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDeliveryOption(String name, IconData icon) {
    return Expanded(
      child: GestureDetector(
        onTap: () {
          widget.onSelected(name, ban: null);
          Navigator.pop(context);
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 8),
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border.all(color: Colors.grey[300]!),
            borderRadius: BorderRadius.circular(16),
          ),
          child: Column(
            children: [
              Icon(icon, color: Colors.grey[500], size: 32),
              const SizedBox(height: 12),
              Text(
                name,
                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTableCard(Ban ban) {
    final isOccupied = ban.trangThai == 'dang_phuc_vu';
    
    return GestureDetector(
      onTap: () {
        widget.onSelected(ban.tenBan, ban: ban);
        Navigator.pop(context);
      },
      child: Container(
        decoration: BoxDecoration(
          color: isOccupied ? Colors.orange[50] : Colors.white,
          border: Border.all(color: isOccupied ? Colors.orange[300]! : Colors.grey[300]!),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Stack(
          children: [
            Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.table_bar,    
                    color: isOccupied ? Colors.orange : Colors.grey[400],
                    size: 32,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    ban.tenBan,
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 14,
                      color: isOccupied ? Colors.orange[800] : Colors.black87,
                    ),
                  ),
                ],
              ),
            ),
            if (isOccupied)
              Positioned(
                top: 10,
                right: 10,
                child: Container(
                  width: 12,
                  height: 12,
                  decoration: const BoxDecoration(
                    color: Colors.orange,
                    shape: BoxShape.circle,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
