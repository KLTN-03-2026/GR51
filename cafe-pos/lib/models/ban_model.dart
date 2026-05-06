class Ban {
  final int id;
  final String maBan;
  final String tenBan;
  final int khuVucId;
  final int trangThai; // 0: trong, 1: dang_phuc_vu
  final int? soChoNgoi;

  Ban({
    required this.id,
    required this.maBan,
    required this.tenBan,
    required this.khuVucId,
    required this.trangThai,
    this.soChoNgoi,
  });

  factory Ban.fromJson(Map<String, dynamic> json) {
    return Ban(
      id: json['id'] ?? 0,
      maBan: json['ma_ban'] ?? '',
      tenBan: json['ten_ban'] ?? '',
      khuVucId: json['khu_vuc_id'] ?? 0,
      trangThai: int.tryParse(json['trang_thai'].toString()) ?? 0,
      soChoNgoi: json['so_cho_ngoi'],
    );
  }
}
