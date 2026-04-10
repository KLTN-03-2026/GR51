import 'dart:async';
import 'package:flutter/foundation.dart';
import '../../models/order_model.dart';
import '../../services/api_service.dart';

class KdsViewModel extends ChangeNotifier {
  final ApiService _apiService = ApiService();
  List<DonHang> kdsOrders = [];
  bool isLoading = false;
  Timer? _timer;

  Future<void> fetchOrders({bool showLoading = false}) async {
    if (showLoading) {
      isLoading = true;
      notifyListeners();
    }

    try {
      final orders = await _apiService.fetchKdsOrders();
      kdsOrders = orders;
    } catch (e) {
      if (kDebugMode) print('Lỗi fetch KDS: $e');
    } finally {
      if (showLoading) {
        isLoading = false;
      }
      notifyListeners();
    }
  }

  void startPolling() {
    fetchOrders(showLoading: true);
    
    _timer = Timer.periodic(const Duration(seconds: 30), (timer) {
      fetchOrders(showLoading: false);
    });
  }

  Future<void> completeOrder(String maDonHang) async {
    // Xóa thẻ khỏi danh sách cục bộ để tạo hiệu ứng nhanh nhạy
    kdsOrders.removeWhere((order) => order.maDonHang == maDonHang);
    notifyListeners();

    try {
      await _apiService.updateOrderStatus(maDonHang, 'hoan_thanh');
    } catch (e) {
      if (kDebugMode) print('Lỗi hoàn thành món KDS: $e');
      fetchOrders(showLoading: false); // Lấy lại dữ liệu nếu API lỗi
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }
}
