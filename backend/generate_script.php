<?php
$migrationsDir = __DIR__ . '/database/migrations';
$modelsDir = __DIR__ . '/app/Models';

if (!is_dir($modelsDir)) mkdir($modelsDir, 0777, true);
if (!is_dir($migrationsDir)) mkdir($migrationsDir, 0777, true);

// Remove existing migrations
$files = glob($migrationsDir . '/*_create_*_table.php');
foreach ($files as $file) {
    if(is_file($file)) unlink($file);
}

$tables = [
    [
        'table' => 'nhan_sus',
        'model' => 'NhanSu',
        'fields' => "
            \$table->string('ma_nhan_su')->primary();
            \$table->string('ten_dang_nhap')->unique();
            \$table->string('mat_khau');
            \$table->string('ma_pin');
            \$table->string('ho_ten');
            \$table->string('so_dien_thoai');
            \$table->string('vai_tro');
            \$table->string('trang_thai');
        ",
        'model_content' => "
use Illuminate\Foundation\Auth\User as Authenticatable;
class NhanSu extends Authenticatable {
    protected \$table = 'nhan_sus';
    protected \$primaryKey = 'ma_nhan_su';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_nhan_su', 'ten_dang_nhap', 'mat_khau', 'ma_pin', 'ho_ten', 'so_dien_thoai', 'vai_tro', 'trang_thai'];
    
    public function caLams() { return \$this->hasMany(CaLam::class, 'ma_nhan_su', 'ma_nhan_su'); }
    public function donHangs() { return \$this->hasMany(DonHang::class, 'ma_nhan_su', 'ma_nhan_su'); }
    public function lichSuKhos() { return \$this->hasMany(LichSuKho::class, 'ma_nhan_su', 'ma_nhan_su'); }
}
"
    ],
    [
        'table' => 'khu_vucs',
        'model' => 'KhuVuc',
        'fields' => "
            \$table->string('ma_khu_vuc')->primary();
            \$table->string('ten_khu_vuc');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class KhuVuc extends Model {
    protected \$table = 'khu_vucs';
    protected \$primaryKey = 'ma_khu_vuc';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_khu_vuc', 'ten_khu_vuc'];
    
    public function bans() { return \$this->hasMany(Ban::class, 'ma_khu_vuc', 'ma_khu_vuc'); }
}
"
    ],
    [
        'table' => 'bans',
        'model' => 'Ban',
        'fields' => "
            \$table->string('ma_ban')->primary();
            \$table->string('ten_ban');
            \$table->string('ma_khu_vuc');
            \$table->string('ma_qr');
            \$table->string('trang_thai');
            \$table->foreign('ma_khu_vuc')->references('ma_khu_vuc')->on('khu_vucs');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class Ban extends Model {
    protected \$table = 'bans';
    protected \$primaryKey = 'ma_ban';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_ban', 'ten_ban', 'ma_khu_vuc', 'ma_qr', 'trang_thai'];
    
    public function khuVuc() { return \$this->belongsTo(KhuVuc::class, 'ma_khu_vuc', 'ma_khu_vuc'); }
    public function donHangs() { return \$this->hasMany(DonHang::class, 'ma_ban', 'ma_ban'); }
}
"
    ],
    [
        'table' => 'danh_mucs',
        'model' => 'DanhMuc',
        'fields' => "
            \$table->string('ma_danh_muc')->primary();
            \$table->string('ten_danh_muc');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class DanhMuc extends Model {
    protected \$table = 'danh_mucs';
    protected \$primaryKey = 'ma_danh_muc';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_danh_muc', 'ten_danh_muc'];
    
    public function mons() { return \$this->hasMany(Mon::class, 'ma_danh_muc', 'ma_danh_muc'); }
}
"
    ],
    [
        'table' => 'mons',
        'model' => 'Mon',
        'fields' => "
            \$table->string('ma_mon')->primary();
            \$table->string('ma_danh_muc');
            \$table->string('ten_mon');
            \$table->string('hinh_anh')->nullable();
            \$table->decimal('gia_ban', 15, 2);
            \$table->text('cong_thuc')->nullable();
            \$table->string('trang_thai');
            \$table->foreign('ma_danh_muc')->references('ma_danh_muc')->on('danh_mucs');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class Mon extends Model {
    protected \$table = 'mons';
    protected \$primaryKey = 'ma_mon';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_mon', 'ma_danh_muc', 'ten_mon', 'hinh_anh', 'gia_ban', 'cong_thuc', 'trang_thai'];
    
    public function danhMuc() { return \$this->belongsTo(DanhMuc::class, 'ma_danh_muc', 'ma_danh_muc'); }
    public function chiTietDonHangs() { return \$this->hasMany(ChiTietDonHang::class, 'ma_mon', 'ma_mon'); }
    public function congThucs() { return \$this->hasMany(CongThuc::class, 'ma_mon', 'ma_mon'); }
}
"
    ],
    [
        'table' => 'kich_cos',
        'model' => 'KichCo',
        'fields' => "
            \$table->string('ma_kich_co')->primary();
            \$table->string('ten_kich_co');
            \$table->decimal('gia_cong_them', 15, 2);
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class KichCo extends Model {
    protected \$table = 'kich_cos';
    protected \$primaryKey = 'ma_kich_co';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_kich_co', 'ten_kich_co', 'gia_cong_them'];
    
    public function chiTietDonHangs() { return \$this->hasMany(ChiTietDonHang::class, 'ma_kich_co', 'ma_kich_co'); }
}
"
    ],
    [
        'table' => 'toppings',
        'model' => 'Topping',
        'fields' => "
            \$table->string('ma_topping')->primary();
            \$table->string('ten_topping');
            \$table->string('hinh_anh')->nullable();
            \$table->decimal('gia_tien', 15, 2);
            \$table->string('trang_thai');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class Topping extends Model {
    protected \$table = 'toppings';
    protected \$primaryKey = 'ma_topping';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_topping', 'ten_topping', 'hinh_anh', 'gia_tien', 'trang_thai'];
    
    public function chiTietToppings() { return \$this->hasMany(ChiTietTopping::class, 'ma_topping', 'ma_topping'); }
}
"
    ],
    [
        'table' => 'nguyen_lieus',
        'model' => 'NguyenLieu',
        'fields' => "
            \$table->string('ma_nguyen_lieu')->primary();
            \$table->string('ten_nguyen_lieu');
            \$table->string('hinh_anh')->nullable();
            \$table->string('don_vi_tinh');
            \$table->decimal('ton_kho', 15, 2);
            \$table->decimal('muc_canh_bao', 15, 2);
            \$table->string('trang_thai');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class NguyenLieu extends Model {
    protected \$table = 'nguyen_lieus';
    protected \$primaryKey = 'ma_nguyen_lieu';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_nguyen_lieu', 'ten_nguyen_lieu', 'hinh_anh', 'don_vi_tinh', 'ton_kho', 'muc_canh_bao', 'trang_thai'];
    
    public function congThucs() { return \$this->hasMany(CongThuc::class, 'ma_nguyen_lieu', 'ma_nguyen_lieu'); }
    public function lichSuKhos() { return \$this->hasMany(LichSuKho::class, 'ma_nguyen_lieu', 'ma_nguyen_lieu'); }
}
"
    ],
    [
        'table' => 'ca_lams',
        'model' => 'CaLam',
        'fields' => "
            \$table->string('ma_ca_lam')->primary();
            \$table->string('ma_nhan_su');
            \$table->dateTime('thoi_gian_bat_dau');
            \$table->dateTime('thoi_gian_ket_thuc')->nullable();
            \$table->decimal('tien_mat_dau_ca', 15, 2);
            \$table->decimal('tien_mat_he_thong', 15, 2);
            \$table->decimal('tien_mat_thuc_te', 15, 2)->nullable();
            \$table->decimal('tong_doanh_thu', 15, 2)->default(0);
            \$table->text('ghi_chu')->nullable();
            \$table->string('trang_thai');
            \$table->foreign('ma_nhan_su')->references('ma_nhan_su')->on('nhan_sus');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class CaLam extends Model {
    protected \$table = 'ca_lams';
    protected \$primaryKey = 'ma_ca_lam';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_ca_lam', 'ma_nhan_su', 'thoi_gian_bat_dau', 'thoi_gian_ket_thuc', 'tien_mat_dau_ca', 'tien_mat_he_thong', 'tien_mat_thuc_te', 'tong_doanh_thu', 'ghi_chu', 'trang_thai'];
    
    public function nhanSu() { return \$this->belongsTo(NhanSu::class, 'ma_nhan_su', 'ma_nhan_su'); }
}
"
    ],
    [
        'table' => 'don_hangs',
        'model' => 'DonHang',
        'fields' => "
            \$table->string('ma_don_hang')->primary();
            \$table->string('ma_ban')->nullable();
            \$table->string('ma_nhan_su')->nullable();
            \$table->string('loai_don');
            \$table->decimal('tong_tien', 15, 2);
            \$table->string('phuong_thuc_thanh_toan');
            \$table->string('trang_thai_thanh_toan');
            \$table->string('trang_thai_don');
            \$table->foreign('ma_ban')->references('ma_ban')->on('bans');
            \$table->foreign('ma_nhan_su')->references('ma_nhan_su')->on('nhan_sus');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class DonHang extends Model {
    protected \$table = 'don_hangs';
    protected \$primaryKey = 'ma_don_hang';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_don_hang', 'ma_ban', 'ma_nhan_su', 'loai_don', 'tong_tien', 'phuong_thuc_thanh_toan', 'trang_thai_thanh_toan', 'trang_thai_don'];
    
    public function ban() { return \$this->belongsTo(Ban::class, 'ma_ban', 'ma_ban'); }
    public function nhanSu() { return \$this->belongsTo(NhanSu::class, 'ma_nhan_su', 'ma_nhan_su'); }
    public function chiTietDonHangs() { return \$this->hasMany(ChiTietDonHang::class, 'ma_don_hang', 'ma_don_hang'); }
    public function danhGia() { return \$this->hasOne(DanhGia::class, 'ma_don_hang', 'ma_don_hang'); }
}
"
    ],
    [
        'table' => 'chi_tiet_don_hangs',
        'model' => 'ChiTietDonHang',
        'fields' => "
            \$table->string('ma_chi_tiet')->primary();
            \$table->string('ma_don_hang');
            \$table->string('ma_mon');
            \$table->string('ma_kich_co')->nullable();
            \$table->integer('so_luong');
            \$table->string('ghi_chu')->nullable();
            \$table->decimal('don_gia', 15, 2);
            \$table->foreign('ma_don_hang')->references('ma_don_hang')->on('don_hangs');
            \$table->foreign('ma_mon')->references('ma_mon')->on('mons');
            \$table->foreign('ma_kich_co')->references('ma_kich_co')->on('kich_cos');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class ChiTietDonHang extends Model {
    protected \$table = 'chi_tiet_don_hangs';
    protected \$primaryKey = 'ma_chi_tiet';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_chi_tiet', 'ma_don_hang', 'ma_mon', 'ma_kich_co', 'so_luong', 'ghi_chu', 'don_gia'];
    
    public function donHang() { return \$this->belongsTo(DonHang::class, 'ma_don_hang', 'ma_don_hang'); }
    public function mon() { return \$this->belongsTo(Mon::class, 'ma_mon', 'ma_mon'); }
    public function kichCo() { return \$this->belongsTo(KichCo::class, 'ma_kich_co', 'ma_kich_co'); }
    public function chiTietToppings() { return \$this->hasMany(ChiTietTopping::class, 'ma_chi_tiet', 'ma_chi_tiet'); }
}
"
    ],
    [
        'table' => 'chi_tiet_toppings',
        'model' => 'ChiTietTopping',
        'fields' => "
            \$table->string('ma_chi_tiet_topping')->primary();
            \$table->string('ma_chi_tiet');
            \$table->string('ma_topping');
            \$table->foreign('ma_chi_tiet')->references('ma_chi_tiet')->on('chi_tiet_don_hangs');
            \$table->foreign('ma_topping')->references('ma_topping')->on('toppings');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class ChiTietTopping extends Model {
    protected \$table = 'chi_tiet_toppings';
    protected \$primaryKey = 'ma_chi_tiet_topping';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_chi_tiet_topping', 'ma_chi_tiet', 'ma_topping'];
    
    public function chiTietDonHang() { return \$this->belongsTo(ChiTietDonHang::class, 'ma_chi_tiet', 'ma_chi_tiet'); }
    public function topping() { return \$this->belongsTo(Topping::class, 'ma_topping', 'ma_topping'); }
}
"
    ],
    [
        'table' => 'danh_gias',
        'model' => 'DanhGia',
        'fields' => "
            \$table->string('ma_danh_gia')->primary();
            \$table->string('ma_don_hang');
            \$table->integer('so_sao');
            \$table->text('binh_luan')->nullable();
            \$table->foreign('ma_don_hang')->references('ma_don_hang')->on('don_hangs');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class DanhGia extends Model {
    protected \$table = 'danh_gias';
    protected \$primaryKey = 'ma_danh_gia';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_danh_gia', 'ma_don_hang', 'so_sao', 'binh_luan'];
    
    public function donHang() { return \$this->belongsTo(DonHang::class, 'ma_don_hang', 'ma_don_hang'); }
}
"
    ],
    [
        'table' => 'cong_thucs',
        'model' => 'CongThuc',
        'fields' => "
            \$table->string('ma_mon');
            \$table->string('ma_nguyen_lieu');
            \$table->decimal('so_luong_can', 10, 2);
            \$table->primary(['ma_mon', 'ma_nguyen_lieu']);
            \$table->foreign('ma_mon')->references('ma_mon')->on('mons');
            \$table->foreign('ma_nguyen_lieu')->references('ma_nguyen_lieu')->on('nguyen_lieus');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class CongThuc extends Model {
    protected \$table = 'cong_thucs';
    public \$incrementing = false;
    protected \$fillable = ['ma_mon', 'ma_nguyen_lieu', 'so_luong_can'];
    
    public function mon() { return \$this->belongsTo(Mon::class, 'ma_mon', 'ma_mon'); }
    public function nguyenLieu() { return \$this->belongsTo(NguyenLieu::class, 'ma_nguyen_lieu', 'ma_nguyen_lieu'); }
    
    // For composite primary key support in eloquent we just disable auto-incrementing
    // and avoid using find() without custom queries.
}
"
    ],
    [
        'table' => 'lich_su_khos',
        'model' => 'LichSuKho',
        'fields' => "
            \$table->string('ma_ls_kho')->primary();
            \$table->string('ma_nguyen_lieu');
            \$table->string('ma_nhan_su')->nullable();
            \$table->string('loai_giao_dich');
            \$table->decimal('so_luong_thay_doi', 15, 2);
            \$table->foreign('ma_nguyen_lieu')->references('ma_nguyen_lieu')->on('nguyen_lieus');
            \$table->foreign('ma_nhan_su')->references('ma_nhan_su')->on('nhan_sus');
        ",
        'model_content' => "
use Illuminate\Database\Eloquent\Model;
class LichSuKho extends Model {
    protected \$table = 'lich_su_khos';
    protected \$primaryKey = 'ma_ls_kho';
    protected \$keyType = 'string';
    public \$incrementing = false;
    protected \$fillable = ['ma_ls_kho', 'ma_nguyen_lieu', 'ma_nhan_su', 'loai_giao_dich', 'so_luong_thay_doi'];
    
    public function nguyenLieu() { return \$this->belongsTo(NguyenLieu::class, 'ma_nguyen_lieu', 'ma_nguyen_lieu'); }
    public function nhanSu() { return \$this->belongsTo(NhanSu::class, 'ma_nhan_su', 'ma_nhan_su'); }
}
"
    ]
];

$index = 1;
foreach ($tables as $t) {
    $tableName = $t['table'];
    $modelName = $t['model'];
    $fields = $t['fields'];
    $modelContentStr = $t['model_content'];

    // Create Migration File
    $timestamp = date('Y_m_d_His', time() + $index);
    $migrationFile = $migrationsDir . '/' . $timestamp . '_create_' . $tableName . '_table.php';
    $migrationContent = "<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('{$tableName}', function (Blueprint \$table) {
{$fields}            \$table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('{$tableName}');
    }
};
";
    file_put_contents($migrationFile, $migrationContent);

    // Create Model File
    $modelFile = $modelsDir . '/' . $modelName . '.php';
    $fullModelContent = "<?php\nnamespace App\Models;\n{$modelContentStr}";
    file_put_contents($modelFile, $fullModelContent);

    $index++;
}

echo "15 models and migrations generated successfully.\\n";
