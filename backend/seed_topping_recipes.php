<?php

use App\Models\Topping;
use App\Models\NguyenLieu;
use App\Models\ToppingCongThuc;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$mappings = [
    ['topping' => 'Trân châu đen', 'ingredient' => 'Trân châu đen', 'qty' => 50],
    ['topping' => 'Trân châu trắng', 'ingredient' => 'Trân châu trắng', 'qty' => 50, 'unit' => 'g'],
    ['topping' => 'Thạch nha đam', 'ingredient' => 'Thạch nha đam', 'qty' => 50, 'unit' => 'g'],
    ['topping' => 'Thạch trái cây', 'ingredient' => 'Thạch trái cây', 'qty' => 50, 'unit' => 'g'],
    ['topping' => 'Kem Macchiato', 'ingredient' => 'Kem Macchiato', 'qty' => 30, 'unit' => 'ml'],
    ['topping' => 'Kem Cheese', 'ingredient' => 'Kem Cheese', 'qty' => 30, 'unit' => 'ml'],
    ['topping' => 'Đào miếng (thêm)', 'ingredient' => 'Đào ngâm', 'qty' => 0.2],
    ['topping' => 'Vải ngâm (thêm)', 'ingredient' => 'Vải ngâm', 'qty' => 0.2],
    ['topping' => 'Thêm 1 shot Espresso', 'ingredient' => 'Cà phê hạt pha máy', 'qty' => 10],
    ['topping' => 'Thêm sữa đặc', 'ingredient' => 'Sữa đặc Ngôi Sao', 'qty' => 20],
];

foreach ($mappings as $m) {
    $tp = Topping::where('ten_topping', $m['topping'])->first();
    if (!$tp) continue;

    $nl = NguyenLieu::where('ten_nguyen_lieu', $m['ingredient'])->first();
    if (!$nl && isset($m['unit'])) {
        $nl = NguyenLieu::create([
            'ma_nguyen_lieu' => 'NL_' . strtoupper(uniqid()),
            'ten_nguyen_lieu' => $m['ingredient'],
            'don_vi_tinh' => $m['unit'],
            'ton_kho' => 1000,
            'muc_canh_bao' => 100,
            'trang_thai' => 1
        ]);
    }

    if ($tp && $nl) {
        $tp->congThucs()->updateOrCreate(
            ['nguyen_lieu_id' => $nl->id],
            ['so_luong_can' => $m['qty']]
        );
        echo "Linked: {$tp->ten_topping} -> {$nl->ten_nguyen_lieu}\n";
    }
}
