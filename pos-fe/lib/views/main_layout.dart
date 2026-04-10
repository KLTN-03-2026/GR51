import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'menu_view.dart';
import 'orders/order_list_view.dart';
import 'order_history_view.dart';
import 'account_view.dart';
import 'inventory_view.dart';
import 'viewmodels/auth_viewmodel.dart';

class MainLayout extends StatefulWidget {
  const MainLayout({Key? key}) : super(key: key);

  @override
  State<MainLayout> createState() => _MainLayoutState();
}

class _MainLayoutState extends State<MainLayout> {
  int _selectedIndex = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FA),
      body: Row(
        children: [
          _buildLeftSidebar(),
          Expanded(
            child: IndexedStack(
              index: _selectedIndex,
              children: const [
                MenuView(),
                OrderListView(),
                OrderHistoryView(),
                InventoryView(),
                AccountView(),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLeftSidebar() {
    return Container(
      width: 80,
      color: Colors.white,
      child: Column(
        children: [
          const SizedBox(height: 24),
          const Text(
            'SMART\nPOS',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              color: Colors.orange,
              fontSize: 12,
              height: 1.2,
            ),
          ),
          const SizedBox(height: 32),
          Expanded(
            child: SingleChildScrollView(
              child: Column(
                children: [
                  _buildSidebarIcon(Icons.coffee, 0, tooltip: 'Bán hàng'),
                  _buildSidebarIcon(Icons.room_service, 1, tooltip: 'Xử lý đơn'),
                  _buildSidebarIcon(Icons.history, 2, tooltip: 'Lịch sử đơn'),
                  _buildSidebarIcon(Icons.inventory_2_outlined, 3, tooltip: 'Tồn kho'),
                  _buildSidebarIcon(Icons.person_outline, 4, tooltip: 'Tài khoản'),
                  const SizedBox(height: 24),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSidebarIcon(IconData icon, int index, {String tooltip = ''}) {
    final isSelected = _selectedIndex == index;
    return GestureDetector(
      onTap: () {
        if (index >= 0) {
          setState(() {
            _selectedIndex = index;
          });
        }
      },
      child: Tooltip(
        message: tooltip,
        child: Container(
          margin: const EdgeInsets.symmetric(vertical: 12),
          child: Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: isSelected ? Colors.orange : Colors.transparent,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(
              icon,
              color: isSelected ? Colors.white : Colors.grey[400],
              size: 28,
            ),
          ),
        ),
      ),
    );
  }
}
