import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/menu_model.dart';
import '../../services/api_service.dart';
import '../../main.dart';
import 'auth_viewmodel.dart';

class MenuViewModel extends ChangeNotifier {
  final ApiService _apiService = ApiService();

  bool isLoading = false;
  String? errorMessage;

  List<DanhMuc> danhMucs = [];
  List<dynamic> listSizes = []; // Hứng dữ liệu Size
  List<dynamic> listToppings = []; // Hứng dữ liệu Topping
  DanhMuc? selectedDanhMuc;

  List<Mon> get filteredItems {
    if (selectedDanhMuc == null) {
      List<Mon> all = [];
      for (var cat in danhMucs) {
        if (cat.mons != null) {
          all.addAll(cat.mons!);
        }
      }
      return all;
    } else {
      return selectedDanhMuc!.mons ?? [];
    }
  }

  Future<void> fetchMenuData() async {
    isLoading = true;
    errorMessage = null;
    notifyListeners();

    try {
      // 1. result bây giờ là Map<String, dynamic> chứa cả 3 mảng
      final result = await _apiService.fetchMenu(); 
      
      // 2. Bóc tách mảng danh_mucs
      if (result['danh_mucs'] != null) {
        final rawDanhMucs = result['danh_mucs'] as List;
        danhMucs = rawDanhMucs.map((e) => DanhMuc.fromJson(e)).toList();
      } else {
        danhMucs = [];
      }

      // 3. Bóc tách mảng sizes và toppings cất đi để lát dùng
      listSizes = result['sizes'] ?? [];
      listToppings = result['toppings'] ?? [];

      selectedDanhMuc = null; // Mặc định chọn "Tất cả"
    } on UnauthorizedException catch (e) {
      errorMessage = e.toString();
      if (navigatorKey.currentContext != null) {
        navigatorKey.currentContext!.read<AuthViewModel>().logout();
      }
    } catch (e) {
      errorMessage = e.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  void selectCategory(DanhMuc? category) {
    selectedDanhMuc = category;
    notifyListeners();
  }
}