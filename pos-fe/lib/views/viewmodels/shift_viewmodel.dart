import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class ShiftViewModel extends ChangeNotifier {
  bool isLoading = true;
  bool isShiftActive = false;
  Map<String, dynamic>? shiftData;
  
  // Bạn nhớ thay bằng base URL và Token thật của app nhé
  final String apiUrl = 'http://127.0.0.1:8000/api/v1'; 

  Future<void> fetchCurrentShift() async {
    isLoading = true;
    notifyListeners();

    try {
      // Gọi API lấy ca làm hiện tại (Thay thế bằng code fetch API chuẩn của app bạn)
      final response = await http.get(
        Uri.parse('$apiUrl/ca-lam/hien-tai'),
        headers: {'Accept': 'application/json'}, // Nhớ truyền Token nếu có
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
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> closeShift() async {
    try {
      final response = await http.post(
        Uri.parse('$apiUrl/ca-lam/ket-ca'),
        headers: {'Accept': 'application/json'},
      );
      if (response.statusCode == 200) {
        isShiftActive = false;
        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }
}