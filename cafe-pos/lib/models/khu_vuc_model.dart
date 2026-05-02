import 'ban_model.dart';

class KhuVuc {
  final int id;
  final String tenKhuVuc;
  final List<Ban> bans;

  KhuVuc({
    required this.id,
    required this.tenKhuVuc,
    this.bans = const [],
  });

  factory KhuVuc.fromJson(Map<String, dynamic> json) {
    return KhuVuc(
      id: json['id'] ?? 0,
      tenKhuVuc: json['ten_khu_vuc'] ?? '',
      bans: (json['bans'] as List?)?.map((e) => Ban.fromJson(e)).toList() ?? [],
    );
  }
}
