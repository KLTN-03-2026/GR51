import 'package:flutter/material.dart';
import '../../models/menu_model.dart';
import '../../models/cart_item_model.dart';
import '../../services/api_service.dart';
import 'package:provider/provider.dart';
import '../../main.dart';
import 'auth_viewmodel.dart';

class CartViewModel extends ChangeNotifier {
  List<CartItem> cartItems = [];

  // Hàm thêm món mới đã được NÂNG CẤP để xử lý Size và Topping
  void addToCart(CartItem newItem) {
    // Tìm xem trong giỏ hàng có cái ly nào giống Y HỆT ly mới không
    int index = cartItems.indexWhere((existingItem) {
      
      // 1. Phải cùng mã món
      bool sameMon = existingItem.mon.maMon == newItem.mon.maMon;
      
      // 2. Phải cùng Size (Hoặc cả 2 đều không có Size)
      bool sameSize = (existingItem.selectedSize?['ma_kich_co'] == newItem.selectedSize?['ma_kich_co']);
      
      // 3. Phải cùng y hệt Topping
      bool sameToppings = _areToppingsEqual(existingItem.selectedToppings, newItem.selectedToppings);
      
      // Nếu giống cả 3 thứ trên, thì mới coi là 1 loại để cộng dồn
      return sameMon && sameSize && sameToppings;
    });

    if (index >= 0) {
      // Nếu tìm thấy ly y hệt, chỉ việc tăng số lượng lên
      cartItems[index].soLuong++;
    } else {
      // Nếu không giống (khác size, khác topping, hoặc món mới), thêm thành 1 dòng mới tinh
      cartItems.add(newItem);
    }
    
    notifyListeners();
  }

  // Hàm hỗ trợ: Kiểm tra xem 2 danh sách topping có giống nhau không
  bool _areToppingsEqual(List<Map<String, dynamic>> list1, List<Map<String, dynamic>> list2) {
    if (list1.length != list2.length) return false;
    
    // Lấy ra danh sách các mã topping để so sánh cho dễ
    var ids1 = list1.map((t) => t['ma_topping']).toSet();
    var ids2 = list2.map((t) => t['ma_topping']).toSet();
    
    // Nếu cả 2 set mã topping chứa các phần tử giống y hệt nhau
    return ids1.containsAll(ids2) && ids2.containsAll(ids1);
  }

  void increaseQuantity(CartItem item) {
    item.soLuong++;
    notifyListeners();
  }

  void decreaseQuantity(CartItem item) {
    if (item.soLuong > 1) {
      item.soLuong--;
    } else {
      cartItems.remove(item);
    }
    notifyListeners();
  }

  void updateCartItem(CartItem oldItem, CartItem newItem) {
    newItem.soLuong = oldItem.soLuong;
    cartItems.remove(oldItem);
    addToCart(newItem);
  }

  void removeItem(CartItem item) {
    cartItems.remove(item);
    notifyListeners();
  }

  void clearCart() {
    cartItems.clear();
    notifyListeners();
  }

  void updateItemNote(CartItem item, String note) {
    if (note.trim().isEmpty) {
      item.ghiChu = null;
    } else {
      item.ghiChu = note.trim();
    }
    notifyListeners();
  }

  double get totalPrice {
    return cartItems.fold(0.0, (sum, item) => sum + item.thanhTien);
  }

  final ApiService _apiService = ApiService();
  bool isSubmitting = false;

  Future<bool> submitOrder({
    required String loaiDon,
    required String phuongThucThanhToan,
    String? maBan,
    required String trangThaiThanhToan,
    required String trangThaiDon,
  }) async {
    if (cartItems.isEmpty) return false;

    isSubmitting = true;
    notifyListeners();

    // 1. Mapping phuong_thuc_thanh_toan
    String mappedPhuongThuc = phuongThucThanhToan;
    if (phuongThucThanhToan == 'Tiền mặt') mappedPhuongThuc = 'tien_mat';
    if (phuongThucThanhToan == 'Chuyển khoản') mappedPhuongThuc = 'chuyen_khoan';

    // 2. Mapping trang_thai_thanh_toan
    String mappedTrangThaiTT = trangThaiThanhToan;
    if (trangThaiThanhToan == 'chưa_thanh_toan' || trangThaiThanhToan == 'Chưa thanh toán') mappedTrangThaiTT = 'chua_thanh_toan';
    if (trangThaiThanhToan == 'đã_thanh_toan' || trangThaiThanhToan == 'Đã thanh toán') mappedTrangThaiTT = 'da_thanh_toan';

    // 3. Mapping loai_don
    String mappedLoaiDon = loaiDon;
    if (loaiDon == 'Mang đi') mappedLoaiDon = 'mang_di';
    // Đảm bảo là 'tai_ban' nếu là bàn được giao 
    if (loaiDon != 'mang_di' && loaiDon != 'Mang đi') mappedLoaiDon = 'tai_ban';

    // IN PAYLOAD TEST
    // print('========= [TEST PAYLOAD TẠO ĐƠN] =========');
    // print('loai_don: $mappedLoaiDon');
    // print('phuong_thuc_thanh_toan: $mappedPhuongThuc');
    // print('trang_thai_thanh_toan: $mappedTrangThaiTT');
    // print('trang_thai_don: $trangThaiDon');
    // print('ma_ban: $maBan');
    // print('cartItems count: ${cartItems.length}');
    // print('==========================================');

    try {
      await _apiService.createOrder(
          cartItems, 
          mappedLoaiDon, 
          mappedPhuongThuc, 
          maBan, 
          mappedTrangThaiTT, 
          trangThaiDon);
      clearCart();
      
      isSubmitting = false;
      notifyListeners();
      return true;
    } on UnauthorizedException catch (_) {
      isSubmitting = false;
      notifyListeners();
      if (navigatorKey.currentContext != null) {
        navigatorKey.currentContext!.read<AuthViewModel>().logout();
      }
      return false;
    } catch (e) {
      isSubmitting = false;
      notifyListeners();
      return false;
    }
  }
}
