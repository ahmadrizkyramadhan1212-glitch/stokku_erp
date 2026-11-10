<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Ingredient;
use App\Models\IngredientPrice;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\MenuItem;
use App\Models\DailySale;
use App\Models\DailySaleItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Expense;

/**
 * [LEGACY] Seeder lama data Dapur Kuliner (Tabso/Pastel). Tidak dipanggil lagi —
 * diganti StokkuSeeder. Disimpan hanya sebagai arsip referensi.
 */
class LegacyKulinerSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat User
        $admin = User::create([
            'name' => 'Owner ',
            'email' => 'admin@.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        $kasir = User::create([
            'name' => 'Kasir ',
            'email' => 'kasir@.com',
            'password' => Hash::make('password'),
            'role' => 'kasir'
        ]);

        // 2. Buat Supplier
        $sup1 = Supplier::create(['name' => 'Pasar Induk', 'kategori_bahan' => 'Sayuran & Tahu']);
        $sup2 = Supplier::create(['name' => 'Toko Daging Barokah', 'kategori_bahan' => 'Daging']);
        $sup3 = Supplier::create(['name' => 'Grosir Sembako', 'kategori_bahan' => 'Sembako']);

        // 3. Buat Bahan Baku
        $ingTahu = Ingredient::create([
            'name' => 'Tahu Kulit',
            'unit' => 'pcs',
            'current_stock' => 1000,
            'min_stock' => 100,
            'cost_per_unit' => 500,
            'supplier_id' => $sup1->id
        ]);
        $ingAyam = Ingredient::create([
            'name' => 'Daging Ayam Fillet',
            'unit' => 'kg',
            'current_stock' => 20,
            'min_stock' => 5,
            'cost_per_unit' => 35000,
            'supplier_id' => $sup2->id
        ]);
        $ingTapioka = Ingredient::create([
            'name' => 'Tepung Tapioka',
            'unit' => 'kg',
            'current_stock' => 10,
            'min_stock' => 2,
            'cost_per_unit' => 12000,
            'supplier_id' => $sup3->id
        ]);
        $ingPanir = Ingredient::create([
            'name' => 'Tepung Panir',
            'unit' => 'kg',
            'current_stock' => 10,
            'min_stock' => 2,
            'cost_per_unit' => 15000,
            'supplier_id' => $sup3->id
        ]);
        $ingTerigu = Ingredient::create([
            'name' => 'Tepung Terigu',
            'unit' => 'kg',
            'current_stock' => 15,
            'min_stock' => 5,
            'cost_per_unit' => 11000,
            'supplier_id' => $sup3->id
        ]);
        $ingWortel = Ingredient::create([
            'name' => 'Wortel',
            'unit' => 'kg',
            'current_stock' => 5,
            'min_stock' => 1,
            'cost_per_unit' => 14000,
            'supplier_id' => $sup1->id
        ]);
        $ingKentang = Ingredient::create([
            'name' => 'Kentang',
            'unit' => 'kg',
            'current_stock' => 5,
            'min_stock' => 1,
            'cost_per_unit' => 16000,
            'supplier_id' => $sup1->id
        ]);
        $ingMinyak = Ingredient::create([
            'name' => 'Minyak Goreng',
            'unit' => 'liter',
            'current_stock' => 20,
            'min_stock' => 5,
            'cost_per_unit' => 16000,
            'supplier_id' => $sup3->id
        ]);

        // 4. Histori Harga Bahan (data awal)
        IngredientPrice::create([
            'ingredient_id' => $ingTahu->id,
            'supplier_id' => $sup1->id,
            'price_per_unit' => 500,
            'quantity' => 1000,
            'purchased_at' => Carbon::now()->subDays(5)
        ]);
        IngredientPrice::create([
            'ingredient_id' => $ingAyam->id,
            'supplier_id' => $sup2->id,
            'price_per_unit' => 35000,
            'quantity' => 20,
            'purchased_at' => Carbon::now()->subDays(2)
        ]);

        // 5. Buat Resep
        $recTabso = Recipe::create([
            'name' => 'Tabso Crispy',
            'serving_qty' => 1,
            'packaging_cost' => 500,
            'overhead_cost' => 300
        ]);
        RecipeItem::create(['recipe_id' => $recTabso->id, 'ingredient_id' => $ingTahu->id, 'quantity' => 1]);       // 1 pcs tahu
        RecipeItem::create(['recipe_id' => $recTabso->id, 'ingredient_id' => $ingAyam->id, 'quantity' => 0.03]);    // 30g ayam
        RecipeItem::create(['recipe_id' => $recTabso->id, 'ingredient_id' => $ingTapioka->id, 'quantity' => 0.01]); // 10g tapioka
        RecipeItem::create(['recipe_id' => $recTabso->id, 'ingredient_id' => $ingPanir->id, 'quantity' => 0.015]);  // 15g panir
        RecipeItem::create(['recipe_id' => $recTabso->id, 'ingredient_id' => $ingMinyak->id, 'quantity' => 0.02]);  // 20ml minyak

        $recPastel = Recipe::create([
            'name' => 'Pastel',
            'serving_qty' => 1,
            'packaging_cost' => 300,
            'overhead_cost' => 200
        ]);
        RecipeItem::create(['recipe_id' => $recPastel->id, 'ingredient_id' => $ingTerigu->id, 'quantity' => 0.03]);  // 30g terigu
        RecipeItem::create(['recipe_id' => $recPastel->id, 'ingredient_id' => $ingKentang->id, 'quantity' => 0.02]); // 20g kentang
        RecipeItem::create(['recipe_id' => $recPastel->id, 'ingredient_id' => $ingWortel->id, 'quantity' => 0.01]);  // 10g wortel
        RecipeItem::create(['recipe_id' => $recPastel->id, 'ingredient_id' => $ingMinyak->id, 'quantity' => 0.02]);  // 20ml minyak

        $recTahuGoreng = Recipe::create([
            'name' => 'Tahu Goreng',
            'serving_qty' => 1,
            'packaging_cost' => 200,
            'overhead_cost' => 100
        ]);
        RecipeItem::create(['recipe_id' => $recTahuGoreng->id, 'ingredient_id' => $ingTahu->id, 'quantity' => 1]);    // 1 pcs tahu
        RecipeItem::create(['recipe_id' => $recTahuGoreng->id, 'ingredient_id' => $ingMinyak->id, 'quantity' => 0.02]); // 20ml minyak

        // 6. Buat Menu Item
        $menu1 = MenuItem::create([
            'recipe_id' => $recTabso->id,
            'name' => 'Tabso Crispy',
            'category' => 'Makanan Utama',
            'price_eceran' => 3500,
            'price_reseller' => 3000,
            'price_agen' => 2800
        ]);
        $menu2 = MenuItem::create([
            'recipe_id' => $recPastel->id,
            'name' => 'Pastel',
            'category' => 'Cemilan',
            'price_eceran' => 2500,
            'price_reseller' => 2200,
            'price_agen' => 2000
        ]);
        $menu3 = MenuItem::create([
            'recipe_id' => $recTahuGoreng->id,
            'name' => 'Tahu Goreng',
            'category' => 'Cemilan',
            'price_eceran' => 2000,
            'price_reseller' => 1800,
            'price_agen' => 1500
        ]);

        // Hitung HPP awal
        foreach ([$menu1, $menu2, $menu3] as $menu) {
            $menu->syncHpp();
        }

        // Persiapan data resep dan menu untuk simulasi
        $menus = [
            ['menu' => $menu1, 'recipe' => $recTabso],
            ['menu' => $menu2, 'recipe' => $recPastel],
            ['menu' => $menu3, 'recipe' => $recTahuGoreng],
        ];
        // Muat semua recipe items dalam struktur yang mudah diakses
        $recipeItems = [];
        foreach (RecipeItem::all() as $ri) {
            $recipeItems[$ri->recipe_id][] = $ri;
        }

        // Inisialisasi stok runtime (mencerminkan stok awal dari DB)
        $stocks = [
            $ingTahu->id => $ingTahu->current_stock,
            $ingAyam->id => $ingAyam->current_stock,
            $ingTapioka->id => $ingTapioka->current_stock,
            $ingPanir->id => $ingPanir->current_stock,
            $ingTerigu->id => $ingTerigu->current_stock,
            $ingWortel->id => $ingWortel->current_stock,
            $ingKentang->id => $ingKentang->current_stock,
            $ingMinyak->id => $ingMinyak->current_stock,
        ];

        // Supplier & bahan grouping (untuk simulasi pembelian harian)
        $supplierIngredients = [
            $sup1->id => [$ingTahu, $ingWortel, $ingKentang],
            $sup2->id => [$ingAyam],
            $sup3->id => [$ingTapioka, $ingPanir, $ingTerigu, $ingMinyak],
        ];

        // Rentang pembelian harian per kategori (agar stok tetap terjaga)
        $purchaseRanges = [
            $sup1->id => ['Tahu Kulit' => [200, 600], 'Wortel' => [2, 10], 'Kentang' => [2, 10]],
            $sup2->id => ['Daging Ayam Fillet' => [5, 20]],
            $sup3->id => [
                'Tepung Tapioka' => [3, 10],
                'Tepung Panir' => [3, 8],
                'Tepung Terigu' => [3, 12],
                'Minyak Goreng' => [5, 15],
            ],
        ];

        // 7. Simulasi 90 hari (3 bulan terakhir) transaksi rame setiap hari
        $startDate = Carbon::now()->subDays(90)->startOfDay();
        $endDate = Carbon::now()->startOfDay(); // hari ini

        $buyerTypes = ['Eceran', 'Reseller', 'Agen'];

        for ($date = $startDate->copy(); $date->lt($endDate); $date->addDay()) {
            $currentDate = $date->format('Y-m-d');

            // ---------- PEMBELIAN BAHAN BAKU HARIAN ----------
            // Pilih supplier bergilir (dua supplier per hari agar stok beragam)
            $supplierIds = array_keys($supplierIngredients);
            $selectedSuppliers = [$supplierIds[$date->dayOfYear % count($supplierIds)]]; // satu supplier per hari
            // Kadang tambah supplier kedua agar lebih ramai
            if ($date->day % 3 == 0) {
                $second = $supplierIds[($date->dayOfYear + 1) % count($supplierIds)];
                if (!in_array($second, $selectedSuppliers)) {
                    $selectedSuppliers[] = $second;
                }
            }

            foreach ($selectedSuppliers as $supplierId) {
                $purchase = Purchase::create([
                    'user_id' => $admin->id,
                    'supplier_id' => $supplierId,
                    'purchase_date' => $currentDate,
                    'payment_method' => 'Tunai',
                    'notes' => 'Restok harian'
                ]);

                $purchaseTotal = 0;
                foreach ($supplierIngredients[$supplierId] as $ingredient) {
                    $range = $purchaseRanges[$supplierId][$ingredient->name] ?? [10, 50];
                    $qty = rand($range[0], $range[1]);

                    // Catat item pembelian
                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'ingredient_id' => $ingredient->id,
                        'quantity' => $qty,
                        'price_per_unit' => $ingredient->cost_per_unit,
                        'subtotal' => $qty * $ingredient->cost_per_unit
                    ]);

                    // Tambah ke stok runtime
                    $stocks[$ingredient->id] += $qty;
                }

                $purchase->recalculateTotal();
            }

            // ---------- PENJUALAN HARIAN (produksi = penjualan) ----------
            $sale = DailySale::create([
                'user_id' => $kasir->id,
                'sale_date' => $currentDate
            ]);

            foreach ($menus as $menuData) {
                $menu = $menuData['menu'];
                $recipeId = $menu->recipe_id;
                $items = $recipeItems[$recipeId] ?? [];

                // Hitung maksimum produksi berdasarkan stok yang tersedia
                $maxServings = PHP_INT_MAX;
                foreach ($items as $ri) {
                    if ($ri->quantity > 0) {
                        $possible = floor($stocks[$ri->ingredient_id] / $ri->quantity);
                        if ($possible < $maxServings) {
                            $maxServings = $possible;
                        }
                    }
                }

                // Tentukan jumlah terjual (produksi) tidak melebihi kapasitas
                $soldQty = 0;
                if ($maxServings > 0) {
                    // Jual antara 60% sampai 100% kapasitas, agar transaksi tetap ramai
                    $minSell = max(1, (int) ($maxServings * 0.6));
                    $maxSell = $maxServings;
                    $soldQty = rand($minSell, $maxSell);

                    // Kurangi stok bahan baku sesuai resep
                    foreach ($items as $ri) {
                        $stocks[$ri->ingredient_id] -= $ri->quantity * $soldQty;
                    }
                }
                // Jika kapasitas = 0, maka tidak ada penjualan untuk menu ini hari itu

                if ($soldQty > 0) {
                    // Tentukan tipe pembeli secara acak
                    $buyerType = $buyerTypes[array_rand($buyerTypes)];

                    DailySaleItem::makeFromQty($sale, $menu, $soldQty, $buyerType)->save();
                }
            }

            $sale->recalculateTotals();

            // ---------- BIAYA OPERASIONAL HARIAN ----------
            Expense::create([
                'user_id' => $admin->id,
                'category' => 'Biaya Produksi',
                'description' => 'Gas, listrik, tenaga masak harian',
                'amount' => rand(25000, 50000),
                'expense_date' => $currentDate
            ]);
        }
    }
}