class Ban {
  final String maBan;
  final String tenBan;
  final int khuVucId;
  final String trangThai; // 'trong', 'dang_phuc_vu'
  final int? soChoNgoi;

  Ban({
    required this.maBan,
    required this.tenBan,
    required this.khuVucId,
    required this.trangThai,
    this.soChoNgoi,
  });

  factory Ban.fromJson(Map<String, dynamic> json) {
    return Ban(
      maBan: json['ma_ban'] ?? '',
      tenBan: json['ten_ban'] ?? '',
      khuVucId: json['khu_vuc_id'] ?? 0,
      trangThai: json['trang_thai'] ?? 'trong',
      soChoNgoi: json['so_cho_ngoi'],
    );
  }
}
