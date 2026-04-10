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
  final String apiUrl = 'http://127.0.0.1:8000/api/v1'; // Đảm bảo URL này đúng với máy bạn

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
  Future<void> fetchCurrentShift() async {
    _isLoading = true;
    notifyListeners();

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token') ?? '';

      final response = await http.get(
        Uri.parse('$apiUrl/ca-lam/hien-tai'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
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

  Future<bool> closeShift() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token') ?? '';

      final response = await http.post(
        Uri.parse('$apiUrl/ca-lam/ket-ca'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );
      if (response.statusCode == 200) {
        isShiftActive = false;
        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      print('Lỗi kết ca: $e');
      return false;
    }
  }

  // Hàm mở ca bây giờ đã nằm GỌN GÀNG BÊN TRONG class AuthViewModel
  Future<bool> openShift(double startingCash) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token') ?? '';

      final response = await http.post(
        Uri.parse('$apiUrl/ca-lam/mo-ca'),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
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
} // <-- Đây mới là dấu ngoặc đóng cuối cùng của Class!