import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../../services/api_service.dart';
import '../../main.dart';
import '../auth/login_view.dart';

class AuthViewModel extends ChangeNotifier {
  final ApiService _apiService = ApiService();

  bool _isLoading = false;
  bool get isLoading => _isLoading;

  String? _errorMessage;
  String? get errorMessage => _errorMessage;

  // --- BIẾN CHO CA LÀM ---
  bool isShiftActive = false;
  Map<String, dynamic>? shiftData;

  // ==========================================
  // 1. CÁC HÀM XÁC THỰC (AUTH)
  // ==========================================
  Future<bool> login(String username, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await _apiService.login(username, password);
      final token = response['data']?['token'];
      
      if (token == null || token.isEmpty) {
        throw Exception('Không nhận được Token từ máy chủ.');
      }

      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('auth_token', token.toString());
      
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _isLoading = false;
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    isShiftActive = false;
    shiftData = null;
    notifyListeners();

    final context = navigatorKey.currentContext;
    if (context != null) {
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (context) => const LoginView()),
        (route) => false,
      );
    }
  }

  // ==========================================
  // 2. CÁC HÀM QUẢN LÝ CA LÀM (SHIFT)
  // ==========================================

  /// Lấy headers xác thực
  Future<Map<String, String>> _getAuthHeaders() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token') ?? '';
    return {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  /// Lấy thông tin ca làm hiện tại + thống kê doanh thu
  Future<void> fetchCurrentShift() async {
    _isLoading = true;
    notifyListeners();

    try {
      final headers = await _getAuthHeaders();
      final response = await http.get(
        Uri.parse('${ApiService.baseUrl}/ca-lam/hien-tai'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body)['data'];
        shiftData = data;
        isShiftActive = true;
      } else {
        isShiftActive = false;
        shiftData = null;
      }
    } catch (e) {
      print('Lỗi lấy ca làm: $e');
      isShiftActive = false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Kết ca - truyền tiền mặt thực tế và ghi chú
  Future<Map<String, dynamic>> closeShift({
    required double tienMatThucTe,
    String? ghiChu,
  }) async {
    try {
      final headers = await _getAuthHeaders();
      final response = await http.post(
        Uri.parse('${ApiService.baseUrl}/ca-lam/ket-ca'),
        headers: headers,
        body: json.encode({
          'tien_mat_thuc_te': tienMatThucTe,
          'ghi_chu': ghiChu,
        }),
      );

      final body = json.decode(response.body);

      if (response.statusCode == 200) {
        isShiftActive = false;
        shiftData = null;
        notifyListeners();
        return {'success': true, 'message': body['message'] ?? 'Kết ca thành công!'};
      } else {
        // Trả về message lỗi từ backend (ví dụ: còn đơn đang xử lý)
        return {
          'success': false,
          'message': body['message'] ?? 'Lỗi kết ca.',
          'don_dang_xu_ly': body['don_dang_xu_ly'],
        };
      }
    } catch (e) {
      print('Lỗi kết ca: $e');
      return {'success': false, 'message': 'Lỗi kết nối. Vui lòng thử lại.'};
    }
  }

  /// Mở ca mới
  Future<bool> openShift(double startingCash) async {
    try {
      final headers = await _getAuthHeaders();
      final response = await http.post(
        Uri.parse('${ApiService.baseUrl}/ca-lam/mo-ca'),
        headers: headers,
        body: json.encode({
          'tien_mat_dau_ca': startingCash
        }),
      );

      if (response.statusCode == 201 || response.statusCode == 200) {
        // Tự động kéo dữ liệu ca làm mới về để UI tự động đổi sang màn hình báo cáo
        await fetchCurrentShift(); 
        return true;
      }
      return false;
    } catch (e) {
      print('Lỗi mở ca: $e');
      return false;
    }
  }
}
