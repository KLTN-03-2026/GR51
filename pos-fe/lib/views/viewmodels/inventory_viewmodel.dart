import 'package:flutter/material.dart';
import '../../models/inventory_model.dart';
import '../../services/api_service.dart';

class InventoryViewModel extends ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<InventoryItem> _items = [];
  List<InventoryItem> _filteredItems = [];
  bool _isLoading = false;
  String? _errorMessage;

  List<InventoryItem> get filteredItems => _filteredItems;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  Future<void> fetchInventory() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final data = await _apiService.fetchInventory();
      _items = data.map((json) => InventoryItem.fromJson(json)).toList();
      _filteredItems = List.from(_items);
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void search(String query) {
    if (query.isEmpty) {
      _filteredItems = List.from(_items);
    } else {
      final lowerQuery = query.toLowerCase();
      _filteredItems = _items.where((item) {
        return item.tenNguyenLieu.toLowerCase().contains(lowerQuery);
      }).toList();
    }
    notifyListeners();
  }
}
