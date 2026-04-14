import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../models/menu_model.dart';
import '../../models/cart_item_model.dart';

class ItemOptionsModal extends StatefulWidget {
  final Mon mon;
  final List<dynamic> sizes;
  final List<dynamic> toppings;
  final Function(CartItem) onAddToCart;
  final CartItem? editingItem;

  const ItemOptionsModal({
    super.key,
    required this.mon,
    required this.sizes,
    required this.toppings,
    required this.onAddToCart,
    this.editingItem,
  });

  @override
  State<ItemOptionsModal> createState() => _ItemOptionsModalState();
}

class _ItemOptionsModalState extends State<ItemOptionsModal> {
  Map<String, dynamic>? _selectedSize;
  List<Map<String, dynamic>> _selectedToppings = [];
  final _currencyFormat = NumberFormat.currency(locale: 'vi_VN', symbol: 'đ');
  final TextEditingController _noteController = TextEditingController();

  @override
  void initState() {
    super.initState();
    if (widget.editingItem != null) {
      _selectedSize = widget.editingItem!.selectedSize;
      _selectedToppings = List.from(widget.editingItem!.selectedToppings);
      _noteController.text = widget.editingItem!.ghiChu ?? '';
    } else {
      // Mặc định tự động chọn Size đầu tiên nếu có
      if (widget.sizes.isNotEmpty) {
        _selectedSize = widget.sizes[0] as Map<String, dynamic>;
      }
    }
  }

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  // Hàm tính tổng tiền Real-time khi bấm bấm
  double get _currentTotal {
    double total = widget.mon.gia;
    if (_selectedSize != null) {
      total += double.tryParse(_selectedSize!['gia_cong_them'].toString()) ?? 0;    
    }
    for (var t in _selectedToppings) {
      total += double.tryParse(t['gia_tien'].toString()) ?? 0;
    }
    return total;
  }

