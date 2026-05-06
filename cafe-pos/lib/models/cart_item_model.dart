import 'menu_model.dart';

class CartItem {
  final Mon mon;
  int soLuong;
  String? ghiChu;
  
  Map<String, dynamic>? selectedSize; 
  List<Map<String, dynamic>> selectedToppings;

  CartItem({
    required this.mon,
    this.soLuong = 1,
    this.ghiChu,
    this.selectedSize,
    this.selectedToppings = const [],
  });

  int get id => mon.id;
  double get price => (thanhTien / soLuong);
  String? get notes => ghiChu;

  double get thanhTien {
    double basePrice = mon.gia;
    if (selectedSize != null && selectedSize!['gia_cong_them'] != null) {
      basePrice += double.tryParse(selectedSize!['gia_cong_them'].toString()) ?? 0;
    }
    for (var topping in selectedToppings) {
      if (topping['gia_tien'] != null) {
        basePrice += double.tryParse(topping['gia_tien'].toString()) ?? 0;
      }
    }
    return basePrice * soLuong;
  }

  Map<String, dynamic> toJson() {
    return {
      'mon_id': mon.id,
      'so_luong': soLuong,
      'don_gia': price,
      'ghi_chu': ghiChu,
      'kich_co_id': selectedSize?['id'],
      'topping_ids': selectedToppings.map((t) => t['id']).toList(),
    };
  }
}