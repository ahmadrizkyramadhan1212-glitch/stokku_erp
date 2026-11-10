<?php

namespace App\Http\Controllers;

use App\Models\DailySale;
use App\Models\DailySaleItem;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\IngredientPrice;
use App\Models\MenuItem;
use App\Models\ProductionLog;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Supplier;
use App\Services\WaReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventoryController extends Controller
{
    public function index()
    {
        $ingredients   = Ingredient::where('is_active', true)->get();
        $criticalCount = $ingredients->whereIn('stock_status', [Ingredient::STATUS_HABIS, Ingredient::STATUS_MENIPIS])->count();
        $fifoCount     = $ingredients->where('is_fifo', true)->count();
        $menusCount    = MenuItem::where('is_active', true)->count();

        return view('inventory.index', compact('ingredients', 'criticalCount', 'fifoCount', 'menusCount'));
    }

    public function materials(Request $request)
    {
        $search   = trim((string) $request->query('q', ''));
        $kategori = (string) $request->query('kategori', '');

        $allIngredients = Ingredient::with(['supplier', 'recipeItems.recipe.menuItem'])
            ->where('is_active', true)
            ->get();

        // Sinkronisasi otomatis agar barang dengan current_stock > min_stock berstatus AMAN,
        // current_stock <= 0 berstatus HABIS, dan 0 < current_stock <= min_stock berstatus MENIPIS
        foreach ($allIngredients as $ing) {
            $expectedStatus = $ing->determineStockStatus();
            if ($ing->stock_status !== $expectedStatus) {
                $ing->stock_status = $expectedStatus;
                $ing->saveQuietly();
            }
            $ing->linked_menu = $ing->recipeItems->first()?->recipe?->menuItem;
        }

        $fifoItems     = $allIngredients->where('is_fifo', true)->values();
        $criticalCount = $allIngredients->whereIn('stock_status', [Ingredient::STATUS_HABIS, Ingredient::STATUS_MENIPIS])->count();

        // Urutkan: Habis -> Menipis (FIFO duluan) -> Aman
        $statusWeight = [
            Ingredient::STATUS_HABIS   => 3,
            Ingredient::STATUS_MENIPIS => 2,
            Ingredient::STATUS_AMAN    => 1,
        ];

        $ingredients = $allIngredients->sort(function (Ingredient $a, Ingredient $b) use ($statusWeight) {
            $wa = $statusWeight[$a->stock_status] ?? 0;
            $wb = $statusWeight[$b->stock_status] ?? 0;
            if ($wa !== $wb) {
                return $wb <=> $wa;
            }
            if ($a->is_fifo !== $b->is_fifo) {
                return $b->is_fifo <=> $a->is_fifo;
            }
            return strcmp($a->name, $b->name);
        })->values();

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $totalCount = $allIngredients->count();

        // Kartu laporan WA tutup warung (alur Karyawan -> Owner)
        $waCard = app(WaReportService::class)->cardData($request->user());

        return view('inventory.materials', compact(
            'ingredients',
            'totalCount',
            'fifoItems',
            'criticalCount',
            'suppliers',
            'search',
            'kategori',
            'waCard'
        ));
    }

    public function production()
    {
        $menus = MenuItem::with('recipe.items.ingredient')->orderBy('name')->get();
        return view('inventory.production', compact('menus'));
    }

    /**
     * 1-Tap Status Stok Cepat Karyawan (Aman / Menipis / Habis) via AJAX atau Form.
     * Otomatisasi Hitung Penjualan di Belakang Layar:
     * Barang Terjual Hari Ini = Stok Pagi - Sisa Stok Malam.
     * Dicatat ke daily_sales & daily_sale_items untuk Kriteria C3 SAW & Laba Bersih tanpa kasir.
     */
    public function updateQuickStatus(Request $request, Ingredient $ingredient)
    {
        // Terima parameter baik 'stock_status' maupun 'status' dari request JSON/Form
        $incomingStatus = strtolower(trim((string) ($request->input('stock_status') ?? $request->input('status') ?? '')));
        $request->merge(['stock_status' => $incomingStatus]);

        $validated = $request->validate([
            'stock_status' => 'required|string|in:aman,menipis,habis',
            'actual_stock' => 'nullable|numeric|min:0',
        ]);

        $status   = $validated['stock_status'];
        $oldStock = max(0.0, (float) $ingredient->current_stock);
        $minStock = max(1.0, (float) $ingredient->min_stock);

        if ($status === Ingredient::STATUS_HABIS) {
            // Klik tombol "Habis" -> stok_saat_ini = 0 dan status = HABIS
            $newStock = 0.0;
            $ingredient->current_stock = 0.0;
            $ingredient->setManualStockStatus(Ingredient::STATUS_HABIS, true);
        } elseif ($status === Ingredient::STATUS_MENIPIS) {
            // Klik tombol "Menipis" -> stok_saat_ini = batas_aman (min_stock) dan status = MENIPIS
            if ($request->filled('actual_stock') && (float) $request->actual_stock > 0 && (float) $request->actual_stock <= $minStock) {
                $newStock = (float) $request->actual_stock;
            } else {
                $newStock = $minStock;
            }
            $ingredient->current_stock = $newStock;
            $ingredient->setManualStockStatus(Ingredient::STATUS_MENIPIS, false);
        } else {
            // Klik tombol "Aman" -> pastikan stok_saat_ini > batas_aman (min_stock) dan status = AMAN
            if ($request->filled('actual_stock') && (float) $request->actual_stock > $minStock) {
                $newStock = (float) $request->actual_stock;
            } elseif ($oldStock > $minStock) {
                $newStock = $oldStock;
            } else {
                $newStock = $minStock + max(2.0, round($minStock * 0.5, 0));
            }
            $ingredient->current_stock = $newStock;
            $ingredient->setManualStockStatus(Ingredient::STATUS_AMAN, false);
        }

        // Otomatisasi Hitung Barang Terjual Hari Ini: Stok Pagi (Sebelumnya) - Sisa Stok Malam
        $deltaSold = max(0.0, $oldStock - $newStock);
        if ($deltaSold > 0) {
            $menuItem = $ingredient->recipeItems()->first()?->recipe?->menuItem;
            if ($menuItem) {
                $today = Carbon::today()->toDateString();
                $dailySale = DailySale::firstOrCreate(
                    ['sale_date' => $today, 'notes' => 'Rekap Otomatis Update Stok Malam'],
                    [
                        'user_id'        => Auth::id() ?: 1,
                        'payment_method' => 'Tunai',
                        'total_revenue'  => 0,
                        'total_hpp'      => 0,
                        'gross_profit'   => 0,
                    ]
                );

                $ratio = max(0.001, (float) ($ingredient->recipeItems()->first()?->quantity ?? 1));
                $soldRetailQty = max(1, (int) round($deltaSold / $ratio));

                $saleItem = DailySaleItem::makeFromQty($dailySale, $menuItem, $soldRetailQty, 'Eceran');
                $saleItem->save();

                $dailySale->recalculateTotals();

                // Sinkronkan sisa stok etalase
                $menuItem->current_stock = max(0, (int) $menuItem->current_stock - $soldRetailQty);
                $menuItem->save();
            }
        }

        $stockFormatted = number_format(
            (float) $ingredient->current_stock,
            (float) $ingredient->current_stock == (int) $ingredient->current_stock ? 0 : 1,
            ',',
            '.'
        );

        $badgeText = match ($ingredient->stock_status) {
            Ingredient::STATUS_HABIS   => "0 {$ingredient->unit} (HABIS)",
            Ingredient::STATUS_MENIPIS => "{$stockFormatted} {$ingredient->unit} (MENIPIS)",
            default                    => "{$stockFormatted} {$ingredient->unit} (AMAN)",
        };

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'       => true,
                'id'            => $ingredient->id,
                'name'          => $ingredient->name,
                'stock_status'  => $ingredient->stock_status,
                'current_stock' => (float) $ingredient->current_stock,
                'min_stock'     => (float) $ingredient->min_stock,
                'unit'          => $ingredient->unit,
                'badge_text'    => $badgeText,
                'delta_sold'    => $deltaSold,
                'updated_at'    => $ingredient->last_status_updated_at?->format('H:i') ?? now()->format('H:i'),
                'message'       => "Status {$ingredient->name} -> " . strtoupper($ingredient->stock_status) . ($deltaSold > 0 ? " (Terjual {$deltaSold} {$ingredient->unit} otomatis dicatat)." : '.'),
            ]);
        }

        return redirect()->back()->with('success', "Status stok {$ingredient->name} berhasil diubah menjadi " . strtoupper($ingredient->stock_status) . ".");
    }

    /**
     * Reset Semua 96 Komoditas menjadi Status [🟢 AMAN / MASIH BANYAK] (Logika Default AMAN).
     */
    public function resetAllAman(Request $request)
    {
        Ingredient::where('is_active', true)->update([
            'stock_status'           => Ingredient::STATUS_AMAN,
            'last_status_updated_at' => now(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Seluruh 96 komoditas warung berhasil diatur ulang menjadi status [🟢 AMAN / MASIH BANYAK]!',
            ]);
        }

        return redirect()->back()->with('success', 'Seluruh komoditas berhasil diatur ulang ke status [🟢 AMAN / MASIH BANYAK]!');
    }

    /**
     * Update Cepat Status Stok via Ketikan Kalimat Karyawan (Quick AI Natural Text Parser).
     * Contoh: "cabai keriting habis, jagung menipis, minyak aman"
     */
    public function quickAiStatus(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:500',
        ]);

        $text = strtolower($request->input('prompt'));
        $ingredients = Ingredient::where('is_active', true)->get();
        $updated = [];

        // Pisahkan klausa dengan koma / titik koma / kata hubung "dan"
        $clauses = preg_split('/[,;]+|\bdan\b/', $text) ?: [$text];

        foreach ($clauses as $clause) {
            $clause = trim($clause);
            if ($clause === '') {
                continue;
            }

            $targetStatus = null;
            if (preg_match('/\b(habis|kosong|nol|ludes)\b/', $clause)) {
                $targetStatus = Ingredient::STATUS_HABIS;
            } elseif (preg_match('/\b(menipis|dikit|sedikit|hampir habis|kritis|kurang)\b/', $clause)) {
                $targetStatus = Ingredient::STATUS_MENIPIS;
            } elseif (preg_match('/\b(aman|banyak|penuh|ready|tersedia|cukup)\b/', $clause)) {
                $targetStatus = Ingredient::STATUS_AMAN;
            }

            if (! $targetStatus) {
                continue;
            }

            foreach ($ingredients as $ingredient) {
                $ingName = strtolower($ingredient->name);
                $words   = array_filter(explode(' ', $ingName), fn ($w) => strlen($w) >= 4);

                $matched = str_contains($clause, $ingName);
                if (! $matched) {
                    foreach ($words as $word) {
                        if (str_contains($clause, $word)) {
                            $matched = true;
                            break;
                        }
                    }
                }

                if ($matched) {
                    if ($targetStatus === Ingredient::STATUS_HABIS) {
                        $ingredient->setManualStockStatus(Ingredient::STATUS_HABIS, true);
                    } else {
                        if ((float) $ingredient->current_stock <= 0) {
                            $min = max(1, (float) $ingredient->min_stock);
                            $ingredient->current_stock = $targetStatus === Ingredient::STATUS_AMAN ? ($min + 2) : max(1, round($min * 0.5, 1));
                        }
                        $ingredient->setManualStockStatus($targetStatus, false);
                    }

                    $updated[] = [
                        'id'            => $ingredient->id,
                        'name'          => $ingredient->name,
                        'stock_status'  => $ingredient->stock_status,
                        'current_stock' => (float) $ingredient->current_stock,
                        'unit'          => $ingredient->unit,
                    ];
                }
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => count($updated) > 0,
                'updated' => $updated,
                'message' => count($updated) > 0
                    ? count($updated) . ' komoditas berhasil diperbarui: ' . collect($updated)->map(fn ($u) => "{$u['name']} (" . strtoupper($u['stock_status']) . ")")->implode(', ')
                    : 'Tidak ditemukan nama komoditas atau kata kunci status (aman/menipis/habis).',
            ]);
        }

        $msg = count($updated) > 0
            ? count($updated) . ' komoditas berhasil diperbarui via input cepat.'
            : 'Tidak ditemukan kecocokan nama barang atau status (aman/menipis/habis).';

        return redirect()->back()->with(count($updated) > 0 ? 'success' : 'error', $msg);
    }

    /**
     * Modal "+ Tambah Barang Baru" (Auto-Sync Gudang + Konversi Kemasan + Etalase Warung + Upload Foto).
     */
    public function storeIngredient(Request $request)
    {
        $validated = $request->validate([
            'name'                    => 'required|string|max:255',
            'kategori'                => 'required|string|max:100',
            'unit'                    => 'required|string|max:30',
            'sale_unit'               => 'nullable|string|max:30',
            'cost_per_unit'           => 'nullable|numeric|min:0',
            'price_eceran'            => 'nullable|numeric|min:0',
            'price_reseller'          => 'nullable|numeric|min:0',
            'price_agen'              => 'nullable|numeric|min:0',
            'current_stock'           => 'required|numeric|min:0',
            'min_stock'               => 'nullable|numeric|min:0',
            'recommended_restock_qty' => 'nullable|numeric|min:1',
            'perishability_score'     => 'nullable|integer|min:1|max:5',
            'popularity_score'        => 'nullable|integer|min:1|max:5',
            'is_fifo'                 => 'nullable|boolean',
            'fifo_note'               => 'nullable|string|max:255',
            'supplier_id'             => 'nullable|exists:suppliers,id',
            'image'                   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('commodities', 'public');
        }

        DB::transaction(function () use ($validated, $request, $imagePath) {
            $kategori = $validated['kategori'];
            $defaultPerishability = match ($kategori) {
                'Sayur & Buah Segar', 'Sayur Segar'         => 3,
                'Bumbu Dapur & Rempah', 'Bumbu Kecil'       => 2,
                'Minyak, Saus & Kecap'                      => 1,
                'Sembako & Bahan Pokok', 'Sembako Kering'   => 1,
                'Lauk, Minuman & Rumah Tangga'              => 1,
                default                                     => 2,
            };

            $unit     = trim($validated['unit']);
            $saleUnit = trim((string) ($validated['sale_unit'] ?: $unit));
            $minStock    = isset($validated['min_stock']) ? (float) $validated['min_stock'] : 3.0;
            $curStock    = (float) $validated['current_stock'];
            $isFifo      = $request->boolean('is_fifo');
            $costPerUnit = isset($validated['cost_per_unit']) && $validated['cost_per_unit'] !== null ? (float) $validated['cost_per_unit'] : 0.0;
            $priceEceran = isset($validated['price_eceran']) && $validated['price_eceran'] !== null ? (float) $validated['price_eceran'] : 0.0;

            $ingredient = Ingredient::create([
                'name'                    => $validated['name'],
                'unit'                    => $unit,
                'kategori'                => $kategori,
                'image_path'              => $imagePath,
                'current_stock'           => $curStock,
                'min_stock'               => $minStock,
                'cost_per_unit'           => $costPerUnit,
                'supplier_id'             => $validated['supplier_id'] ?? null,
                'is_active'               => true,
                'perishability_score'     => $validated['perishability_score'] ?? $defaultPerishability,
                'popularity_score'        => $validated['popularity_score'] ?? 4,
                'recommended_restock_qty' => $validated['recommended_restock_qty'] ?? 10,
                'is_fifo'                 => $isFifo,
                'fifo_note'               => $isFifo ? ($validated['fifo_note'] ?: "Dahulukan penjualan stok lama {$validated['name']} (FIFO) sebelum stok baru!") : null,
                'last_status_updated_at'  => now(),
                'notes'                   => 'Harga beli acuan Rp ' . number_format($costPerUnit, 0, ',', '.') . " / {$unit}",
            ]);

            if ($curStock > 0 && $costPerUnit > 0) {
                IngredientPrice::create([
                    'ingredient_id'  => $ingredient->id,
                    'supplier_id'    => $ingredient->supplier_id,
                    'price_per_unit' => $costPerUnit,
                    'quantity'       => $curStock,
                    'purchased_at'   => now()->toDateString(),
                    'notes'          => 'Stok awal komoditas baru',
                ]);
            }

            // Rasio konversi otomatis jika satuan gudang kg/dus dan satuan eceran ons/bungkus/pouch
            $ratio = 1.0;
            if ($unit === 'kg' && $saleUnit === 'ons') {
                $ratio = 0.1;
            } elseif ($unit === 'kg' && in_array($saleUnit, ['kantong', 'bungkus'], true)) {
                $ratio = 0.25;
            } elseif ($unit === 'ikat' && $saleUnit === 'bungkus') {
                $ratio = 0.25;
            } elseif ($unit === 'dus' && in_array($saleUnit, ['pouch', 'botol'], true)) {
                $ratio = round(1 / 12, 3);
            } elseif ($unit === 'dus' && $saleUnit === 'bungkus') {
                $ratio = 0.025;
            }

            $recipe = Recipe::create([
                'name'           => "Kemas Ulang {$ingredient->name} (per {$saleUnit})",
                'description'    => "1 {$saleUnit} = {$ratio} {$unit} {$ingredient->name}",
                'serving_qty'    => 1,
                'packaging_cost' => 0,
                'overhead_cost'  => 0,
            ]);

            RecipeItem::create([
                'recipe_id'     => $recipe->id,
                'ingredient_id' => $ingredient->id,
                'quantity'      => $ratio,
            ]);

            $priceReseller = isset($validated['price_reseller']) && $validated['price_reseller'] !== null
                ? (float) $validated['price_reseller']
                : max(0, round($priceEceran * 0.95, -2));
            $priceAgen     = isset($validated['price_agen']) && $validated['price_agen'] !== null
                ? (float) $validated['price_agen']
                : max(0, round($priceEceran * 0.90, -2));

            $etalaseQty = $ratio > 0 ? (int) floor($curStock / $ratio) : (int) round($curStock);

            $menu = MenuItem::create([
                'recipe_id'      => $recipe->id,
                'name'           => $ingredient->name,
                'category'       => $kategori,
                'sale_unit'      => $saleUnit,
                'current_stock'  => max(0, $etalaseQty),
                'price_eceran'   => $priceEceran,
                'price_reseller' => $priceReseller,
                'price_agen'     => $priceAgen,
                'description'    => "Satuan keluar eceran per {$saleUnit}",
                'is_active'      => true,
            ]);

            $menu->syncHpp();
        });

        return redirect()
            ->back()
            ->with('success', "Komoditas \"{$validated['name']}\" berhasil ditambahkan dan disinkronkan ke daftar stok warung!");
    }

    /**
     * Modal Edit Barang & Upload Foto Riil Komoditas.
     */
    public function updateIngredient(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'name'                    => 'required|string|max:255',
            'kategori'                => 'required|string|max:100',
            'unit'                    => 'required|string|max:30',
            'current_stock'           => 'required|numeric|min:0',
            'min_stock'               => 'required|numeric|min:0',
            'cost_per_unit'           => 'nullable|numeric|min:0',
            'price_eceran'            => 'nullable|numeric|min:0',
            'recommended_restock_qty' => 'nullable|numeric|min:1',
            'is_fifo'                 => 'nullable|boolean',
            'image'                   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($ingredient->image_path && ! str_starts_with($ingredient->image_path, 'images/') && Storage::disk('public')->exists($ingredient->image_path)) {
                Storage::disk('public')->delete($ingredient->image_path);
            }
            $ingredient->image_path = $request->file('image')->store('commodities', 'public');
        }

        $ingredient->name          = trim($validated['name']);
        $ingredient->kategori      = $validated['kategori'];
        $ingredient->unit          = trim($validated['unit']);
        $ingredient->current_stock = (float) $validated['current_stock'];
        $ingredient->min_stock     = (float) $validated['min_stock'];

        if (isset($validated['cost_per_unit'])) {
            $ingredient->cost_per_unit = (float) $validated['cost_per_unit'];
        }
        if (isset($validated['recommended_restock_qty'])) {
            $ingredient->recommended_restock_qty = (float) $validated['recommended_restock_qty'];
        }
        if ($request->has('is_fifo')) {
            $ingredient->is_fifo = $request->boolean('is_fifo');
        }

        // Sinkronisasi otomatis status stok berdasarkan current_stock vs min_stock
        $ingredient->stock_status           = $ingredient->determineStockStatus();
        $ingredient->last_status_updated_at = now();
        $ingredient->save();

        // Sinkronkan juga ke MenuItem terkait jika ada
        $menuItem = $ingredient->recipeItems()->first()?->recipe?->menuItem;
        if ($menuItem) {
            $menuItem->name     = $ingredient->name;
            $menuItem->category = $ingredient->kategori;
            if (! empty($validated['price_eceran'])) {
                $menuItem->price_eceran = (float) $validated['price_eceran'];
            }
            $menuItem->save();
            $menuItem->syncHpp();
        }

        if ($request->expectsJson() || $request->ajax()) {
            $stockFormatted = number_format(
                (float) $ingredient->current_stock,
                (float) $ingredient->current_stock == (int) $ingredient->current_stock ? 0 : 1,
                ',',
                '.'
            );
            $badgeText = match ($ingredient->stock_status) {
                Ingredient::STATUS_HABIS   => "0 {$ingredient->unit} (HABIS)",
                Ingredient::STATUS_MENIPIS => "{$stockFormatted} {$ingredient->unit} (MENIPIS)",
                default                    => "{$stockFormatted} {$ingredient->unit} (AMAN)",
            };

            return response()->json([
                'success'       => true,
                'id'            => $ingredient->id,
                'name'          => $ingredient->name,
                'kategori'      => $ingredient->kategori,
                'unit'          => $ingredient->unit,
                'current_stock' => (float) $ingredient->current_stock,
                'min_stock'     => (float) $ingredient->min_stock,
                'stock_status'  => $ingredient->stock_status,
                'cost_per_unit' => (float) $ingredient->cost_per_unit,
                'image_url'     => $ingredient->image_url,
                'badge_text'    => $badgeText,
                'message'       => "Data & foto \"{$ingredient->name}\" berhasil diperbarui.",
            ]);
        }

        return redirect()
            ->route('inventory.materials')
            ->with('success', "Data komoditas \"{$ingredient->name}\" berhasil diperbarui!");
    }

    public function storePurchase(Request $request)
    {
        $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'supplier_id'   => 'nullable|exists:suppliers,id',
            'quantity'      => 'required|numeric|min:0.1',
            'total_price'   => 'required|numeric|min:0',
        ]);

        $ingredient = Ingredient::findOrFail($request->ingredient_id);
        $pricePerUnit = $request->total_price / $request->quantity;

        // 1. Simpan ke Pengeluaran/Pembelian
        $purchase = Purchase::create([
            'user_id'        => Auth::id(),
            'supplier_id'    => $request->supplier_id ?: $ingredient->supplier_id,
            'purchase_date'  => now()->format('Y-m-d'),
            'total_amount'   => $request->total_price,
            'payment_method' => 'Tunai',
            'notes'          => 'Belanja kulakan via Halaman Stok Gudang',
        ]);

        PurchaseItem::create([
            'purchase_id'    => $purchase->id,
            'ingredient_id'  => $ingredient->id,
            'quantity'       => $request->quantity,
            'price_per_unit' => $pricePerUnit,
            'subtotal'       => $request->total_price,
        ]);

        IngredientPrice::create([
            'ingredient_id'  => $ingredient->id,
            'supplier_id'    => $purchase->supplier_id,
            'price_per_unit' => $pricePerUnit,
            'quantity'       => $request->quantity,
            'purchased_at'   => now()->format('Y-m-d'),
            'notes'          => 'Kulakan via Halaman Stok Gudang',
        ]);

        // 2. Hitung Harga Rata-Rata Baru (Weighted Moving Average)
        $oldTotalValue = (float) $ingredient->current_stock * (float) $ingredient->cost_per_unit;
        $newTotalValue = $oldTotalValue + (float) $request->total_price;
        $newTotalStock = (float) $ingredient->current_stock + (float) $request->quantity;

        $newAvgPrice = $newTotalStock > 0 ? ($newTotalValue / $newTotalStock) : $ingredient->cost_per_unit;

        // 3. Update Stok & Harga (stock_status otomatis tersinkronisasi oleh model Ingredient)
        $ingredient->current_stock          = $newTotalStock;
        $ingredient->cost_per_unit          = round($newAvgPrice, 2);
        $ingredient->stock_status           = $ingredient->determineStockStatus();
        $ingredient->last_status_updated_at = now();
        $ingredient->save();

        // 4. Update HPP semua menu yang mengandung bahan ini
        foreach ($ingredient->recipeItems as $recipeItem) {
            $recipeItem->recipe->menuItem?->syncHpp();
        }

        if ($request->expectsJson() || $request->ajax()) {
            $stockFormatted = number_format(
                (float) $ingredient->current_stock,
                (float) $ingredient->current_stock == (int) $ingredient->current_stock ? 0 : 1,
                ',',
                '.'
            );
            $badgeText = match ($ingredient->stock_status) {
                Ingredient::STATUS_HABIS   => "0 {$ingredient->unit} (HABIS)",
                Ingredient::STATUS_MENIPIS => "{$stockFormatted} {$ingredient->unit} (MENIPIS)",
                default                    => "{$stockFormatted} {$ingredient->unit} (AMAN)",
            };

            return response()->json([
                'success'       => true,
                'id'            => $ingredient->id,
                'name'          => $ingredient->name,
                'stock_status'  => $ingredient->stock_status,
                'current_stock' => (float) $ingredient->current_stock,
                'cost_per_unit' => (float) $ingredient->cost_per_unit,
                'unit'          => $ingredient->unit,
                'badge_text'    => $badgeText,
                'message'       => "Kulakan {$ingredient->name} (+{$request->quantity} {$ingredient->unit}) berhasil dicatat!",
            ]);
        }

        return redirect()->back()->with('success', "Kulakan {$ingredient->name} (+{$request->quantity} {$ingredient->unit}) berhasil! Stok dan HPP Moving Average telah diperbarui.");
    }

    public function storeProduction(Request $request)
    {
        $request->validate([
            'menu_id'  => 'required|exists:menu_items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $menu = MenuItem::with('recipe.items.ingredient')->findOrFail($request->menu_id);
        $qty  = (int) $request->quantity;

        // 1. Validasi apakah bahan cukup
        if ($menu->production_capacity < $qty) {
            return redirect()->back()->with('error', 'Stok curah gudang tidak cukup untuk dikemas ulang sejumlah ini!');
        }

        // 2. Potong stok bahan baku (otomatis menyinkronkan stock_status)
        foreach ($menu->recipe->items as $recipeItem) {
            $ingredient = $recipeItem->ingredient;
            if ($ingredient) {
                $ingredient->current_stock = max(0, (float) $ingredient->current_stock - ($recipeItem->quantity * $qty));
                $ingredient->save();
            }
        }

        // 3. Tambah stok siap jual
        $menu->current_stock += $qty;
        $menu->save();

        // 4. Catat Log Produksi / Kemas Ulang untuk kebutuhan BI Dashboard
        ProductionLog::create([
            'menu_item_id'    => $menu->id,
            'quantity'        => $qty,
            'production_date' => now()->format('Y-m-d'),
        ]);

        return redirect()->back()->with('success', "Berhasil mengemas ulang {$qty} {$menu->sale_unit} {$menu->name} ke etalase kasir.");
    }

    /**
     * Koreksi Fisik / Barang Busuk / Susut (Stock Opname Gudang).
     * Jika stok fisik nyata lebih sedikit dari sistem, selisihnya otomatis dikalikan
     * harga beli (cost_per_unit) dan dicatat ke tabel expenses dengan kategori "Barang Busuk/Susut".
     */
    public function storeOpname(Request $request)
    {
        $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'actual_stock'  => 'required|numeric|min:0',
            'reason'        => 'nullable|string|max:255',
        ]);

        $ingredient  = Ingredient::findOrFail($request->ingredient_id);
        $actualStock = max(0.0, (float) $request->actual_stock);
        $oldStock    = max(0.0, (float) $ingredient->current_stock);
        $reason      = trim((string) ($request->reason ?: 'Koreksi stok fisik di rak'));

        // Hitung selisih
        $difference = $actualStock - $oldStock;
        $lossValue  = 0;
        $soldValue  = 0;
        $isSpoilage = (bool) preg_match('/\b(busuk|susut|layu|rusak|tumpah|hilang)\b/i', $reason);

        // Jika minus: cek apakah karena Barang Busuk/Susut atau karena Terjual Harian
        if ($difference < 0) {
            $lostQty = abs($difference);
            if ($isSpoilage) {
                $lossValue = round($lostQty * (float) $ingredient->cost_per_unit, 2);

                Expense::create([
                    'user_id'      => Auth::id() ?: 1,
                    'expense_date' => now()->format('Y-m-d'),
                    'category'     => 'Barang Busuk/Susut',
                    'amount'       => $lossValue,
                    'description'  => "Koreksi Fisik (Barang Busuk/Susut): {$ingredient->name} (-{$lostQty} {$ingredient->unit}). Alasan: {$reason}",
                ]);
            } else {
                // Otomatis catat sebagai penjualan harian (Omset Kotor)
                $menuItem = $ingredient->recipeItems()->first()?->recipe?->menuItem;
                if ($menuItem) {
                    $today = Carbon::today()->toDateString();
                    $dailySale = DailySale::firstOrCreate(
                        ['sale_date' => $today, 'notes' => 'Rekap Otomatis Update Stok Malam'],
                        [
                            'user_id'        => Auth::id() ?: 1,
                            'payment_method' => 'Tunai',
                            'total_revenue'  => 0,
                            'total_hpp'      => 0,
                            'gross_profit'   => 0,
                        ]
                    );

                    $ratio = max(0.001, (float) ($ingredient->recipeItems()->first()?->quantity ?? 1));
                    $soldRetailQty = max(1, (int) round($lostQty / $ratio));

                    $saleItem = DailySaleItem::makeFromQty($dailySale, $menuItem, $soldRetailQty, 'Eceran');
                    $saleItem->save();
                    $dailySale->recalculateTotals();
                    $soldValue = (float) $saleItem->subtotal_revenue;

                    $menuItem->current_stock = max(0, (int) $menuItem->current_stock - $soldRetailQty);
                    $menuItem->save();
                }
            }
        }

        // Update stok fisik dan sinkronkan status stok secara otomatis (Habis / Menipis / Aman)
        $ingredient->current_stock          = $actualStock;
        $ingredient->stock_status           = $ingredient->determineStockStatus();
        $ingredient->last_status_updated_at = now();
        $ingredient->save();

        if ($difference < 0 && $isSpoilage) {
            $msg = "Koreksi fisik {$ingredient->name} disimpan. Susut " . abs($difference) . " {$ingredient->unit} (Rp " . number_format($lossValue, 0, ',', '.') . ") tercatat pada pengeluaran Barang Busuk/Susut.";
        } elseif ($difference < 0 && $soldValue > 0) {
            $msg = "Stok fisik {$ingredient->name} disesuaikan menjadi {$actualStock} {$ingredient->unit}. Terjual " . abs($difference) . " {$ingredient->unit} (+Rp " . number_format($soldValue, 0, ',', '.') . " ke Omset Kotor Hari Ini).";
        } else {
            $msg = "Stok fisik {$ingredient->name} berhasil disesuaikan menjadi {$actualStock} {$ingredient->unit} (" . strtoupper($ingredient->stock_status) . ").";
        }

        if ($request->expectsJson() || $request->ajax()) {
            $stockFormatted = number_format(
                (float) $ingredient->current_stock,
                (float) $ingredient->current_stock == (int) $ingredient->current_stock ? 0 : 1,
                ',',
                '.'
            );
            $badgeText = match ($ingredient->stock_status) {
                Ingredient::STATUS_HABIS   => "0 {$ingredient->unit} (HABIS)",
                Ingredient::STATUS_MENIPIS => "{$stockFormatted} {$ingredient->unit} (MENIPIS)",
                default                    => "{$stockFormatted} {$ingredient->unit} (AMAN)",
            };

            return response()->json([
                'success'       => true,
                'id'            => $ingredient->id,
                'name'          => $ingredient->name,
                'stock_status'  => $ingredient->stock_status,
                'current_stock' => (float) $ingredient->current_stock,
                'min_stock'     => (float) $ingredient->min_stock,
                'unit'          => $ingredient->unit,
                'badge_text'    => $badgeText,
                'message'       => $msg,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function storeProductionOpname(Request $request)
    {
        $request->validate([
            'menu_id'      => 'required|exists:menu_items,id',
            'actual_stock' => 'required|numeric|min:0',
            'reason'       => 'required|string|max:255',
        ]);

        $menu       = MenuItem::findOrFail($request->menu_id);
        $difference = (float) $request->actual_stock - (float) $menu->current_stock;

        if ($difference < 0) {
            $lossValue = round(abs($difference) * (float) $menu->hpp, 2);
            Expense::create([
                'user_id'      => Auth::id(),
                'expense_date' => now()->format('Y-m-d'),
                'category'     => 'Barang Busuk/Susut',
                'amount'       => $lossValue,
                'description'  => "Koreksi Stok Etalase: {$menu->name}. Alasan: {$request->reason}",
            ]);
        }

        $menu->current_stock = (int) $request->actual_stock;
        $menu->save();

        return redirect()->back()->with('success', 'Stok fisik barang etalase siap jual berhasil disesuaikan.');
    }
}
