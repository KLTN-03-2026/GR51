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
    var list = _orders.where((o) => 
      o.trangThaiDon == 'dang_pha' || 
      o.trangThaiDon == 'cho_xac_nhan'
    ).toList();
    list.sort((a, b) {
      if (a.loaiDon != b.loaiDon) {
         return a.loaiDon == 'mang_di' ? -1 : 1;
      }
      final dA = a.createdAt != null ? DateTime.tryParse(a.createdAt!) ?? DateTime.now() : DateTime.now();
      final dB = b.createdAt != null ? DateTime.tryParse(b.createdAt!) ?? DateTime.now() : DateTime.now();
      return dA.compareTo(dB);
    });
    return list;
  }

  List<DonHang> get pendingPaymentOrders {
    return _orders.where((o) => 
      o.trangThaiDon == 'hoan_thanh_pha_che' && 
      (o.trangThaiThanhToan != 'da_thanh_toan' && o.trangThaiThanhToan != 'đã_thanh_toan')
    ).toList();
  }

  List<DonHang> get completedOrders {
    var list = _orders.where((o) => 
      o.trangThaiDon == 'hoan_thanh' || 
      o.trangThaiDon == 'hoàn_thành' ||
      // Safeguard: Mọi đơn đã thanh toán hoàn toàn nếu lọt khỏi Xử lý đơn đều được đếm vào lịch sử
      o.trangThaiThanhToan == 'da_thanh_toan' || 
      o.trangThaiThanhToan == 'đã_thanh_toan'
    ).toList();
    list.sort((a, b) {
      final dA = a.createdAt != null ? DateTime.tryParse(a.createdAt!) ?? DateTime.now() : DateTime.now();
      final dB = b.createdAt != null ? DateTime.tryParse(b.createdAt!) ?? DateTime.now() : DateTime.now();
      return dB.compareTo(dA); // MỚI NHẤT LÊN ĐẦU
    });
    return list;
  }

  Future<void> loadOrders() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _orders = await _apiService.fetchTodayOrders();
    } on UnauthorizedException catch (e) {
      _errorMessage = e.toString();
      if (navigatorKey.currentContext != null) {
        navigatorKey.currentContext!.read<AuthViewModel>().logout();
      }
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> completePreparation(String maDonHang) async {
    final index = _orders.indexWhere((o) => o.maDonHang == maDonHang);
    DonHang? oldOrder;
    if (index != -1) {
      oldOrder = _orders[index];
      _orders[index] = DonHang(
        maDonHang: oldOrder.maDonHang,
        maBan: oldOrder.maBan,
        loaiDon: oldOrder.loaiDon,
        tongTien: oldOrder.tongTien,
        phuongThucThanhToan: oldOrder.phuongThucThanhToan,
        trangThaiThanhToan: oldOrder.trangThaiThanhToan,
        trangThaiDon: 'hoan_thanh_pha_che',
        createdAt: oldOrder.createdAt,
        updatedAt: DateTime.now().toUtc().toIso8601String(),
        chiTietDonHangs: oldOrder.chiTietDonHangs,
        ban: oldOrder.ban,
      );
      notifyListeners();
    }

    try {
      final success = await _apiService.updateOrderStatus(maDonHang, 'hoan_thanh_pha_che');
      if (!success && oldOrder != null) {
        _orders[index] = oldOrder; // rollback if fail
        notifyListeners();
      }
      return success;
    } on UnauthorizedException catch (_) {
      if (oldOrder != null) {
        _orders[index] = oldOrder;
        notifyListeners();
      }
      if (navigatorKey.currentContext != null) {
        navigatorKey.currentContext!.read<AuthViewModel>().logout();
      }
      return false;
    } catch (e) {
      if (oldOrder != null) {
        _orders[index] = oldOrder;
        notifyListeners();
      }
      return false;
    }
  }

  Future<bool> confirmPayment(String maDonHang) async {
    final index = _orders.indexWhere((o) => o.maDonHang == maDonHang);
    DonHang? oldOrder;
    if (index != -1) {
      oldOrder = _orders[index];
      _orders[index] = DonHang(
        maDonHang: oldOrder.maDonHang,
        maBan: oldOrder.maBan,
        loaiDon: oldOrder.loaiDon,
        tongTien: oldOrder.tongTien,
        phuongThucThanhToan: oldOrder.phuongThucThanhToan,
        trangThaiThanhToan: 'da_thanh_toan',
        trangThaiDon: 'hoan_thanh',
        createdAt: oldOrder.createdAt,
        updatedAt: DateTime.now().toUtc().toIso8601String(),
        chiTietDonHangs: oldOrder.chiTietDonHangs,
        ban: oldOrder.ban,
      );
      notifyListeners();
    }

    try {
      final success = await _apiService.updatePaymentAndOrderStatus(maDonHang, 'da_thanh_toan', 'hoan_thanh');
      if (!success && oldOrder != null) {
        _orders[index] = oldOrder;
        notifyListeners();
      }
      return success;
    } on UnauthorizedException catch (_) {
      if (oldOrder != null) {
        _orders[index] = oldOrder;
        notifyListeners();
      }
      if (navigatorKey.currentContext != null) {
        navigatorKey.currentContext!.read<AuthViewModel>().logout();
      }
      return false;
    } catch (e) {
      if (oldOrder != null) {
        _orders[index] = oldOrder;
        notifyListeners();
      }
      return false;
    }
  }

  Future<void> loadOrdersSilently() async {
    try {
      final fetched = await _apiService.fetchTodayOrders();
      _orders = fetched;
      notifyListeners();
    } catch (e) {
      // ignore silently on polling
    }
  }
}
