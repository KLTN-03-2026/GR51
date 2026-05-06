class DanhMuc {
  final int id;
  final String tenDanhMuc;
  final List<Mon>? mons;

  DanhMuc({
    required this.id,
    required this.tenDanhMuc,
    this.mons,
  });

  factory DanhMuc.fromJson(Map<String, dynamic> json) {
    var monList = json['mons'] as List?;
    List<Mon>? parsedMons;
    if (monList != null) {
      parsedMons = monList.map((m) => Mon.fromJson(m)).toList();
    }

    return DanhMuc(
      id: json['id'] ?? 0,
      tenDanhMuc: json['ten_danh_muc'] ?? '',
      mons: parsedMons,
    );
  }
}

class Mon {
  final int id;
  final String maMon;
  final String tenMon;
  final double gia;
  final String? hinhAnh;
  final int danhMucId;
  final bool isHetHang;

  Mon({
    required this.id,
    required this.maMon,
    required this.tenMon,
    required this.gia,
    this.hinhAnh,
    required this.danhMucId,
    this.isHetHang = false,
  });

  factory Mon.fromJson(Map<String, dynamic> json) {
    return Mon(
      id: json['id'] ?? 0,
      maMon: json['ma_mon']?.toString() ?? '',
      tenMon: json['ten_mon'] ?? '',
      gia: double.tryParse(json['gia_ban'].toString()) ?? double.tryParse(json['gia'].toString()) ?? 0.0,
      hinhAnh: json['hinh_anh'],
      danhMucId: int.tryParse(json['danh_muc_id']?.toString() ?? '') ?? 0,
      isHetHang: json['is_het_hang'] == true || json['is_het_hang'] == 1,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'ma_mon': maMon,
      'ten_mon': tenMon,
      'gia_ban': gia,
      'danh_muc_id': danhMucId,
    };
  }
}
