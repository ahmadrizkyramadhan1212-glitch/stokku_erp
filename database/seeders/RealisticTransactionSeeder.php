<?php

namespace Database\Seeders;

use App\Models\DailySale;
use App\Models\DailySaleItem;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RealisticTransactionSeeder extends Seeder
{
    /**
     * Update harga 96 komoditas acuan pasar induk dan seed transaksi realistis skala modal Rp 10 Juta (margin ~25%).
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Hubungkan Recipe 1 ke Ingredient 1 (Jagung Manis) jika belum
            $rec1 = Recipe::find(1);
            if ($rec1) {
                $ri1 = RecipeItem::where('recipe_id', 1)->first();
                if ($ri1) {
                    $ri1->update(['ingredient_id' => 1, 'quantity' => 1.0]);
                } else {
                    RecipeItem::create(['recipe_id' => 1, 'ingredient_id' => 1, 'quantity' => 1.0]);
                }
            }

            // 2. Daftar 96 Komoditas Acuan Pasar Induk (Tanpa mengubah image_path)
            $priceMap = [
                // Sayur & Buah Segar
                1  => ['cost' => 6500, 'sell' => 9000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                2  => ['cost' => 8500, 'sell' => 11000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                3  => ['cost' => 12000, 'sell' => 15000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 1500],
                4  => ['cost' => 6500, 'sell' => 9000, 'unit' => 'buah', 'sale_unit' => 'buah', 'ratio' => 1.0],
                5  => ['cost' => 8000, 'sell' => 11000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                6  => ['cost' => 10000, 'sell' => 13000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                7  => ['cost' => 12000, 'sell' => 15000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                8  => ['cost' => 12000, 'sell' => 15000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 1500],
                9  => ['cost' => 7500, 'sell' => 10000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                10 => ['cost' => 8500, 'sell' => 11000, 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0],
                11 => ['cost' => 17000, 'sell' => 22000, 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0],
                12 => ['cost' => 5000, 'sell' => 7000, 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0],
                13 => ['cost' => 22000, 'sell' => 28000, 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0],
                14 => ['cost' => 17000, 'sell' => 22000, 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0],
                15 => ['cost' => 10000, 'sell' => 13000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                16 => ['cost' => 25000, 'sell' => 32000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                17 => ['cost' => 20000, 'sell' => 25000, 'unit' => 'sisir', 'sale_unit' => 'sisir', 'ratio' => 1.0],
                18 => ['cost' => 20000, 'sell' => 26000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                19 => ['cost' => 24000, 'sell' => 30000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                20 => ['cost' => 27000, 'sell' => 34000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 3400],
                21 => ['cost' => 6500, 'sell' => 9000, 'unit' => 'butir', 'sale_unit' => 'butir', 'ratio' => 1.0],

                // Bumbu Dapur & Rempah
                22 => ['cost' => 42000, 'sell' => 52000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 5200],
                23 => ['cost' => 3000, 'sell' => 4500, 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0],
                24 => ['cost' => 38000, 'sell' => 48000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 4800],
                25 => ['cost' => 48000, 'sell' => 58000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 5800],
                26 => ['cost' => 3000, 'sell' => 4500, 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0],
                27 => ['cost' => 38000, 'sell' => 46000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 4600],
                28 => ['cost' => 38000, 'sell' => 46000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 4600],
                29 => ['cost' => 40000, 'sell' => 48000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 4800],
                30 => ['cost' => 24000, 'sell' => 30000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 3000],
                31 => ['cost' => 12000, 'sell' => 16000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 1600],
                32 => ['cost' => 12000, 'sell' => 16000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 1600],
                33 => ['cost' => 30000, 'sell' => 38000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 3800],
                34 => ['cost' => 45000, 'sell' => 55000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 5500],
                35 => ['cost' => 17000, 'sell' => 22000, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                36 => ['cost' => 8000, 'sell' => 11000, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                37 => ['cost' => 70000, 'sell' => 85000, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                38 => ['cost' => 9500, 'sell' => 12500, 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0],
                39 => ['cost' => 25000, 'sell' => 32000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 3200],
                40 => ['cost' => 9500, 'sell' => 12500, 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0],
                41 => ['cost' => 25000, 'sell' => 32000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 3200],
                42 => ['cost' => 6000, 'sell' => 8000, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                43 => ['cost' => 6000, 'sell' => 8000, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],

                // Minyak, Saus & Kecap
                44 => ['cost' => 20000, 'sell' => 23000, 'unit' => 'dus', 'sale_unit' => 'pouch', 'ratio' => 1.0],
                45 => ['cost' => 40000, 'sell' => 45000, 'unit' => 'pouch', 'sale_unit' => 'pouch', 'ratio' => 1.0],
                46 => ['cost' => 10000, 'sell' => 12500, 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0],
                47 => ['cost' => 10000, 'sell' => 13000, 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0],
                48 => ['cost' => 11000, 'sell' => 14500, 'unit' => 'pouch', 'sale_unit' => 'pouch', 'ratio' => 1.0],
                49 => ['cost' => 12000, 'sell' => 15500, 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0],
                50 => ['cost' => 11000, 'sell' => 14000, 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0],
                51 => ['cost' => 4500, 'sell' => 6000, 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0],
                52 => ['cost' => 21000, 'sell' => 25000, 'unit' => 'pouch', 'sale_unit' => 'pouch', 'ratio' => 1.0],
                53 => ['cost' => 5500, 'sell' => 7500, 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0],
                54 => ['cost' => 10000, 'sell' => 13000, 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0],
                55 => ['cost' => 21000, 'sell' => 26500, 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0],

                // Sembako & Bahan Pokok
                56 => ['cost' => 12500, 'sell' => 15500, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                57 => ['cost' => 9000, 'sell' => 11500, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                58 => ['cost' => 28000, 'sell' => 32000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                59 => ['cost' => 13500, 'sell' => 16000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                60 => ['cost' => 5500, 'sell' => 7500, 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0],
                61 => ['cost' => 5500, 'sell' => 7500, 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0],
                62 => ['cost' => 9500, 'sell' => 12500, 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0],
                63 => ['cost' => 17500, 'sell' => 22500, 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0],
                64 => ['cost' => 17500, 'sell' => 22500, 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0],
                65 => ['cost' => 30000, 'sell' => 36000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 3600],
                66 => ['cost' => 23500, 'sell' => 28000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 2800],
                67 => ['cost' => 18000, 'sell' => 20500, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                68 => ['cost' => 9500, 'sell' => 11500, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                69 => ['cost' => 10000, 'sell' => 12500, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                70 => ['cost' => 12500, 'sell' => 15500, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                71 => ['cost' => 12000, 'sell' => 15000, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                72 => ['cost' => 12500, 'sell' => 15500, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                73 => ['cost' => 6000, 'sell' => 7500, 'unit' => 'kotak', 'sale_unit' => 'kotak', 'ratio' => 1.0],
                74 => ['cost' => 17000, 'sell' => 21000, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                75 => ['cost' => 22000, 'sell' => 26000, 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0],
                76 => ['cost' => 25000, 'sell' => 31000, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                77 => ['cost' => 4500, 'sell' => 6000, 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0],
                78 => ['cost' => 98000, 'sell' => 125000, 'unit' => 'dus', 'sale_unit' => 'bungkus', 'ratio' => 0.025, 'sell_unit' => 3800],
                79 => ['cost' => 7500, 'sell' => 9500, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],

                // Lauk, Minuman & Rumah Tangga
                80 => ['cost' => 17000, 'sell' => 22000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                81 => ['cost' => 80000, 'sell' => 98000, 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1, 'sell_unit' => 9800],
                82 => ['cost' => 14000, 'sell' => 17500, 'unit' => 'kaleng', 'sale_unit' => 'kaleng', 'ratio' => 1.0],
                83 => ['cost' => 24000, 'sell' => 29000, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                84 => ['cost' => 24000, 'sell' => 29000, 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0],
                85 => ['cost' => 8000, 'sell' => 11000, 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0],
                86 => ['cost' => 41000, 'sell' => 48000, 'unit' => 'dus', 'sale_unit' => 'dus', 'ratio' => 1.0],
                87 => ['cost' => 20000, 'sell' => 25000, 'unit' => 'galon', 'sale_unit' => 'galon', 'ratio' => 1.0],
                88 => ['cost' => 20000, 'sell' => 3000, 'unit' => 'kantong', 'sale_unit' => 'kantong', 'ratio' => 1.0, 'cost_unit' => 2000],
                89 => ['cost' => 4000, 'sell' => 5500, 'unit' => 'pcs', 'sale_unit' => 'pcs', 'ratio' => 1.0],
                90 => ['cost' => 4800, 'sell' => 6000, 'unit' => 'pcs', 'sale_unit' => 'pcs', 'ratio' => 1.0],
                91 => ['cost' => 28000, 'sell' => 33000, 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0],
                92 => ['cost' => 10000, 'sell' => 13000, 'unit' => 'pcs', 'sale_unit' => 'pcs', 'ratio' => 1.0],
                93 => ['cost' => 11000, 'sell' => 14000, 'unit' => 'pack', 'sale_unit' => 'pack', 'ratio' => 1.0],
                94 => ['cost' => 2500, 'sell' => 3500, 'unit' => 'pcs', 'sale_unit' => 'pcs', 'ratio' => 1.0],
                95 => ['cost' => 19000, 'sell' => 22000, 'unit' => 'tabung', 'sale_unit' => 'tabung', 'ratio' => 1.0],
                96 => ['cost' => 4000, 'sell' => 5500, 'unit' => 'pcs', 'sale_unit' => 'pcs', 'ratio' => 1.0],
            ];

            foreach ($priceMap as $id => $p) {
                $ing = Ingredient::find($id);
                if (!$ing) continue;

                $realCost = $p['cost_unit'] ?? $p['cost'];
                $ing->cost_per_unit = $realCost;
                if (isset($p['unit'])) {
                    $ing->unit = $p['unit'];
                }
                $ing->notes = "Harga beli acuan Rp " . number_format($realCost, 0, ',', '.') . " / {$ing->unit}";
                $ing->save();

                $ri = RecipeItem::where('ingredient_id', $id)->first();
                if ($ri && isset($p['ratio'])) {
                    $ri->quantity = $p['ratio'];
                    $ri->save();
                }

                $menu = $ri ? MenuItem::where('recipe_id', $ri->recipe_id)->first() : MenuItem::find($id);
                if ($menu) {
                    $menu->sale_unit = $p['sale_unit'] ?? $menu->sale_unit;
                    $sellPrice = $p['sell_unit'] ?? $p['sell'];
                    $menu->price_eceran = $sellPrice;
                    $menu->price_reseller = round($sellPrice * 0.95);
                    $menu->price_agen = round($sellPrice * 0.90);
                    $menu->hpp = round($realCost * ($p['ratio'] ?? 1.0), 2);
                    $menu->save();
                }
            }

            // 3. Bersihkan transaksi lama
            DB::table('daily_sale_items')->delete();
            DB::table('daily_sales')->delete();
            DB::table('purchase_items')->delete();
            DB::table('purchases')->delete();
            DB::table('expenses')->delete();

            $owner = User::where('role', 'admin')->orWhere('role', 'owner')->first() ?? User::first();
            $supplierPasar = Supplier::first() ?? Supplier::create(['name' => 'Pasar Induk Sayur', 'is_active' => true]);

            $menus = MenuItem::where('is_active', true)->where('price_eceran', '>', 0)->get()->keyBy('id');
            $ingredients = Ingredient::where('is_active', true)->get()->keyBy('id');

            // 4. SUSUN TRANSAKSI REALISTIS
            $dailyPlan = [
                0 => ['date' => Carbon::now()->toDateString(), 'rev' => 1850000, 'pur' => 1350000, 'exp' => 30000],
                1 => ['date' => Carbon::now()->subDays(1)->toDateString(), 'rev' => 1620000, 'pur' => 1185000, 'exp' => 25000],
                2 => ['date' => Carbon::now()->subDays(2)->toDateString(), 'rev' => 1380000, 'pur' => 1005000, 'exp' => 25000],
                3 => ['date' => Carbon::now()->subDays(3)->toDateString(), 'rev' => 1350000, 'pur' =>  975000, 'exp' => 25000],
                4 => ['date' => Carbon::now()->subDays(4)->toDateString(), 'rev' => 1320000, 'pur' =>  960000, 'exp' => 20000],
                5 => ['date' => Carbon::now()->subDays(5)->toDateString(), 'rev' => 1360000, 'pur' => 1000000, 'exp' => 20000],
                6 => ['date' => Carbon::now()->subDays(6)->toDateString(), 'rev' => 1320000, 'pur' =>  960000, 'exp' => 20000],
                7 => ['date' => Carbon::now()->subDays(7)->toDateString(), 'rev' => 1600000, 'pur' => 1175000, 'exp' => 25000],
                8 => ['date' => Carbon::now()->subDays(8)->toDateString(), 'rev' => 1600000, 'pur' => 1175000, 'exp' => 25000],
            ];

            // 21 hari histori sebelumnya (bulan lalu) untuk kelengkapan grafik SPK 30 hari
            for ($d = 9; $d <= 29; $d++) {
                $dt = Carbon::now()->subDays($d);
                $dailyPlan[$d] = [
                    'date' => $dt->toDateString(),
                    'rev'  => 1450000,
                    'pur'  => 1060000,
                    'exp'  => 25000,
                ];
            }

            $coreMenuIds = [59, 58, 44, 78, 27, 28, 24, 22, 1, 8, 67, 56, 10, 2, 6, 88, 86, 95, 60, 61];

            foreach ($dailyPlan as $daysAgo => $plan) {
                $dateStr = $plan['date'];
                $targetRev = (float) $plan['rev'];
                $targetPur = (float) $plan['pur'];
                $targetExp = (float) $plan['exp'];

                // Penjualan (DailySale) 3 sesi
                $sessions = [
                    ['hour' => 8,  'pct' => 0.45, 'notes' => 'Sesi Belanja Pagi Ibu-Ibu Warung'],
                    ['hour' => 13, 'pct' => 0.30, 'notes' => 'Sesi Belanja Siang Masak'],
                    ['hour' => 18, 'pct' => 0.25, 'notes' => 'Sesi Belanja Sore & Pulang Kerja'],
                ];

                $runningRev = 0;
                $runningHpp = 0;
                foreach ($sessions as $sIdx => $sess) {
                    $sessRevTarget = ($sIdx === count($sessions) - 1)
                        ? ($targetRev - $runningRev)
                        : round($targetRev * $sess['pct'], -2);

                    $sale = DailySale::create([
                        'user_id' => $owner->id,
                        'sale_date' => $dateStr . ' ' . sprintf('%02d:00:00', $sess['hour']),
                        'total_revenue' => 0,
                        'total_hpp' => 0,
                        'gross_profit' => 0,
                        'payment_method' => ($sIdx === 0 ? 'Tunai' : ($sIdx === 1 ? 'QRIS' : 'Tunai')),
                        'notes' => $sess['notes'],
                        'created_at' => $dateStr . ' ' . sprintf('%02d:15:00', $sess['hour']),
                        'updated_at' => $dateStr . ' ' . sprintf('%02d:15:00', $sess['hour']),
                    ]);

                    $sessCurRev = 0;
                    $sessCurHpp = 0;
                    $itemsCount = 6 + ($daysAgo % 4);
                    $shuffledMenus = collect($coreMenuIds)->shuffle()->take($itemsCount);

                    foreach ($shuffledMenus as $mIdx => $mId) {
                        $m = $menus[$mId] ?? null;
                        if (!$m) continue;

                        $price = (float) $m->price_eceran;
                        $hpp = (float) $m->hpp;
                        if ($price <= 0) continue;

                        $remRev = $sessRevTarget - $sessCurRev;
                        if ($remRev <= 0) break;

                        $maxQty = max(1, min(10, (int) floor($remRev / $price)));
                        $qty = ($mIdx === count($shuffledMenus) - 1)
                            ? max(1, (int) round($remRev / $price))
                            : mt_rand(1, max(1, min(4, $maxQty)));

                        $lineRev = $qty * $price;
                        $lineHpp = $qty * $hpp;

                        DailySaleItem::create([
                            'daily_sale_id' => $sale->id,
                            'menu_item_id' => $m->id,
                            'buyer_type' => 'eceran',
                            'qty_sold' => $qty,
                            'selling_price' => $price,
                            'hpp_per_item' => $hpp,
                            'subtotal_revenue' => $lineRev,
                            'subtotal_hpp' => $lineHpp,
                            'contribution_margin' => $lineRev - $lineHpp,
                            'created_at' => $sale->created_at,
                            'updated_at' => $sale->created_at,
                        ]);

                        $sessCurRev += $lineRev;
                        $sessCurHpp += $lineHpp;
                    }

                    if ($sIdx === count($sessions) - 1) {
                        $diff = $targetRev - ($runningRev + $sessCurRev);
                        if ($diff != 0) {
                            $firstLine = DailySaleItem::where('daily_sale_id', $sale->id)->first();
                            if ($firstLine) {
                                $firstLine->subtotal_revenue += $diff;
                                $firstLine->subtotal_hpp += round($diff * 0.746);
                                $firstLine->contribution_margin = $firstLine->subtotal_revenue - $firstLine->subtotal_hpp;
                                $firstLine->save();
                                $sessCurRev += $diff;
                                $sessCurHpp += round($diff * 0.746);
                            }
                        }
                    }

                    $sale->update([
                        'total_revenue' => $sessCurRev,
                        'total_hpp' => $sessCurHpp,
                        'gross_profit' => $sessCurRev - $sessCurHpp,
                    ]);

                    $runningRev += $sessCurRev;
                    $runningHpp += $sessCurHpp;
                }

                // Pembelian Kulakan (Purchase)
                $purchase = Purchase::create([
                    'user_id' => $owner->id,
                    'supplier_id' => $supplierPasar->id,
                    'purchase_date' => $dateStr . ' 05:30:00',
                    'total_amount' => $targetPur,
                    'payment_method' => 'Tunai',
                    'notes' => 'Kulakan Subuh Pasar Induk Sayur & Bumbu Adhi Jaya',
                    'receipt_path' => ($daysAgo % 2 === 0) ? 'receipts/sample_nota.jpg' : null,
                    'created_at' => $dateStr . ' 05:45:00',
                    'updated_at' => $dateStr . ' 05:45:00',
                ]);

                $purIngIds = [59, 58, 27, 28, 24, 22, 1, 8, 10, 2];
                $purCurAmount = 0;
                foreach ($purIngIds as $pIdx => $ingId) {
                    $ing = $ingredients[$ingId] ?? null;
                    if (!$ing) continue;
                    $cost = (float) $ing->cost_per_unit;
                    if ($cost <= 0) continue;

                    $remPur = $targetPur - $purCurAmount;
                    if ($remPur <= 0) break;

                    if ($pIdx === count($purIngIds) - 1) {
                        $subtotal = $remPur;
                        $qty = max(0.5, round($subtotal / $cost, 1));
                    } else {
                        $qty = mt_rand(5, 20);
                        $subtotal = round($qty * $cost);
                        if ($purCurAmount + $subtotal > $targetPur - 10000) {
                            $subtotal = $remPur;
                            $qty = max(0.5, round($subtotal / $cost, 1));
                        }
                    }

                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'ingredient_id' => $ing->id,
                        'quantity' => $qty,
                        'price_per_unit' => $cost,
                        'subtotal' => $subtotal,
                        'created_at' => $purchase->created_at,
                        'updated_at' => $purchase->created_at,
                    ]);

                    $purCurAmount += $subtotal;
                    if ($purCurAmount >= $targetPur) break;
                }

                // Pengeluaran Operasional (Expense)
                if ($targetExp > 0) {
                    Expense::create([
                        'user_id' => $owner->id,
                        'category' => 'Operasional Warung',
                        'description' => 'Plastik kresek, es balok pendingin, & retribusi kebersihan pasar',
                        'amount' => $targetExp,
                        'expense_date' => $dateStr,
                        'notes' => 'Beban harian operasional warung',
                        'created_at' => $dateStr . ' 12:00:00',
                        'updated_at' => $dateStr . ' 12:00:00',
                    ]);
                }
            }
        });
    }
}
