import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/khu_vuc_model.dart';
import '../../services/api_service.dart';
import '../../main.dart';
import 'auth_viewmodel.dart';

class TableSelectionViewModel extends ChangeNotifier {
  final ApiService _apiService = ApiService(); // Use singleton or normal instantiation

  bool _isLoading = false;
  bool get isLoading => _isLoading;

  String? _errorMessage;
  String? get errorMessage => _errorMessage;

  List<KhuVuc> _khuVucs = [];
  List<KhuVuc> get khuVucs => _khuVucs;

  KhuVuc? _selectedKhuVuc;
  KhuVuc? get selectedKhuVuc => _selectedKhuVuc;

  Future<void> loadData() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _khuVucs = await _apiService.fetchTableData();
      if (_khuVucs.isNotEmpty) {
        // Mặc định chọn khu vực đầu tiên (ví dụ: Tầng 1)
        _selectedKhuVuc = _khuVucs.first;
      }
    } on UnauthorizedException catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
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

  void selectKhuVuc(KhuVuc khuVuc) {
    _selectedKhuVuc = khuVuc;
    notifyListeners();
  }
}
