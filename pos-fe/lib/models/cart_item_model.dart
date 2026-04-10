import 'menu_model.dart';

class CartItem {
  final Mon mon;
  int soLuong;
  String? ghiChu;
  
  // Thêm 2 trường mới để hứng dữ liệu từ Popup
  Map<String, dynamic>? selectedSize; 
  List<Map<String, dynamic>> selectedToppings;

  CartItem({
    required this.mon,
    this.soLuong = 1,
    this.ghiChu,
    this.selectedSize,
    this.selectedToppings = const [], // Mặc định là không có topping
  });

  // Cập nhật lại hàm tính tiền: Giá món + Giá Size + Giá các Topping
  double get thanhTien {
    double basePrice = mon.gia;
    
    // Cộng/trừ tiền Size (Ví dụ: Size S: -5000, Size L: +10000)
    if (selectedSize != null && selectedSize!['gia_cong_them'] != null) {
      basePrice += double.tryParse(selectedSize!['gia_cong_them'].toString()) ?? 0;
    }
    
    // Cộng tiền Topping
    for (var topping in selectedToppings) {
      if (topping['gia_tien'] != null) {
        basePrice += double.tryParse(topping['gia_tien'].toString()) ?? 0;
      }
    }
    
    return basePrice * soLuong;
  }

  // Nâng cấp hàm xuất JSON để đẩy lên API
  Map<String, dynamic> toJson() {
    return {
      'ma_mon': mon.maMon,
      'so_luong': soLuong,
      'don_gia': mon.gia,
      'ghi_chu': ghiChu ?? '',
      'ma_kich_co': selectedSize?['ma_kich_co'],
      'gia_cong_them': selectedSize?['gia_cong_them'],
      'toppings': selectedToppings.map((t) => { 
        'ma_topping': t['ma_topping'],
        'gia_tien': t['gia_tien'],
        'so_luong': 1 // Hiện tại mỗi topping khách thường chọn 1 phần
      }).toList(),
    };
  }
}