  void _toggleTopping(Map<String, dynamic> topping) {
    setState(() {
      if (_selectedToppings.contains(topping)) {
        _selectedToppings.remove(topping);
      } else {
        _selectedToppings.add(topping);
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.grey[50], // Tách biệt màu nền form với các nút trắng
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        width: 500, // Chiều rộng chuẩn cho form Popup trên POS
        constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.85),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // 1. Header
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 16, 16, 16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Tùy chọn món', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.grey),
                    onPressed: () => Navigator.pop(context),
                  )
                ],
              ),
            ),
            const Divider(height: 1),

            // 2. Nội dung cuộn được
            Expanded(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Thông tin món gốc
                    Row(
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: (widget.mon.hinhAnh != null) 
                              ? Image.network(widget.mon.hinhAnh!, width: 80, height: 80, fit: BoxFit.cover)
                              : Container(width: 80, height: 80, color: Colors.grey[200], child: const Icon(Icons.coffee, color: Colors.grey)),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(widget.mon.tenMon, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                              const SizedBox(height: 8),
                              Text(_currencyFormat.format(widget.mon.gia), style: const TextStyle(fontSize: 16, color: Colors.orange, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        )
                      ],
                    ),
                    const SizedBox(height: 24),

                    // Khối chọn Size
                    if (widget.sizes.isNotEmpty) ...[
                      _buildSectionTitle('1', 'Chọn Size', isRequired: true),
                      const SizedBox(height: 12),
                      GridView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 3, childAspectRatio: 2.2, crossAxisSpacing: 12, mainAxisSpacing: 12
                        ),
                        itemCount: widget.sizes.length,
                        // Thay thế đoạn code bên trong phần itemBuilder của GridView chọn Size:

itemBuilder: (context, index) {
  final size = widget.sizes[index];
  final isSelected = _selectedSize == size;
  
  // 1. Fix an toàn cho giá tiền (Nếu không có cột   thì cho bằng 0)
  final priceOffset = double.tryParse(size['gia_cong_them']?.toString() ?? '0') ?? 0;
  String priceText = priceOffset == 0 ? '0 đ' : (priceOffset > 0 ? '+${_currencyFormat.format(priceOffset)}' : _currencyFormat.format(priceOffset));

  return GestureDetector(
    onTap: () => setState(() => _selectedSize = size),
    child: Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: isSelected ? Colors.orange[50] : Colors.white,
        border: Border.all(color: isSelected ? Colors.orange : Colors.grey[300]!, width: isSelected ? 2 : 1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Stack(
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // 2. Fix an toàn cho Tên Size (Dùng ?? để dự phòng)
              Text(
                size['ten_kich_co']?.toString() ?? 'Size Null', // <--- SỬA CHÍNH Ở ĐÂY
                style: TextStyle(
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal, 
                  color: isSelected ? Colors.orange[800] : Colors.black87
                )
              ),
              Text(priceText, style: TextStyle(fontSize: 12, color: Colors.grey[600])),
            ],
          ),
          if (isSelected) const Positioned(top: 0, right: 0, child: Icon(Icons.check, color: Colors.orange, size: 16))
        ],
      ),
    ),
  );
},
                      ),
                      const SizedBox(height: 24),
                    ],

                    // Khối chọn Topping
                    if (widget.toppings.isNotEmpty) ...[
                      _buildSectionTitle('2', 'Thêm Topping', subTitle: '(Không bắt buộc)'),
                      const SizedBox(height: 12),
                      GridView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2, childAspectRatio: 3.5, crossAxisSpacing: 12, mainAxisSpacing: 12
                        ),
                        itemCount: widget.toppings.length,
                        itemBuilder: (context, index) {
                          final topping = widget.toppings[index];
                          final isSelected = _selectedToppings.contains(topping);
                          final price = double.tryParse(topping['gia_tien'].toString()) ?? 0;

                          return GestureDetector(
                            onTap: () => _toggleTopping(topping),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12),
                              decoration: BoxDecoration(
                                color: Colors.white, // Nền trắng nổi bật
                                border: Border.all(color: Colors.grey[300]!),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        Text(topping['ten_topping'], overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w500)),
                                        Text('+${_currencyFormat.format(price)}', style: TextStyle(fontSize: 12, color: Colors.grey[600])),
                                      ],
                                    ),
                                  ),
                                  Icon(isSelected ? Icons.check_circle : Icons.circle_outlined, color: isSelected ? Colors.blue : Colors.grey[300])
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ],
                    const SizedBox(height: 24),

                    // Khối Ghi chú
                    _buildSectionTitle(
                      (widget.sizes.isNotEmpty && widget.toppings.isNotEmpty) ? '3' 
                      : (widget.sizes.isNotEmpty || widget.toppings.isNotEmpty) ? '2' : '1', 
                      'Ghi chú', 
                      subTitle: '(Không bắt buộc)'
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _noteController,
                      maxLines: 2,
                      decoration: InputDecoration(
                        filled: true,
                        fillColor: Colors.white,
                        hintText: 'VD: Ít đá, không đường...',
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide: BorderSide(color: Colors.grey[300]!),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(8),
                          borderSide: BorderSide(color: Colors.grey[300]!),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const Divider(height: 1),

            // 3. Bottom Bar
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.only(bottomLeft: Radius.circular(16), bottomRight: Radius.circular(16))
              ),
              child: Row(
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Tạm tính', style: TextStyle(color: Colors.grey, fontSize: 13)),
                      Text(_currencyFormat.format(_currentTotal), style: const TextStyle(color: Colors.orange, fontSize: 22, fontWeight: FontWeight.bold)),
                    ],
                  ),
                  const Spacer(),
                  OutlinedButton(
                    onPressed: () => Navigator.pop(context),
                    style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))),
                    child: const Text('Hủy', style: TextStyle(color: Colors.black87)),
                  ),
                  const SizedBox(width: 12),
                  ElevatedButton(
                    onPressed: () {
                      if (widget.sizes.isNotEmpty && _selectedSize == null) {
                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Vui lòng chọn Size!')));
                        return;
                      }
                      
                      // Gom data thành 1 CartItem hoàn chỉnh
                      final newItem = CartItem(
                        mon: widget.mon,
                        soLuong: widget.editingItem?.soLuong ?? 1,
                        ghiChu: _noteController.text.trim().isNotEmpty ? _noteController.text.trim() : null,
                        selectedSize: _selectedSize,
                        selectedToppings: _selectedToppings,
                      );
                      
                      widget.onAddToCart(newItem);
                      Navigator.pop(context); // Đóng popup
                    },
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.orange[800], padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8))),
                    child: Text(widget.editingItem != null ? 'Cập nhật' : 'Thêm vào đơn', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                  )
                ],
              ),
            )
          ],
        ),
      ),
    );
  }

 
  Widget _buildSectionTitle(String number, String title, {bool isRequired = false, String? subTitle}) {
    return Row(
      children: [
        CircleAvatar(radius: 12, backgroundColor: Colors.grey[200], child: Text(number, style: const TextStyle(fontSize: 12, color: Colors.black87))),
        const SizedBox(width: 8),
        Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        if (isRequired) const Text(' *', style: TextStyle(color: Colors.red, fontSize: 16)),
        // Đã sửa lại chữ Padding và thêm subTitle!
        if (subTitle != null) Padding(padding: const EdgeInsets.only(left: 8), child: Text(subTitle!, style: const TextStyle(color: Colors.grey, fontSize: 14))),
      ],
    );
  }
}