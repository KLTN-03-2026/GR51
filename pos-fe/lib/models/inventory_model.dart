class InventoryItem {
  final String maNguyenLieu;
  final String tenNguyenLieu;
  final double tonKho;
  final double mucCanhBao;
  final String donViTinh;

  InventoryItem({
    required this.maNguyenLieu,
    required this.tenNguyenLieu,
    required this.tonKho,
    required this.mucCanhBao,
    required this.donViTinh,
  });

  factory InventoryItem.fromJson(Map<String, dynamic> json) {
    return InventoryItem(
      maNguyenLieu: json['ma_nguyen_lieu'] ?? '',
      tenNguyenLieu: json['ten_nguyen_lieu'] ?? '',
      tonKho: double.tryParse(json['ton_kho']?.toString() ?? '0') ?? 0.0,
      mucCanhBao: double.tryParse(json['muc_canh_bao']?.toString() ?? '0') ?? 0.0,
      donViTinh: json['don_vi_tinh'] ?? '',
    );
  }
}
