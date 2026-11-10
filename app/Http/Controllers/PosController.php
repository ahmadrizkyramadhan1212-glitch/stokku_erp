<?php

namespace App\Http\Controllers;

use App\Models\DailySale;
use App\Models\DailySaleItem;
use App\Models\Ingredient;
use App\Models\MenuItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    /**
     * Halaman Pencatatan Barang Keluar & Kontrol FIFO Warung STOKKU ERP.
     * Menampilkan komoditas aktif lengkap dengan indikator stok fisik real-time
     * dan prioritas FIFO di urutan paling atas (tanpa fitur pembayaran kasir supermarket).
     */
    public function index()
    {
        $rawMenus = MenuItem::with(['recipe.items.ingredient'])
            ->where('is_active', true)
            ->get();

        $menus = $rawMenus->map(function (MenuItem $menu) {
            $ingredient = $menu->primary_ingredient;

            // Sinkronisasi stok etalase jika gudang baru direstock via Scan Struk Pasar
            // atau jika status stok gudang ditandai habis oleh karyawan
            if ($ingredient) {
                if ($ingredient->stock_status === Ingredient::STATUS_HABIS || (float) $ingredient->current_stock <= 0) {
                    $effectiveStock = 0;
                } elseif ((int) $menu->current_stock <= 0 && (float) $ingredient->current_stock > 0) {
                    $effectiveStock = max(1, $menu->production_capacity);
                    $menu->current_stock = $effectiveStock;
                    $menu->saveQuietly();
                } else {
                    $effectiveStock = (int) $menu->current_stock;
                }
            } else {
                $effectiveStock = (int) $menu->current_stock;
            }

            $stockStatus = $ingredient
                ? ($ingredient->stock_status ?: $ingredient->determineStockStatus())
                : ($effectiveStock > 5 ? Ingredient::STATUS_AMAN : ($effectiveStock > 0 ? Ingredient::STATUS_MENIPIS : Ingredient::STATUS_HABIS));

            if ($effectiveStock <= 0) {
                $stockStatus = Ingredient::STATUS_HABIS;
            }

            // Sematkan atribut tambahan untuk tampilan kartu Barang Keluar & Alpine.js
            $menu->setAttribute('effective_stock', $effectiveStock);
            $menu->setAttribute('is_fifo', (bool) ($ingredient?->is_fifo ?? false));
            $menu->setAttribute('fifo_note', $ingredient?->fifo_note ?? 'Stok sisa kemarin — keluarkan/kemas lebih dulu!');
            $menu->setAttribute('kategori', $ingredient?->kategori ?? ($menu->category ?: 'Sayur Segar'));
            $menu->setAttribute('stock_status_code', $stockStatus);
            $menu->setAttribute('ingredient_id', $ingredient?->id);
            $menu->setAttribute('ingredient_stock', (float) ($ingredient?->current_stock ?? $effectiveStock));
            $menu->setAttribute('ingredient_unit', $ingredient?->unit ?? ($menu->sale_unit ?: 'pcs'));

            return $menu;
        })
        ->sortBy([
            // 1. Barang FIFO (sisa kemarin yang masih ada stoknya) wajib paling atas!
            fn (MenuItem $a, MenuItem $b) => (($b->is_fifo && $b->effective_stock > 0) <=> ($a->is_fifo && $a->effective_stock > 0)),
            // 2. Barang yang masih ada stoknya di atas barang yang habis
            fn (MenuItem $a, MenuItem $b) => (($b->effective_stock > 0) <=> ($a->effective_stock > 0)),
            // 3. Barang FIFO walaupun stok 0 tetap mudah dipantau sebelum non-FIFO habis
            fn (MenuItem $a, MenuItem $b) => ($b->is_fifo <=> $a->is_fifo),
            // 4. Urut abjad nama komoditas
            fn (MenuItem $a, MenuItem $b) => strcmp($a->name, $b->name),
        ])
        ->values();

        $today = Carbon::today()->toDateString();
        $todaySalesCount = DailySale::whereDate('sale_date', $today)->count();
        $todayOutQty     = (int) DailySaleItem::whereHas('dailySale', fn ($q) => $q->whereDate('sale_date', $today))->sum('qty_sold');
        $todayRevenue    = (float) DailySale::whereDate('sale_date', $today)->sum('total_revenue');
        $fifoMenus       = $menus->where('is_fifo', true)->values();

        return view('pos.index', compact(
            'menus',
            'todaySalesCount',
            'todayOutQty',
            'todayRevenue',
            'fifoMenus'
        ));
    }

    /**
     * Simpan pencatatan Barang Keluar (Eceran / Reseller / Agen Grosir),
     * potong stok fisik MenuItem & Ingredient, serta perbarui stock_status gudang otomatis.
     * Di balik layar tetap menghitung nilai penjualan ke daily_sales & daily_sale_items
     * agar SPK SAW (Kriteria C3), Matriks BCG, dan Laba Bersih Owner berjalan 100%.
     */
    public function store(Request $request)
    {
        $request->validate([
            'buyer_type' => 'required|string',
            'cart'       => 'required',
        ]);

        $cart = is_array($request->cart)
            ? $request->cart
            : json_decode((string) $request->cart, true);

        if (empty($cart) || ! is_array($cart)) {
            return redirect()->back()->with('error', 'Daftar barang keluar masih kosong!');
        }

        // Normalisasi kategori saluran barang keluar (Eceran / Reseller / Agen)
        $rawBuyer = trim((string) $request->buyer_type);
        $buyerType = match (true) {
            stripos($rawBuyer, 'reseller') !== false => 'Reseller',
            stripos($rawBuyer, 'agen') !== false || stripos($rawBuyer, 'grosir') !== false => 'Agen',
            default => 'Eceran',
        };

        // Default internal untuk kolom payment_method pada tabel daily_sales
        $dbPaymentMethod = 'Tunai';

        // Validasi kecukupan stok fisik terlebih dahulu
        $validatedItems = [];
        foreach ($cart as $item) {
            $menuId = (int) ($item['id'] ?? 0);
            $qty    = (int) ($item['qty'] ?? 0);

            if ($menuId <= 0 || $qty <= 0) {
                continue;
            }

            $menu = MenuItem::with('recipe.items.ingredient')->find($menuId);
            if (! $menu) {
                continue;
            }

            $ingredient = $menu->primary_ingredient;
            $availableStock = (int) $menu->current_stock;

            if ($availableStock <= 0 && $ingredient && (float) $ingredient->current_stock > 0 && $ingredient->stock_status !== Ingredient::STATUS_HABIS) {
                $availableStock = max(1, $menu->production_capacity);
                $menu->current_stock = $availableStock;
                $menu->saveQuietly();
            }

            if ($qty > $availableStock) {
                return redirect()->back()->with(
                    'error',
                    "Stok fisik untuk komoditas {$menu->name} tidak mencukupi! (Tersedia: {$availableStock} " . ($menu->sale_unit ?: 'pcs') . ')'
                );
            }

            $validatedItems[] = [
                'menu' => $menu,
                'qty'  => $qty,
            ];
        }

        if (empty($validatedItems)) {
            return redirect()->back()->with('error', 'Tidak ada item barang keluar yang valid.');
        }

        $receiptSummary = DB::transaction(function () use ($validatedItems, $buyerType, $dbPaymentMethod) {
            $sale = DailySale::create([
                'user_id'        => Auth::id(),
                'sale_date'      => Carbon::now()->format('Y-m-d'),
                'payment_method' => $dbPaymentMethod,
                'total_revenue'  => 0,
                'total_hpp'      => 0,
                'gross_profit'   => 0,
                'notes'          => "Rekap Barang Keluar ({$buyerType})",
            ]);

            $soldNames = [];
            $totalQty  = 0;

            foreach ($validatedItems as $row) {
                /** @var MenuItem $menu */
                $menu = $row['menu'];
                $qty  = (int) $row['qty'];
                $totalQty += $qty;

                $saleItem = DailySaleItem::makeFromQty($sale, $menu, $qty, $buyerType);
                $saleItem->save();

                // 1. Kurangi stok siap jual di etalase (menu_items)
                $menu->current_stock = max(0, (int) $menu->current_stock - $qty);
                $menu->save();

                // 2. Kurangi stok gudang utama (ingredients) sesuai rasio resep & perbarui stock_status otomatis
                if ($menu->recipe) {
                    foreach ($menu->recipe->items as $recipeItem) {
                        $ingredient = $recipeItem->ingredient;
                        if ($ingredient) {
                            $ratio     = max(0.0001, (float) $recipeItem->quantity);
                            $deductQty = round($qty * $ratio, 2);

                            $newIngStock = max(0.0, (float) $ingredient->current_stock - $deductQty);
                            $ingredient->current_stock          = $newIngStock;
                            $ingredient->stock_status           = $ingredient->determineStockStatus($newIngStock);
                            $ingredient->last_status_updated_at = now();

                            // Jika stok FIFO sudah habis keluar semua, matikan flag is_fifo otomatis
                            if ($newIngStock <= 0 && $ingredient->is_fifo) {
                                $ingredient->is_fifo = false;
                            }

                            $ingredient->save();
                        }
                    }
                }

                $soldNames[] = "{$menu->name} ({$qty} " . ($menu->sale_unit ?: 'pcs') . ')';
            }

            $sale->recalculateTotals();

            return [
                'sale_id'       => $sale->id,
                'buyer_type'    => $buyerType,
                'total_qty'     => $totalQty,
                'total_revenue' => (float) $sale->total_revenue,
                'items_summary' => implode(', ', $soldNames),
                'time'          => now()->format('H:i'),
            ];
        });

        $isOwner = (Auth::user()?->role ?? 'admin') === 'admin';
        $successMessage = $isOwner
            ? 'Rekap Barang Keluar (' . $receiptSummary['buyer_type'] . ' • ' . $receiptSummary['total_qty'] . ' satuan fisik) berhasil disimpan! Stok warung & data SPK telah diperbarui.'
            : 'Catatan Barang Keluar (' . $receiptSummary['buyer_type'] . ' • ' . $receiptSummary['total_qty'] . ' satuan fisik) berhasil disimpan! Stok fisik gudang telah diperbarui.';

        return redirect()
            ->route('pos.index')
            ->with('success', $successMessage)
            ->with('last_receipt', $receiptSummary);
    }
}
