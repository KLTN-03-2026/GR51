import 'menu_model.dart';
import 'ban_model.dart';

class DonHang {
  final String maDonHang;
  final String? maBan;
  final String loaiDon;
  final double tongTien;
  final String phuongThucThanhToan;
  final String trangThaiThanhToan;
  final String trangThaiDon;
  final String? createdAt;
  final String? updatedAt;
  final int? minutesWaiting;
  final int? priorityScore;
  final List<ChiTietDonHang> chiTietDonHangs;
  final Ban? ban;

  DonHang({
    required this.maDonHang,
    this.maBan,
    required this.loaiDon,
    required this.tongTien,
    required this.phuongThucThanhToan,
    required this.trangThaiThanhToan,
    required this.trangThaiDon,
    this.createdAt,
    this.updatedAt,
    this.minutesWaiting,
    this.priorityScore,
    required this.chiTietDonHangs,
    this.ban,
  });

  factory DonHang.fromJson(Map<String, dynamic> json) {
    var chiTietList = json['chi_tiet_don_hangs'] as List? ?? [];
    List<ChiTietDonHang> chiTiets = chiTietList.map((i) => ChiTietDonHang.fromJson(i)).toList();

    return DonHang(
      maDonHang: json['ma_don_hang'] ?? '',
      maBan: json['ma_ban']?.toString(), // có thể rỗng đối với mang đi
      loaiDon: json['loai_don'] ?? 'tai_ban',
      tongTien: double.tryParse(json['tong_tien']?.toString() ?? '0') ?? 0.0,
      phuongThucThanhToan: json['phuong_thuc_thanh_toan'] ?? '',
      trangThaiThanhToan: json['trang_thai_thanh_toan'] ?? '',
      trangThaiDon: json['trang_thai_don'] ?? '',
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
      minutesWaiting: (json['minutes_waiting'] as num?)?.toInt(),
      priorityScore: (json['priority_score'] as num?)?.toInt(),
      chiTietDonHangs: chiTiets,
      ban: json['ban'] != null ? Ban.fromJson(json['ban']) : null,
    );
  }
}

class ChiTietDonHang {
  final String maChiTiet;
  final String maDonHang;
  final String maMon;
  final int soLuong;
  final double donGia;
  final String? ghiChu;
  final Mon? mon;

  ChiTietDonHang({
    required this.maChiTiet,
    required this.maDonHang,
    required this.maMon,
    required this.soLuong,
    required this.donGia,
    this.ghiChu,
    this.mon,
  });

  factory ChiTietDonHang.fromJson(Map<String, dynamic> json) {
    return ChiTietDonHang(
      maChiTiet: json['ma_chi_tiet'] ?? '',
      maDonHang: json['ma_don_hang'] ?? '',
      maMon: json['ma_mon'] ?? '',
      soLuong: json['so_luong'] ?? 1,
      donGia: double.tryParse(json['don_gia']?.toString() ?? '0') ?? 0.0,
      ghiChu: json['ghi_chu'],
      mon: json['mon'] != null ? Mon.fromJson(json['mon']) : null,
    );
  }
}
