import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../services/api_service.dart';
import '../models/khu_vuc_model.dart';
import 'dart:html' as html;

class TableQrView extends StatefulWidget {
  const TableQrView({super.key});

  @override
  State<TableQrView> createState() => _TableQrViewState();
}

class _TableQrViewState extends State<TableQrView> {
  final ApiService _apiService = ApiService();
  bool _isLoading = true;
  String? _error;
  List<KhuVuc> _khuVucs = [];

  // Default IP config text controller for development
  final TextEditingController _domainController = TextEditingController(text: 'http://192.168.100.230:5173');

  @override
  void initState() {
    super.initState();
    _loadTables();
  }

  @override
  void dispose() {
    _domainController.dispose();
    super.dispose();
  }

  Future<void> _loadTables() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final data = await _apiService.fetchTableData();
      setState(() {
        _khuVucs = data;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  void _printQr(String tableId, String tableName, String khuVucName) {
    final url = '${_domainController.text.trim()}/?table=$tableId';
    
    showDialog(
      context: context,
      builder: (context) {
        return Dialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          child: Container(
            width: 400,
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text('Gunpla Coffe', style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: const Color(0xFF6E4423))),
                const SizedBox(height: 8),
                Text('$tableName ($khuVucName)', style: const TextStyle(fontSize: 18, color: Colors.black87)),
                const SizedBox(height: 24),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    border: Border.all(color: Colors.grey[300]!, width: 2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: QrImageView(
                    data: url,
                    version: QrVersions.auto,
                    size: 250.0,
                  ),
                ),
                const SizedBox(height: 24),
                const Text('Quét mã để gọi món ngay tại bàn!', style: TextStyle(color: Colors.grey, fontSize: 14)),
                const SizedBox(height: 32),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: [
                    TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text('Đóng'),
                    ),
                    ElevatedButton.icon(
                      icon: const Icon(Icons.print),
                      label: const Text('In (Bấm Ctrl + P)'),
                      style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF6E4423), foregroundColor: Colors.white),
                      onPressed: () {
                        // Hint user to press Ctrl+P
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('Hãy nhấn Ctrl + P trên trình duyệt Windows để in bảng này.')),
                        );
                      },
                    )
                  ],
                )
              ],
            ),
          ),
        );
      }
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFDFBF7),
      appBar: AppBar(
        title: const Text('Quản Lý Mã QR Bàn', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20)),
        backgroundColor: Colors.white,
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: const Color(0xFF6E4423)),
            onPressed: _loadTables,
            tooltip: 'Làm mới',
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Column(
        children: [
          _buildSettingsBar(),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _buildSettingsBar() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      color: Colors.white,
      child: Row(
        children: [
          const Icon(Icons.link, color: Colors.grey),
          const SizedBox(width: 12),
          const Text('Tên miền hệ thống Web:', style: TextStyle(fontWeight: FontWeight.bold)),
          const SizedBox(width: 16),
          Expanded(
            child: TextField(
              controller: _domainController,
              decoration: const InputDecoration(
                isDense: true,
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                hintText: 'VD: http://192.168.1.5:5173',
              ),
              onChanged: (_) => setState(() {}),
            ),
          ),
          const SizedBox(width: 16),
          Text(
            'Đổi theo IP LAN để test bằng điện thoại',
            style: TextStyle(color: Colors.grey[500], fontSize: 13, fontStyle: FontStyle.italic),
          )
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator(color: const Color(0xFF6E4423)));
    }
    
    if (_error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 64, color: Colors.red),
            const SizedBox(height: 16),
            Text(_error!, style: const TextStyle(color: Colors.red)),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _loadTables,
              child: const Text('Thử lại'),
            )
          ],
        ),
      );
    }

    if (_khuVucs.isEmpty) {
      return const Center(child: Text('Chưa có khu vực / bàn nào được cài đặt.'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(24),
      itemCount: _khuVucs.length,
      itemBuilder: (context, index) {
        final kv = _khuVucs[index];
        return _buildKhuVucSection(kv);
      },
    );
  }

  Widget _buildKhuVucSection(KhuVuc kv) {
    if (kv.bans.isEmpty) return const SizedBox.shrink();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 16.0, top: 8.0),
          child: Row(
            children: [
              const Icon(Icons.other_houses, color: const Color(0xFF6E4423)),
              const SizedBox(width: 8),
              Text(
                kv.tenKhuVuc,
                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.black87),
              ),
              const SizedBox(width: 12),
              Expanded(child: Divider(color: Colors.grey[300], thickness: 1)),
            ],
          ),
        ),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
            maxCrossAxisExtent: 250,
            crossAxisSpacing: 16,
            mainAxisSpacing: 16,
            childAspectRatio: 0.8,
          ),
          itemCount: kv.bans.length,
          itemBuilder: (context, index) {
            final ban = kv.bans[index];
            final qrDataString = '\${_domainController.text.trim()}/?table=\${ban.maBan}';

            return Card(
              elevation: 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      ban.tenBan,
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.grey[200]!),
                      ),
                      child: QrImageView(
                        data: qrDataString,
                        version: QrVersions.auto,
                        size: 130.0,
                      ),
                    ),
                    const Spacer(),
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        icon: const Icon(Icons.print, size: 18),
                        label: const Text('In QR'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: const Color(0xFF6E4423),
                          side: const BorderSide(color: const Color(0xFF6E4423)),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6))
                        ),
                        onPressed: () => _printQr(ban.maBan, ban.tenBan, kv.tenKhuVuc),
                      ),
                    )
                  ],
                ),
              ),
            );
          },
        ),
        const SizedBox(height: 32),
      ],
    );
  }
}
