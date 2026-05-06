import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/order_model.dart';
import '../../services/api_service.dart';
import '../../main.dart';
import 'auth_viewmodel.dart';

class OrderViewModel extends ChangeNotifier {
  final ApiService _apiService = ApiService();

  bool _isLoading = false;
  bool get isLoading => _isLoading;

  String? _errorMessage;
  String? get errorMessage => _errorMessage;

  List<DonHang> _orders = [];
  List<DonHang> get orders => _orders;

  List<DonHang> get dangPhaOrders {
    var list = _orders.where((o) => (o.trangThaiDon == 1 || o.trangThaiDon == 0)).toList();
    list.sort((a, b) {
      if (a.loaiDon != b.loaiDon) return a.loaiDon == 'mang_di' ? -1 : 1;
      final dA = DateTime.tryParse(a.createdAt ?? '') ?? DateTime.now();
      final dB = DateTime.tryParse(b.createdAt ?? '') ?? DateTime.now();
      return dA.compareTo(dB);
    });
    return list;
  }

  List<DonHang> get pendingPaymentOrders {
    return _orders.where((o) => o.trangThaiDon == 2 && o.trangThaiThanhToan == 0).toList();
  }

  List<DonHang> get completedOrders {
    var list = _orders.where((o) => o.trangThaiDon == 2 && o.trangThaiThanhToan == 1).toList();
    list.sort((a, b) {
      final dA = DateTime.tryParse(a.createdAt ?? '') ?? DateTime.now();
      final dB = DateTime.tryParse(b.createdAt ?? '') ?? DateTime.now();
      return dB.compareTo(dA);
    });
    return list;
  }

  Future<void> loadOrders() async {
    _isLoading = true; _errorMessage = null; notifyListeners();
    try { _orders = await _apiService.fetchTodayOrders(); } 
    on UnauthorizedException catch (e) { 
      _errorMessage = e.toString();
      if (navigatorKey.currentContext != null) navigatorKey.currentContext!.read<AuthViewModel>().logout();
    } catch (e) { _errorMessage = e.toString().replaceAll('Exception: ', ''); } 
    finally { _isLoading = false; notifyListeners(); }
  }

  Future<bool> completePreparation(int orderId) async {
    final index = _orders.indexWhere((o) => o.id == orderId);
    if (index == -1) return false;
    final oldOrder = _orders[index];
    _orders[index] = DonHang(
      id: oldOrder.id,
      maDonHang: oldOrder.maDonHang,
      maBan: oldOrder.maBan,
      banId: oldOrder.banId,
      loaiDon: oldOrder.loaiDon,
      tongTien: oldOrder.tongTien,
      phuongThucThanhToan: oldOrder.phuongThucThanhToan,
      trangThaiThanhToan: oldOrder.trangThaiThanhToan,
      trangThaiDon: 2, // Hoàn thành
      createdAt: oldOrder.createdAt,
      chiTietDonHangs: oldOrder.chiTietDonHangs,
      ban: oldOrder.ban,
    );
    notifyListeners();

    try {
      final success = await _apiService.updateOrderStatus(orderId, 2);
      if (!success) { _orders[index] = oldOrder; notifyListeners(); }
      return success;
    } catch (e) {
      _orders[index] = oldOrder; notifyListeners();
      return false;
    }
  }

  Future<bool> confirmPayment(int orderId) async {
    final index = _orders.indexWhere((o) => o.id == orderId);
    if (index == -1) return false;
    final oldOrder = _orders[index];
    _orders[index] = DonHang(
      id: oldOrder.id,
      maDonHang: oldOrder.maDonHang,
      maBan: oldOrder.maBan,
      banId: oldOrder.banId,
      loaiDon: oldOrder.loaiDon,
      tongTien: oldOrder.tongTien,
      phuongThucThanhToan: oldOrder.phuongThucThanhToan,
      trangThaiThanhToan: 1, // Đã thanh toán
      trangThaiDon: 2,
      createdAt: oldOrder.createdAt,
      chiTietDonHangs: oldOrder.chiTietDonHangs,
      ban: oldOrder.ban,
    );
    notifyListeners();

    try {
      final success = await _apiService.updatePaymentAndOrderStatus(orderId, 1, 2);
      if (!success) { _orders[index] = oldOrder; notifyListeners(); }
      return success;
    } catch (e) {
      _orders[index] = oldOrder; notifyListeners();
      return false;
    }
  }

  Future<void> loadOrdersSilently() async {
    try { _orders = await _apiService.fetchTodayOrders(); notifyListeners(); } catch (e) {}
  }

  Future<Map<String, dynamic>> cancelOrder(int orderId, String lyDoHuy) async {
    final index = _orders.indexWhere((o) => o.id == orderId);
    DonHang? oldOrder;
    if (index != -1) {
      oldOrder = _orders[index];
      _orders.removeAt(index);
      notifyListeners();
    }
    try {
      return await _apiService.cancelOrder(orderId, lyDoHuy);
    } catch (e) {
      if (oldOrder != null) { _orders.insert(index, oldOrder); notifyListeners(); }
      return {'success': false, 'message': e.toString()};
    }
  }
}
