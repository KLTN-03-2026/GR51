import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'viewmodels/menu_viewmodel.dart';
import 'viewmodels/cart_viewmodel.dart';
import '../models/menu_model.dart';
import '../models/ban_model.dart';
import '../models/cart_item_model.dart';
import 'modals/table_selection_modal.dart';
import 'modals/payment_modal.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'viewmodels/auth_viewmodel.dart';
import '../models/item_options_modal.dart'; 
class MenuView extends StatefulWidget {
  const MenuView({super.key});

  @override
  State<MenuView> createState() => _MenuViewState();
}

class _MenuViewState extends State<MenuView> {
  String currentViTri = 'Mang đi'; // State để lưu vị trí/bàn đang chọn
  Ban? selectedBan; // Lưu trữ bàn đang chọn

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<MenuViewModel>().fetchMenuData();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFDFBF7),
      body: Row(
        children: [
          Expanded(
            child: Column(
              children: [
                _buildHeaderBar(context),
                _buildCategoryFilter(context),
                Expanded(
                  child: _buildMenuGrid(context),
                ),
              ],
            ),
          ),
          _buildRightSidebar(context),
        ],
      ),
    );
  }

 // Thêm BuildContext vào tham số để có thể đọc ViewModel
  Widget _buildHeaderBar(BuildContext context) {
    // Đọc dữ liệu từ AuthViewModel
    final viewModel = context.watch<AuthViewModel>();

    // Khởi tạo các giá trị mặc định (khi chưa mở ca)
    String hoTen = 'Chưa mở ca';
    String caLamText = 'Vui lòng mở ca làm việc';
    String chuCaiDau = '?';

    // Nếu đã mở ca và có dữ liệu, tiến hành trích xuất
    if (viewModel.isShiftActive && viewModel.shiftData != null) {
      final data = viewModel.shiftData!;
      hoTen = data['nhan_vien']?['ho_ten'] ?? 'Nhân viên';
      
      // Lấy chữ cái đầu tiên của tên để làm Avatar
      chuCaiDau = hoTen.isNotEmpty ? hoTen[0].toUpperCase() : 'NV';
      
      // Định dạng lại giờ bắt đầu ca
      DateTime startTime = DateTime.parse(data['thoi_gian_bat_dau']);
      String formattedStartTime = DateFormat('HH:mm').format(startTime);
      caLamText = 'Ca làm (Từ $formattedStartTime)';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      color: Colors.white,
      child: Row(
        children: [
          Expanded(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              decoration: BoxDecoration(
                color: Colors.grey[100],
                borderRadius: BorderRadius.circular(24),
              ),
              child: TextField(
                decoration: InputDecoration(
                  icon: Icon(Icons.search, color: Colors.grey[500]),
                  hintText: 'Tìm kiếm món ăn, đồ uống...',
                  hintStyle: TextStyle(color: Colors.grey[400]),
                  border: InputBorder.none,
                ),
              ),
            ),
          ),
          const SizedBox(width: 24),
          Icon(Icons.notifications_none, color: Colors.grey[600], size: 28),
          const SizedBox(width: 24),
          Row(
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  // HIỂN THỊ TÊN THẬT
                  Text(hoTen, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  // HIỂN THỊ THỜI GIAN CA LÀM THẬT
                  Text(caLamText, style: TextStyle(color: Colors.grey[500], fontSize: 13)),
                ],
              ),
              const SizedBox(width: 12),
              CircleAvatar(
                backgroundColor: const Color(0xFFEFE6DD),
                // HIỂN THỊ CHỮ CÁI ĐẦU TIÊN CỦA TÊN THẬT
                child: Text(chuCaiDau, style: const TextStyle(color: const Color(0xFF6E4423), fontWeight: FontWeight.bold)),
              )
            ],
          )
        ],
      ),
    );
  }

  Widget _buildCategoryFilter(BuildContext context) {
    return Consumer<MenuViewModel>(
      builder: (context, viewModel, child) {
        if (viewModel.isLoading || viewModel.errorMessage != null) {
          return const SizedBox(height: 60);
        }

        return Container(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          child: SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _buildFilterChip(
                  context,
                  id: 0,
                  name: 'Tất cả',
                  isSelected: viewModel.selectedDanhMuc == null,
                  onTap: () => viewModel.selectCategory(null),
                ),
                ...viewModel.danhMucs.map((cat) => _buildFilterChip(
                      context,
                      id: cat.id,
                      name: cat.tenDanhMuc,
                      isSelected: viewModel.selectedDanhMuc == cat,
                      onTap: () => viewModel.selectCategory(cat),
                    )),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildFilterChip(BuildContext context, {required int id, required String name, required bool isSelected, required VoidCallback onTap}) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(right: 12),
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF6E4423) : Colors.white,
          borderRadius: BorderRadius.circular(24),
          border: isSelected ? null : Border.all(color: Colors.grey[300]!),
        ),
        child: Text(
          name,
          style: TextStyle(
            color: isSelected ? Colors.white : Colors.grey[800],
            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
          ),
        ),
      ),
    );
  }

  Widget _buildMenuGrid(BuildContext context) {
    // 1. Chặn lưới Menu nếu chưa mở ca
    final authVM = context.watch<AuthViewModel>();
    if (!authVM.isShiftActive) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.lock_outline, size: 80, color: Colors.grey[400]),
            const SizedBox(height: 16),
            const Text(
              'Chưa mở ca làm việc',
              style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.black54),
            ),
            const SizedBox(height: 8),
            const Text(
              'Vui lòng mở ca làm việc để hiển thị thực đơn và bắt đầu bán hàng.',
              style: TextStyle(fontSize: 14, color: Colors.grey),
            ),
          ],
        ),
      );
    }

    return Consumer<MenuViewModel>(
      builder: (context, viewModel, child) {
        if (viewModel.isLoading) {
          return const Center(child: CircularProgressIndicator(color: const Color(0xFF6E4423)));
        }

        if (viewModel.errorMessage != null) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, color: Colors.red, size: 60),
                const SizedBox(height: 16),
                Text('Có lỗi xảy ra: ${viewModel.errorMessage}', style: const TextStyle(color: Colors.red)),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () => viewModel.fetchMenuData(),
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF6E4423)),
                  child: const Text('Thử lại', style: TextStyle(color: Colors.white)),
                ),
              ],
            ),
          );
        }

        if (viewModel.filteredItems.isEmpty) {
          return const Center(child: Text('Không có món ăn nào trong danh mục này.', style: TextStyle(color: Colors.grey)));
        }

        return GridView.builder(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
          itemCount: viewModel.filteredItems.length,
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 3,
            crossAxisSpacing: 20,
            mainAxisSpacing: 20,
            childAspectRatio: 0.75, // Tỉ lệ hiển thị chuẩn card
          ),
          itemBuilder: (context, index) {
            final mon = viewModel.filteredItems[index];
            return GestureDetector(
              onTap: () {
                if (mon.isHetHang) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Món nước này đã hết nguyên liệu tồn kho!'),
                      backgroundColor: Colors.red,
                    ),
                  );
                  return;
                }

                final menuVM = context.read<MenuViewModel>();
                showDialog(
                  context: context,
                  builder: (context) => ItemOptionsModal(
                    mon: mon,
                    sizes: menuVM.listSizes,
                    toppings: menuVM.listToppings,
                    onAddToCart: (CartItem newItem) {
                      // Đẩy nguyên giỏ hàng đã có size/topping vào ViewModel
                      context.read<CartViewModel>().addToCart(newItem); 
                    },
                  ),
                );
              },
              child: _buildMenuCard(mon),
            );
          },
        );
      },
    );
  }

  Widget _buildMenuCard(Mon mon) {
    final currencyFormat = NumberFormat.currency(locale: 'vi_VN', symbol: 'đ');
    
    return Opacity(
      opacity: mon.isHetHang ? 0.6 : 1.0,
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.08),
            blurRadius: 15,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 6,
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.all(8.0),
              child: Stack(
                children: [
                  Positioned.fill(
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: (mon.hinhAnh != null && mon.hinhAnh!.isNotEmpty)
                          ? Image.network(
                              mon.hinhAnh!,
                              fit: BoxFit.cover,
                              errorBuilder: (context, error, stackTrace) => Container(
                                color: Colors.grey[200],
                                child: const Icon(Icons.image_not_supported, size: 40, color: Colors.grey),
                              ),
                            )
                          : Container(
                              color: Colors.grey[200],
                              child: const Icon(Icons.coffee, size: 50, color: Colors.grey),
                            ),
                    ),
                  ),
                  if (!mon.isHetHang)
                    Positioned(
                      bottom: 8,
                      left: 0,
                      right: 0,
                      child: Center(
                        child: Container(
                          padding: const EdgeInsets.all(4),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.9),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.add, color: const Color(0xFF6E4423), size: 20),
                        ),
                      ),
                    ),
                  if (mon.isHetHang)
                    Positioned.fill(
                      child: Container(
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.5),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Center(
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            decoration: BoxDecoration(
                              color: Colors.red[600],
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Text('Hết hàng', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
          Expanded(
            flex: 3,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    mon.tenMon,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 4),
                  Text(
                    currencyFormat.format(mon.gia),
                    style: const TextStyle(color: const Color(0xFF6E4423), fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                ],
              ),
            ),
          )
        ],
      ),
    ));
  }

  Widget _buildRightSidebar(BuildContext context) {
    final authVM = context.watch<AuthViewModel>();
    
    return Consumer<CartViewModel>(
      builder: (context, cart, child) {
        final currencyFormat = NumberFormat.currency(locale: 'vi_VN', symbol: 'đ');
        
        return Container(
          width: 360,
          color: Colors.white,
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(child: const Text('Đơn hàng hiện tại', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18))),
                  if (cart.cartItems.isNotEmpty)
                    TextButton(
                      onPressed: () => cart.clearCart(),
                      child: const Text('Xóa tất cả', style: TextStyle(color: Colors.red)),
                      style: TextButton.styleFrom(padding: EdgeInsets.zero, minimumSize: const Size(60, 30)),
                    )
                ],
              ),
              const SizedBox(height: 16),
              // Bàn/Vị trí
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFFDFBF7),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEFE6DD), 
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.location_on, color: const Color(0xFF6E4423), size: 20),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Vị trí / Bàn', style: TextStyle(color: Colors.black54, fontSize: 12)),
                          Text(currentViTri, style: const TextStyle(fontWeight: FontWeight.bold)),
                        ],
                      ),
                    ),
                    OutlinedButton(
                      onPressed: () {
                        showModalBottomSheet(
                          context: context,
                          isScrollControlled: true,
                          backgroundColor: Colors.transparent,
                          builder: (context) => TableSelectionModal(
                            onSelected: (viTri, {Ban? ban}) {
                              setState(() {
                                currentViTri = viTri;
                                selectedBan = ban;
                              });
                            },
                          ),
                        );
                      },
                      style: OutlinedButton.styleFrom(
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                        side: BorderSide(color: Colors.grey[300]!),
                      ),
                      child: const Text('Thay đổi', style: TextStyle(color: Colors.grey)),
                    )
                  ],
                ),
              ),
              const SizedBox(height: 16),
              // Danh sách / Trạng thái trống
              Expanded(
                child: cart.cartItems.isEmpty ? _buildEmptyCart() : _buildCartList(cart, currencyFormat),
              ),
              // Tính toán tổng
              const Divider(),
              const SizedBox(height: 16),
              _buildSummaryRow('Tạm tính', currencyFormat.format(cart.totalPrice)),
              const SizedBox(height: 8),
              _buildSummaryRow('Giảm giá', '0 đ'),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Tổng cộng', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
                  Text(
                    currencyFormat.format(cart.totalPrice), 
                    style: const TextStyle(color: const Color(0xFF6E4423), fontWeight: FontWeight.bold, fontSize: 24),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              // Nút Gửi pha chế
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: (!authVM.isShiftActive || cart.cartItems.isEmpty || cart.isSubmitting)
                    ? null
                    : () async {
                        final String loaiDon = selectedBan != null ? 'tai_ban' : 'mang_di';
                        final String? maBanString = selectedBan?.maBan;

                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('Đang in các tem của ly nước để pha chế...'),
                            backgroundColor: Colors.blue,
                            duration: Duration(seconds: 1),
                          ),
                        );

                        bool success = await cart.submitOrder(
                          loaiDon: loaiDon,
                          phuongThucThanhToan: 'tien_mat',
                          banId: selectedBan?.id,
                          trangThaiThanhToan: 0, // Chua thanh toan
                          trangThaiDon: 1, // Dang pha
                        );

                        if (context.mounted) {
                          if (success) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Đã gửi yêu cầu pha chế thành công!'), backgroundColor: Colors.green),
                            );
                          } else {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Lỗi: Thu ngân kiểm tra lại kết nối mạng.'), backgroundColor: Colors.red),
                            );
                          }
                        }
                      },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFFFFFFF), // Slate/Dark Blue
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('Gửi pha chế', style: TextStyle(color: Colors.black, fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: (!authVM.isShiftActive || cart.cartItems.isEmpty || cart.isSubmitting)
                      ? null
                      : () async {
                          final String loaiDon = selectedBan != null ? 'tai_ban' : 'mang_di';
                          final String? maBanString = selectedBan?.maBan;

                          final success = await showDialog<bool>(
                            context: context,
                            builder: (context) => PaymentModal(
                              loaiDon: loaiDon,
                              banId: selectedBan?.id,
                              totalPrice: cart.totalPrice,
                            ),
                          );
                          
                          if (context.mounted && success == true) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Tạo đơn hàng thành công!'),
                                backgroundColor: Colors.green,
                              ),
                            );
                          } else if (context.mounted && success == false) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text('Lỗi: Không thể khởi tạo đơn hàng.'),
                                backgroundColor: Colors.red,
                              ),
                            );
                          }
                        },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF6E4423),
                    disabledBackgroundColor: Colors.grey[300],
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 0,
                  ),
                  child: cart.isSubmitting
                      ? const SizedBox(
                          height: 24,
                          width: 24,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Text('Thanh toán', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold)),
                ),
              )
            ],
          ),
        );
      },
    );
  }

  Widget _buildEmptyCart() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: const Color(0xFFFDFBF7), // Thay đổi cho đẹp hơn
              shape: BoxShape.circle,
            ),
            child: Icon(Icons.shopping_bag_outlined, size: 48, color: const Color(0xFF6E4423)), // Đổi icon
          ),
          const SizedBox(height: 16),
          const Text(
            'Chưa có món nào trong giỏ',
            style: TextStyle(
              color: Colors.blueGrey,
              fontSize: 16,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCartList(CartViewModel cart, NumberFormat format) {
    return ListView.separated(
      itemCount: cart.cartItems.length,
      separatorBuilder: (context, index) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Divider(color: Colors.grey[200]),
      ),
      itemBuilder: (context, index) {
        final item = cart.cartItems[index];
        return Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            // Tên và giá
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(item.mon.tenMon, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
                  if (item.ghiChu != null && item.ghiChu!.trim().isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(top: 2, bottom: 2),
                      child: Text(
                        item.ghiChu!,
                        style: const TextStyle(fontSize: 12, fontStyle: FontStyle.italic, color: Colors.grey),
                      ),
                    ),
                  const SizedBox(height: 4),
                  Text(
                    format.format(item.thanhTien),
                    style: const TextStyle(color: Colors.orange, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ),
            IconButton(
              icon: const Icon(Icons.edit_note, color: Colors.grey, size: 24),
              onPressed: () {
                final menuVM = context.read<MenuViewModel>();
                showDialog(
                  context: context,
                  builder: (context) => ItemOptionsModal(
                    mon: item.mon,
                    sizes: menuVM.listSizes,
                    toppings: menuVM.listToppings,
                    editingItem: item,
                    onAddToCart: (CartItem updatedItem) {
                      cart.updateCartItem(item, updatedItem);
                    },
                  ),
                );
              },
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(),
            ),
            const SizedBox(width: 8),
            // Điều chỉnh số lượng
            Container(
              decoration: BoxDecoration(
                border: Border.all(color: Colors.grey[300]!),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Row(
                children: [
                  IconButton(
                    icon: Icon(Icons.remove, color: Colors.grey[600], size: 18),
                    onPressed: () => cart.decreaseQuantity(item),
                    constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                    padding: EdgeInsets.zero,
                  ),
                  Text(item.soLuong.toString(), style: const TextStyle(fontWeight: FontWeight.bold)),
                  IconButton(
                    icon: const Icon(Icons.add, color: Colors.orange, size: 18),
                    onPressed: () => cart.increaseQuantity(item),
                    constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                    padding: EdgeInsets.zero,
                  ),
                ],
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildSummaryRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: Colors.black54)),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
      ],
    );
  }

  // Removed _showNoteDialog as it is replaced by ItemOptionsModal
}
