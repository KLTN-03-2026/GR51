import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/order_model.dart';
import '../../services/api_service.dart';
import '../../main.dart';
import 'auth_viewmodel.dart';
import 'package:dart_pusher_channels/dart_pusher_channels.dart';
import 'package:audioplayers/audioplayers.dart';
import 'dart:convert';

class OrderViewModel extends ChangeNotifier {
  final ApiService _apiService = ApiService();
  final AudioPlayer _audioPlayer = AudioPlayer();
  PusherChannelsClient? _pusherClient;

  OrderViewModel() {
    initPusher();
  }

  void initPusher() {
    try {
      final options = PusherChannelsOptions.fromHost(
        scheme: 'ws',
        host: '127.0.0.1',
        port: 8080,
        key: 'my_reverb_key',
      );

      _pusherClient = PusherChannelsClient.websocket(
        options: options,
        connectionErrorHandler: (exception, trace, client) {
          debugPrint("❌ Pusher Connection Error: $exception");
        },
      );

      final channel = _pusherClient!.publicChannel('pos-orders');

      // Theo dõi trạng thái kết nối
      _pusherClient!.lifecycleStream.listen((state) {
        debugPrint("🌐 Pusher State: $state");
        if (state == PusherChannelsClientLifeCycleState.establishedConnection) {
          debugPrint("🌐 Đã kết nối Reverb, tiến hành subscribe kênh pos-orders...");
          channel.subscribe();
        }
      });
      // Lắng nghe mọi sự kiện trên channel để debug
      channel.bind('new-order').listen((event) {
        debugPrint("🔔 NHẬN ĐƠN HÀNG MỚI (new-order)!");
        loadOrdersSilently();
        playSoundAndNotify();
      });

      channel.bind('App\\Events\\OrderCreated').listen((event) {
        debugPrint("🔔 NHẬN ĐƠN HÀNG MỚI (App\\Events\\OrderCreated)!");
        loadOrdersSilently();
        playSoundAndNotify();
      });

      channel.bind('staff-called').listen((event) {
        String tableName = "không rõ";
        try {
          final dynamic rawData = event.data;
          Map<String, dynamic>? data;
          if (rawData is String) {
            data = jsonDecode(rawData);
          } else if (rawData is Map) {
            data = Map<String, dynamic>.from(rawData);
          }

          if (data != null && data['ban'] != null) {
            tableName = data['ban']['ten_ban'] ?? data['ban']['ma_ban'] ?? "không rõ";
          }
        } catch (e) {}
        playCallStaffSoundAndNotify(tableName);
      });

      channel.bind('App\\Events\\StaffCalled').listen((event) {
        String tableName = "không rõ";
        try {
          final dynamic rawData = event.data;
          Map<String, dynamic>? data;
          if (rawData is String) {
            data = jsonDecode(rawData);
          } else if (rawData is Map) {
            data = Map<String, dynamic>.from(rawData);
          }

          if (data != null && data['ban'] != null) {
            tableName = data['ban']['ten_ban'] ?? data['ban']['ma_ban'] ?? "không rõ";
          }
        } catch (e) {}
        playCallStaffSoundAndNotify(tableName);
      });

      _pusherClient!.connect();
    } catch (e) {
      debugPrint("Pusher Init Error: $e");
    }
  }

  void playSoundAndNotify() async {
    try {
      if (navigatorKey.currentContext != null) {
        ScaffoldMessenger.of(navigatorKey.currentContext!).showSnackBar(
          const SnackBar(
            content: Text('🔔 CÓ ĐƠN HÀNG MỚI TỪ WEB!', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            backgroundColor: Colors.green,
            duration: Duration(seconds: 4),
          ),
        );
      }
      await _audioPlayer.play(UrlSource('https://actions.google.com/sounds/v1/alarms/beep_short.ogg'));
    } catch (e) {
      debugPrint("Audio play error: $e");
    }
  }

  void playCallStaffSoundAndNotify(String tableName) async {
    try {
      if (navigatorKey.currentContext != null) {
        ScaffoldMessenger.of(navigatorKey.currentContext!).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.notifications_active, color: Colors.white),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    '🛎️ BÀN $tableName ĐANG GỌI NHÂN VIÊN!',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                ),
              ],
            ),
            backgroundColor: Colors.orange.shade800,
            duration: const Duration(seconds: 10),
            action: SnackBarAction(
              label: 'ĐÃ XONG',
              textColor: Colors.white,
              onPressed: () {},
            ),
          ),
        );
      }
      // Sử dụng âm thanh tiếng chuông (Bell) khác để phân biệt với tiếng đơn hàng
      await _audioPlayer.play(AssetSource('sounds/uia_sound.mp3'));
    } catch (e) {
      debugPrint("Audio play error: $e");
    }
  }

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
      ghiChu: oldOrder.ghiChu,
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
      ghiChu: oldOrder.ghiChu,
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
