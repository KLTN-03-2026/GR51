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
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'username': username,
          'password': password,
        }),
      );

      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        if (payload['success'] == true) {
          return payload;
        } else {
          throw Exception(payload['message'] ?? 'Đăng nhập thất bại');
        }
      } else {
        throw Exception('Server trả về lỗi: ${response.statusCode}');
      }
    } catch (e) {
      if (e is Exception) rethrow;
      throw Exception('Lỗi kết nối API: $e');
    }
  }

  Future<void> createOrder(
      List<CartItem> cartItems, 
      String loaiDon, 
      String phuongThucThanhToan,
      String? maBan,
      String trangThaiThanhToan,
      String trangThaiDon) async {
    final url = Uri.parse('$baseUrl/don-hang');
    
    final payload = {
      'loai_don': loaiDon,
      'ma_ban': maBan,
      'phuong_thuc_thanh_toan': phuongThucThanhToan,
      'trang_thai_thanh_toan': trangThaiThanhToan,
      'trang_thai_don': trangThaiDon,
      'chi_tiets': cartItems.map((item) => item.toJson()).toList(),
    };

    try {
      final headers = await _getHeaders();
      final response = await http.post(
        url,
        headers: headers,
        body: json.encode(payload),
      );

      if (response.statusCode == 401) {
        throw UnauthorizedException();
      }

      // Xử lý khi Backend trả về JSON thành công hoặc lỗi có cấu trúc
      if (response.statusCode == 200 || response.statusCode == 201) {
        final data = json.decode(response.body);
        if (data['success'] != true) {
          throw Exception(data['message'] ?? 'Lỗi khi tạo đơn hàng từ Server');
        }
      } else {
        // Xử lý khi Backend trả về lỗi (4xx, 5xx)
        try {
          // Cố gắng đọc JSON lỗi từ Laravel nếu có
          final errorData = json.decode(response.body);
          throw Exception(errorData['message'] ?? 'Server xử lý thất bại (${response.statusCode})');
        } catch (formatException) {
          // Nếu không phải JSON (VD: lỗi máy chủ sập hẳn)
          throw Exception('Lỗi máy chủ (${response.statusCode}). Vui lòng thử lại sau.');
        }
      }
    } catch (e) {
      // Phân loại lỗi mạng và lỗi hệ thống
      if (e is Exception) {
        rethrow; // Ném thẳng exception đã xử lý ở trên ra ngoài View
      }
      throw Exception('Không thể kết nối đến máy chủ. Kiểm tra lại mạng của bạn.');
    }
  } 

  // Thay đổi kiểu trả về thành Map<String, dynamic> để chứa cả danh mục, size và topping
  Future<Map<String, dynamic>> fetchMenu() async {
    final url = Uri.parse('$baseUrl/menu');
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      
      if (response.statusCode == 401) {
        throw UnauthorizedException();
      }

      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        if (payload['success'] == true) {
          // Trả về thẳng cục 'data' (là một Map chứa danh_mucs, sizes, toppings)
          return payload['data'] as Map<String, dynamic>; 
        } else {
          throw Exception(payload['message'] ?? 'Lỗi khi lấy dữ liệu Menu');
        }
      } else {
        throw Exception('Server trả về lỗi: ${response.statusCode}');
      }
    } catch (e) {
      if (e is Exception) rethrow; // Ném lại các lỗi cụ thể như UnauthorizedException
      throw Exception('Lỗi kết nối API: $e');
    }
  }

  Future<List<KhuVuc>> fetchTableData() async {
    final url = Uri.parse('$baseUrl/tables');
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      
      if (response.statusCode == 401) {
        throw UnauthorizedException();
      }

      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        if (payload['success'] == true) {
          final data = payload['data'] as List;
          return data.map((e) => KhuVuc.fromJson(e)).toList();
        } else {
          throw Exception(payload['message'] ?? 'Lỗi khi lấy dữ liệu Bàn');
        }
      } else {
        throw Exception('Server trả về lỗi: ${response.statusCode}');
      }
    } catch (e) {
      if (e is Exception) rethrow;
      throw Exception('Lỗi kết nối API: $e');
    }
  }

  Future<List<DonHang>> fetchTodayOrders() async {
    final url = Uri.parse('$baseUrl/don-hang');
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      if (response.statusCode == 401) {
        throw UnauthorizedException();
      }
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        if (payload['success'] == true) {
          final data = payload['data'] as List;
          return data.map((e) => DonHang.fromJson(e)).toList();
        } else {
          throw Exception(payload['message'] ?? 'Lỗi khi lấy danh sách đơn hàng');
        }
      } else {
        throw Exception('Server trả về lỗi: ${response.statusCode}');
      }
    } catch (e) {
      if (e is Exception) rethrow;
      throw Exception('Lỗi kết nối API: $e');
    }
  }

  Future<List<DonHang>> fetchKdsOrders() async {
    final url = Uri.parse('$baseUrl/don-hang/kds');
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      if (response.statusCode == 401) {
        throw UnauthorizedException();
      }
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        if (payload['success'] == true) {
          final data = payload['data'] as List;
          return data.map((e) => DonHang.fromJson(e)).toList();
        } else {
          throw Exception(payload['message'] ?? 'Lỗi khi lấy danh sách đơn hàng KDS');
        }
      } else {
        throw Exception('Server trả về lỗi: ${response.statusCode}');
      }
    } catch (e) {
      if (e is Exception) rethrow;
      throw Exception('Lỗi kết nối API: $e');
    }
  }

  Future<bool> updateOrderStatus(String maDonHang, String trangThaiMoi) async {
    final url = Uri.parse('$baseUrl/don-hang/$maDonHang');
    try {
      final headers = await _getHeaders();
      final response = await http.put(
        url,
        headers: headers,
        body: json.encode({'trang_thai_don': trangThaiMoi}),
      );
      if (response.statusCode == 401) {
        throw UnauthorizedException();
      }
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        if (payload['success'] == true) {
          return true;
        } else {
          throw Exception(payload['message'] ?? 'Lỗi cập nhật trạng thái');
        }
      } else {
        throw Exception('Server trả về lỗi: ${response.statusCode}');
      }
    } catch (e) {
      if (e is Exception) rethrow;
      throw Exception('Lỗi kết nối API: $e');
    }
  }

  Future<bool> updatePaymentAndOrderStatus(String maDonHang, String trangThaiThanhToan, String trangThaiDon) async {
    final url = Uri.parse('$baseUrl/don-hang/$maDonHang');
    try {
      final headers = await _getHeaders();
      final response = await http.put(
        url,
        headers: headers,
        body: json.encode({
          'trang_thai_thanh_toan': trangThaiThanhToan,
          'trang_thai_don': trangThaiDon
        }),
      );
      if (response.statusCode == 401) {
        throw UnauthorizedException();
      }
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        if (payload['success'] == true) {
          return true;
        } else {
          throw Exception(payload['message'] ?? 'Lỗi cập nhật trạng thái');
        }
      } else {
        throw Exception('Server trả về lỗi: ${response.statusCode}');
      }
    } catch (e) {
      if (e is Exception) rethrow;
      throw Exception('Lỗi kết nối API: $e');
    }
  }

  Future<Map<String, dynamic>> fetchRecipe(String maMon) async {
    final url = Uri.parse('$baseUrl/mon-an/$maMon/cong-thuc');
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      if (response.statusCode == 401) {
        throw UnauthorizedException();
      }
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        if ((payload['success'] == true || payload['status'] == 'success') && payload['data'] != null) {
          return payload['data'] as Map<String, dynamic>;
        }
        // Fallback for direct return format
        return payload as Map<String, dynamic>;
      } else if (response.statusCode == 404) {
         throw Exception('Công thức đang được cập nhật...');
      } else {
        throw Exception('Server trả về lỗi: ${response.statusCode}');
      }
    } catch (e) {
      if (e is Exception) rethrow;
      throw Exception('Lỗi kết nối API: $e');
    }
  }

  Future<List<Map<String, dynamic>>> fetchInventory() async {
    final url = Uri.parse('$baseUrl/kho/ton-kho');
    try {
      final headers = await _getHeaders();
      final response = await http.get(url, headers: headers);
      if (response.statusCode == 401) {
        throw UnauthorizedException();
      }
      if (response.statusCode == 200) {
        final payload = json.decode(response.body);
        if (payload['data'] is List) {
           final data = payload['data'] as List;
           return data.cast<Map<String, dynamic>>();
        }
        if (payload['status'] == 200 && payload.containsKey('data')) {
           final data = payload['data'] as List;
           return data.cast<Map<String, dynamic>>();
        }
        throw Exception(payload['message'] ?? 'Lỗi khi tải dữ liệu tồn kho');
      } else {
        throw Exception('Server trả về lỗi: ${response.statusCode}');
      }
    } catch (e) {
      if (e is Exception) rethrow;
      throw Exception('Lỗi kết nối API: $e');
    }
  }
}
