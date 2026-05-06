import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/menu_model.dart';
import '../models/cart_item_model.dart';
import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/khu_vuc_model.dart';
import '../models/order_model.dart';

class UnauthorizedException implements Exception {
  final String message;
  UnauthorizedException([this.message = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.']);
  
  @override
  String toString() => message;
}

class ApiService {
  static String get baseUrl {
    if (kIsWeb) {
      // 
      return 'http://127.0.0.1:8000/api/v1';
    } else if (Platform.isAndroid) {
      return 'http://10.0.2.2:8000/api/v1';
    } else {
      return 'http://127.0.0.1:8000/api/v1';
    }
  }

  Future<Map<String, String>> _getHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Future<Map<String, dynamic>> login(String username, String password) async {
    final url = Uri.parse('$baseUrl/login');
    try {
      final response = await http.post(
        url,
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: json.encode({'username': username, 'password': password}),
      );
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        if (payload['success'] == true) return payload;
        throw Exception(payload['message'] ?? 'Đăng nhập thất bại');
      }
      throw Exception('Lỗi server: ${response.statusCode}');
    } catch (e) { rethrow; }
  }

  Future<void> createOrder(
      List<CartItem> cartItems, 
      String loaiDon, 
      String phuongThucThanhToan,
      int? banId,
      int trangThaiThanhToan,
      int trangThaiDon) async {
    final url = Uri.parse('$baseUrl/don-hang');
    final payload = {
      'loai_don': loaiDon,
      'ban_id': banId,
      'phuong_thuc_thanh_toan': phuongThucThanhToan,
      'trang_thai_thanh_toan': trangThaiThanhToan,
      'trang_thai_don': trangThaiDon,
      'chi_tiets': cartItems.map((item) => item.toJson()).toList(),
    };

    try {
      final headers = await _getHeaders();
      final response = await http.post(url, headers: headers, body: json.encode(payload));
      if (response.statusCode == 401) throw UnauthorizedException();
      if (response.statusCode != 200 && response.statusCode != 201) {
        final err = json.decode(response.body);
        throw Exception(err['message'] ?? 'Lỗi tạo đơn');
      }
    } catch (e) { rethrow; }
  } 

  Future<Map<String, dynamic>> fetchMenu() async {
    final url = Uri.parse('$baseUrl/menu');
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      if (response.statusCode == 401) throw UnauthorizedException();
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        return payload['data'] as Map<String, dynamic>; 
      }
      throw Exception('Lỗi lấy menu: ${response.statusCode}');
    } catch (e) { rethrow; }
  }

  Future<List<KhuVuc>> fetchTableData() async {
    final url = Uri.parse('$baseUrl/tables');
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      if (response.statusCode == 401) throw UnauthorizedException();
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        final data = payload['data'] as List;
        return data.map((e) => KhuVuc.fromJson(e)).toList();
      }
      throw Exception('Lỗi lấy bàn');
    } catch (e) { rethrow; }
  }
  Future<List<Map<String, dynamic>>> fetchInventory() async {
    final url = Uri.parse('$baseUrl/kho/ton-kho');
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      if (response.statusCode == 401) throw UnauthorizedException();
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        return List<Map<String, dynamic>>.from(payload['data']);
      }
      throw Exception('Lỗi lấy kho');
    } catch (e) { rethrow; }
  }


  Future<List<DonHang>> fetchTodayOrders() async {
    final url = Uri.parse('$baseUrl/don-hang');
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      if (response.statusCode == 401) throw UnauthorizedException();
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        final data = payload['data'] as List;
        return data.map((e) => DonHang.fromJson(e)).toList();
      }
      throw Exception('Lỗi lấy đơn hàng');
    } catch (e) { rethrow; }
  }

  Future<List<DonHang>> fetchKdsOrders() async {
    final url = Uri.parse('$baseUrl/don-hang/kds');
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      if (response.statusCode == 401) throw UnauthorizedException();
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        final data = payload['data'] as List;
        return data.map((e) => DonHang.fromJson(e)).toList();
      }
      throw Exception('Lỗi lấy KDS');
    } catch (e) { rethrow; }
  }

  Future<Map<String, dynamic>> fetchRecipe(dynamic monId) async {
    final url = Uri.parse('$baseUrl/mon-an/$monId/cong-thuc');
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      if (response.statusCode == 401) throw UnauthorizedException();
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        return payload['data'] ?? {};
      }
      throw Exception('Lỗi lấy công thức');
    } catch (e) { rethrow; }
  }

  Future<bool> updateOrderStatus(dynamic orderId, dynamic trangThaiMoi) async {
    final url = Uri.parse('$baseUrl/don-hang/$orderId');
    try {
      final headers = await _getHeaders();
      final response = await http.put(url, headers: headers, body: json.encode({'trang_thai_don': trangThaiMoi}));
      if (response.statusCode == 401) throw UnauthorizedException();
      return response.statusCode == 200;
    } catch (e) { rethrow; }
  }

  Future<bool> updatePaymentAndOrderStatus(int orderId, int trangThaiThanhToan, int trangThaiDon) async {
    final url = Uri.parse('$baseUrl/don-hang/$orderId');
    try {
      final headers = await _getHeaders();
      final response = await http.put(url, headers: headers, body: json.encode({
        'trang_thai_thanh_toan': trangThaiThanhToan,
        'trang_thai_don': trangThaiDon
      }));
      if (response.statusCode == 401) throw UnauthorizedException();
      return response.statusCode == 200;
    } catch (e) { rethrow; }
  }


  Future<Map<String, dynamic>> cancelOrder(int orderId, String lyDoHuy) async {
    final url = Uri.parse('$baseUrl/don-hang/$orderId/huy');
    try {
      final headers = await _getHeaders();
      final response = await http.put(url, headers: headers, body: json.encode({'ly_do_huy': lyDoHuy}));
      if (response.statusCode == 401) throw UnauthorizedException();
      return json.decode(response.body);
    } catch (e) { rethrow; }
  }
}
