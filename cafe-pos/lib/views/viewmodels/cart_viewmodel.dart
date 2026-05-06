import 'package:flutter/material.dart';
import '../../models/menu_model.dart';
import '../../models/cart_item_model.dart';
import '../../services/api_service.dart';

class CartViewModel extends ChangeNotifier {
  List<CartItem> cartItems = [];

  void addToCart(CartItem newItem) {
    int index = cartItems.indexWhere((existingItem) {
      bool sameMon = existingItem.mon.id == newItem.mon.id;
      bool sameSize = (existingItem.selectedSize?['id'] == newItem.selectedSize?['id']);
      bool sameToppings = _areToppingsEqual(existingItem.selectedToppings, newItem.selectedToppings);
      return sameMon && sameSize && sameToppings;
    });

    if (index >= 0) {
      cartItems[index].soLuong++;
    } else {
      cartItems.add(newItem);
    }
    notifyListeners();
  }

  void updateCartItem(CartItem oldItem, CartItem newItem) {
    int index = cartItems.indexOf(oldItem);
    if (index != -1) {
      cartItems[index] = newItem;
      notifyListeners();
    }
  }

  bool _areToppingsEqual(List<Map<String, dynamic>> list1, List<Map<String, dynamic>> list2) {
    if (list1.length != list2.length) return false;
    var ids1 = list1.map((t) => t['id']).toSet();
    var ids2 = list2.map((t) => t['id']).toSet();
    return ids1.containsAll(ids2) && ids2.containsAll(ids1);
  }

  void increaseQuantity(CartItem item) { item.soLuong++; notifyListeners(); }
  void decreaseQuantity(CartItem item) {
    if (item.soLuong > 1) item.soLuong--;
    else cartItems.remove(item);
    notifyListeners();
  }

  void removeItem(CartItem item) { cartItems.remove(item); notifyListeners(); }
  void clearCart() { cartItems.clear(); notifyListeners(); }

  double get totalPrice => cartItems.fold(0.0, (sum, item) => sum + item.thanhTien);

  final ApiService _apiService = ApiService();
  bool isSubmitting = false;

  Future<bool> submitOrder({
    required String loaiDon,
    required String phuongThucThanhToan,
    int? banId,
    required int trangThaiThanhToan,
    required int trangThaiDon,
  }) async {
    if (cartItems.isEmpty) return false;
    isSubmitting = true; notifyListeners();

    String mappedPhuongThuc = (phuongThucThanhToan.toLowerCase().contains('chuyen_khoan') || phuongThucThanhToan.toLowerCase().contains('chuyển khoản')) ? 'chuyen_khoan' : 'tien_mat';
    String mappedLoaiDon = (loaiDon.toLowerCase().contains('mang_di') || loaiDon.toLowerCase().contains('mang đi')) ? 'mang_di' : 'tai_ban';

    try {
      await _apiService.createOrder(
          cartItems, mappedLoaiDon, mappedPhuongThuc, banId, trangThaiThanhToan, trangThaiDon);
      clearCart();
      isSubmitting = false; notifyListeners();
      return true;
    } catch (e) {
      isSubmitting = false; notifyListeners();
      return false;
    }
  }
}
