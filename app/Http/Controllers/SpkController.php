<?php

namespace App\Http\Controllers;

use App\Models\DailySaleItem;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\WaReportLog;
use App\Services\GeminiService;
use App\Services\WaReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpkController extends Controller
{
    /**
     * Halaman utama SPK (Metode SAW untuk Prioritas Belanja Pasar & Matriks BCG untuk Evaluasi Portofolio).
     */
    public function index(Request $request)
    {
        $days = (int) $request->input('days', 30);
        if (!in_array($days, [7, 14, 30, 90])) {
            $days = 30;
        }

        $weights = $this->resolveWeights($request);
        $analysis = $this->buildSpkData($days, $weights);
        $latestWaLog = WaReportLog::orderByDesc('report_date')->first();
        $waCard = app(WaReportService::class)->cardData($request->user());

        $tab = $request->input('tab', 'warung');
        $initialTab = in_array($tab, ['warung', 'academic']) ? $tab : 'warung';

        return view('spk.index', array_merge($analysis, [
            'days' => $days,
            'weights' => $weights,
            'latestWaLog' => $latestWaLog,
            'waCard' => $waCard,
            'initialTab' => $initialTab,
        ]));
    }

    /**
     * Unduh laporan PDF Hasil SPK (SAW Prioritas Belanja Pasar + Matriks BCG).
     */
    public function exportPdf(Request $request)
    {
        if (! $request->user()?->isOwner()) {
            return redirect()->route('dashboard')->with('error', 'Akses khusus Owner: Laporan dan estimasi modal hanya dapat diakses oleh Owner.');
        }

        $days = (int) $request->input('days', 30);
        if (!in_array($days, [7, 14, 30, 90])) {
            $days = 30;
        }

        $weights = $this->resolveWeights($request);
        $analysis = $this->buildSpkData($days, $weights);

        $pdf = Pdf::loadView('spk.pdf', array_merge($analysis, [
            'days' => $days,
            'weights' => $weights,
        ]));
        $pdf->setPaper('A4', 'portrait');

        $filename = 'Laporan_SPK_SAW_BCG_STOKKU_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Fitur 2: AI Smart Restock & Asisten Alokasi Modal Belanja (Integrasi SPK SAW).
     */
    public function aiRecommendRestock(Request $request, GeminiService $geminiService)
    {
        $budget = max(50000, (float) $request->input('budget_modal', 750000));
        $days = (int) $request->input('days', 30);
        if (!in_array($days, [7, 14, 30, 90])) {
            $days = 30;
        }

        $weights = $this->resolveWeights($request);
        $spkData = $this->buildSpkData($days, $weights);
        $sawItems = $spkData['sawItems'] ?? collect();

        // Siapkan array data ringkas ranking teratas SAW
        $topItems = $sawItems->take(20)->map(function ($it) {
            return [
                'name'                    => $it->name,
                'stock_status'            => $it->stock_status,
                'current_stock'           => (float) $it->current_stock,
                'minimum_stock'           => (float) $it->minimum_stock,
                'unit'                    => $it->unit,
                'recommended_restock_qty' => (float) $it->recommended_restock_qty,
                'cost_per_unit'           => (float) ($it->cost_per_unit ?? 0),
                'selling_price'           => (float) ($it->price_eceran ?? 0),
                'margin'                  => (float) ($it->c4_raw ?? 0),
                'perishability_score'     => (int) ($it->perishability_score ?? 3),
                'saw_score'               => (float) ($it->saw_score_exact ?? $it->saw_score ?? 0),
                'rank'                    => (int) ($it->rank ?? 1),
            ];
        })->toArray();

        $recommendation = $geminiService->recommendBudgetRestock($budget, $topItems);

        return response()->json([
            'success'        => true,
            'budget'         => $budget,
            'recommendation' => $recommendation,
        ]);
    }

    /**
     * Resolusi bobot kriteria SAW (Default: C1=35%, C2=30%, C3=20%, C4=15%).
     * Mendukung kustomisasi bobot dinamis melalui query parameter w1, w2, w3, w4.
     * Public agar bisa dipakai ulang oleh WaReportService (laporan WA tutup warung).
     */
    public function resolveWeights(Request $request): array
    {
        $w1 = (float) $request->input('w1', 35);
        $w2 = (float) $request->input('w2', 30);
        $w3 = (float) $request->input('w3', 20);
        $w4 = (float) $request->input('w4', 15);

        // Jika dikirim dalam desimal (misal 0.35), konversi ke skala persen dulu
        if ($w1 <= 1 && $w2 <= 1 && $w3 <= 1 && $w4 <= 1 && ($w1 + $w2 + $w3 + $w4) > 0) {
            $w1 *= 100;
            $w2 *= 100;
            $w3 *= 100;
            $w4 *= 100;
        }

        $sum = $w1 + $w2 + $w3 + $w4;
        if ($sum <= 0) {
            return [
                'c1' => 0.35,
                'c2' => 0.30,
                'c3' => 0.20,
                'c4' => 0.15,
                'c1_pct' => 35,
                'c2_pct' => 30,
                'c3_pct' => 20,
                'c4_pct' => 15,
            ];
        }

        return [
            'c1' => round($w1 / $sum, 4),
            'c2' => round($w2 / $sum, 4),
            'c3' => round($w3 / $sum, 4),
            'c4' => round($w4 / $sum, 4),
            'c1_pct' => round(($w1 / $sum) * 100, 0),
            'c2_pct' => round(($w2 / $sum) * 100, 0),
            'c3_pct' => round(($w3 / $sum) * 100, 0),
            'c4_pct' => round(($w4 / $sum) * 100, 0),
        ];
    }

    /**
     * Bangun data lengkap SPK Metode SAW & Matriks BCG dengan proteksi penuh Division by Zero.
     * Public agar bisa dipakai ulang oleh WaReportService (Top 5 belanja pasar di laporan WA).
     */
    public function buildSpkData(int $days, array $weights): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        // 1. Ambil agregasi penjualan per MenuItem pada periode terpilih
        $salesData = DailySaleItem::select(
            'menu_item_id',
            DB::raw('SUM(qty_sold) as total_qty'),
            DB::raw('SUM(subtotal_revenue) as total_revenue'),
            DB::raw('SUM(subtotal_hpp) as total_hpp'),
            DB::raw('COUNT(DISTINCT daily_sale_id) as tx_count'),
            DB::raw('COUNT(DISTINCT DATE(created_at)) as active_days')
        )
            ->whereHas('dailySale', function ($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })
            ->groupBy('menu_item_id')
            ->get()
            ->keyBy('menu_item_id');

        $totalActiveDaysAll = (int) DailySaleItem::whereHas('dailySale', function ($q) use ($startDate) {
            $q->where('created_at', '>=', $startDate);
        })->selectRaw('COUNT(DISTINCT DATE(created_at)) as cnt')->value('cnt');

        $datasetConfidence = min(98, max(35, (int) round(($totalActiveDaysAll / max($days, 1)) * 100)));

        // 2. Matriks BCG untuk SELURUH MenuItem (termasuk barang baru yang belum punya histori penjualan)
        $allMenus = MenuItem::with('recipe.items.ingredient')->get();

        $total_sold_all = (float) $salesData->sum('total_qty');
        $total_cm_all = 0.0;

        foreach ($allMenus as $menu) {
            $sale = $salesData->get($menu->id);
            $qty = $sale ? (float) $sale->total_qty : 0.0;
            $rev = $sale ? (float) $sale->total_revenue : 0.0;
            $hpp = $sale ? (float) $sale->total_hpp : 0.0;
            $txCount = $sale ? (int) $sale->tx_count : 0;
            $activeDays = $sale ? (int) $sale->active_days : 0;

            $menu->sold_qty = $qty;
            $menu->revenue = $rev;
            $menu->total_cost = $hpp;
            $menu->tx_count = $txCount;
            $menu->active_days = $activeDays;
            $menu->is_new_item = ($qty <= 0);

            // Margin Kontribusi per unit: gunakan realisasi penjualan jika ada, atau estimasi harga jual - HPP untuk barang baru (Aman dari Division by Zero)
            $theoreticalMargin = max(0.0, (float) $menu->price_eceran - (float) $menu->hpp);
            $menu->cm_per_item = $qty > 0
                ? (($rev - $hpp) / max($qty, 1))
                : $theoreticalMargin;

            $menu->total_cm = $qty > 0 ? ($rev - $hpp) : 0.0;
            $total_cm_all += $menu->total_cm;

            // Pangsa pasar / Menu Mix (%) dengan proteksi Division by Zero
            $menu->mm_percent = $total_sold_all > 0
                ? (($qty / max($total_sold_all, 1)) * 100)
                : 0.0;

            // Confidence Score per item (0-100%)
            if ($qty <= 0) {
                $menu->confidence_score = 25;
                $menu->confidence_label = 'Barang Baru (Estimasi Margin)';
            } else {
                $dayFactor = min(1.0, $activeDays / max(min($days, 14), 1));
                $txFactor = min(1.0, $txCount / 10);
                $menu->confidence_score = (int) round(45 + ($dayFactor * 35) + ($txFactor * 18));
                $menu->confidence_label = $menu->confidence_score >= 80
                    ? 'Tinggi (Data Historis Kuat)'
                    : 'Sedang (Data Transaksi Cukup)';
            }
        }

        $menuCount = max($allMenus->count(), 1);
        $avg_mm = $total_sold_all > 0 ? ((1 / $menuCount) * 0.7 * 100) : 0.0;
        $avg_cm = $total_sold_all > 0
            ? ($total_cm_all / max($total_sold_all, 1))
            : (float) ($allMenus->avg('cm_per_item') ?? 0.0);

        $bcgByIngredientId = [];

        foreach ($allMenus as $menu) {
            $isHighMM = ($total_sold_all > 0) && ($menu->mm_percent >= $avg_mm);
            $isHighCM = $menu->cm_per_item >= $avg_cm;

            if ($isHighMM && $isHighCM) {
                $menu->category = 'Star';
                $menu->category_label = 'Unggulan Warung (Star)';
                $menu->action = 'Komoditas primadona warung (Laris & Margin Tinggi). Jaga ketersediaan stok harian dan prioritaskan di etalase depan.';
            } elseif ($isHighMM && !$isHighCM) {
                $menu->category = 'Plowhorse';
                $menu->category_label = 'Penarik Pembeli (Plowhorse)';
                $menu->action = 'Sangat laris dicari pelanggan namun margin tipis. Pertahankan stok sebagai penarik trafik belanja & bundel dengan bumbu margin tinggi.';
            } elseif (!$isHighMM && $isHighCM) {
                $menu->category = 'Puzzle';
                $menu->category_label = 'Potensial / Margin Tinggi (Puzzle)';
                $menu->action = $menu->is_new_item
                    ? 'Komoditas baru dengan potensi margin tinggi. Tawarkan aktif ke pelanggan warung untuk mulai membangun histori penjualan.'
                    : 'Keuntungan per unit tinggi namun perputaran masih lambat. Beli secukupnya di pasar dan tawarkan paket hemat ke pelanggan.';
            } else {
                $menu->category = 'Dog';
                $menu->category_label = 'Evaluasi Stok (Dog)';
                $menu->action = $menu->is_new_item
                    ? 'Komoditas baru dengan margin standar dan belum ada transaksi. Pantau penjualan minggu pertama sebelum menambah stok gudang.'
                    : 'Perputaran lambat dan margin tipis. Kurangi jumlah kulakan pasar agar modal tidak mengendap atau membusuk di gudang.';
            }

            if ($menu->recipe && $menu->recipe->items) {
                foreach ($menu->recipe->items as $recipeItem) {
                    if ($recipeItem->ingredient_id) {
                        $bcgByIngredientId[$recipeItem->ingredient_id] = [
                            'category' => $menu->category,
                            'category_label' => $menu->category_label,
                            'menu_name' => $menu->name,
                            'sale_unit' => $menu->sale_unit ?: 'unit',
                            'ratio' => max(0.001, (float) $recipeItem->quantity),
                            'price_eceran' => (float) $menu->price_eceran,
                            'hpp' => (float) $menu->hpp,
                            'sold_qty' => (float) $menu->sold_qty,
                            'cm_per_item' => (float) $menu->cm_per_item,
                            'confidence_score' => (int) $menu->confidence_score,
                        ];
                    }
                }
            }
        }

        $menus = $allMenus->sortByDesc(function ($menu) {
            $order = ['Star' => 4, 'Plowhorse' => 3, 'Puzzle' => 2, 'Dog' => 1];
            return ($order[$menu->category] ?? 0) * 100000 + $menu->sold_qty;
        })->values();

        // 3. Perhitungan Metode SAW (Simple Additive Weighting) untuk Prioritas Belanja Pasar Pagi
        $ingredients = Ingredient::all();

        $maxSoldQty = max(1.0, (float) collect($bcgByIngredientId)->max('sold_qty'));

        // Tahap A: Bangun Matriks Keputusan (X) untuk setiap komoditas
        $sawRaw = $ingredients->map(function (Ingredient $ing) use ($bcgByIngredientId, $maxSoldQty) {
            $linked = $bcgByIngredientId[$ing->id] ?? null;
            $status = $ing->stock_status ?: $ing->determineStockStatus();
            $currentStock = (float) $ing->current_stock;
            $minStock = max(0.0, (float) $ing->minimum_stock);

            // C1: Status Stok Gudang (Benefit - Semakin habis/menipis, nilai semakin tinggi)
            if ($status === 'habis' || $currentStock <= 0) {
                $c1Raw = 100.0;
                $c1Label = 'Habis Total (0 ' . $ing->unit . ')';
            } elseif ($status === 'menipis' && $ing->is_fifo) {
                $c1Raw = 82.0;
                $c1Label = 'Menipis + Sisa Kemarin (FIFO)';
            } elseif ($status === 'menipis' || ($minStock > 0 && $currentStock <= $minStock)) {
                $c1Raw = 75.0;
                $c1Label = 'Stok Menipis (' . rtrim(rtrim(number_format($currentStock, 2, ',', '.'), '0'), ',') . ' ' . $ing->unit . ')';
            } elseif ($minStock > 0 && $currentStock <= ($minStock * 1.5)) {
                $c1Raw = 45.0;
                $c1Label = 'Mendekati Minimum';
            } else {
                $c1Raw = 15.0;
                $c1Label = 'Stok Aman (' . rtrim(rtrim(number_format($currentStock, 2, ',', '.'), '0'), ',') . ' ' . $ing->unit . ')';
            }

            // C2: Tingkat Cepat Busuk / Perishability (Skala 1-3, Benefit)
            $c2Raw = (float) max(1, min(3, (int) ($ing->perishability_score ?? 2)));
            $c2Label = match ((int) $c2Raw) {
                3 => '3/3 • Cepat Busuk (Sayur/Segar)',
                2 => '2/3 • Sedang (Bumbu Dapur)',
                default => '1/3 • Tahan Lama (Sembako)',
            };

            // C3: Tingkat Kelarisan / Popularitas (Skala 1-5 + volume terjual historis)
            $popScore = (float) max(1, min(5, (int) ($ing->popularity_score ?? 3)));
            $soldQty = $linked ? (float) $linked['sold_qty'] : 0.0;
            $salesRatio = $soldQty > 0 ? min(1.0, $soldQty / $maxSoldQty) : 0.0;
            $c3Raw = round(($popScore * 0.75) + ($salesRatio * 5.0 * 0.25), 2);
            $c3Label = 'Skor ' . (int) $popScore . '/5 (' . number_format($soldQty, 0, ',', '.') . ' terjual)';

            // C4: Margin Keuntungan (Selisih Harga Jual Eceran vs HPP/Harga Beli)
            // Proteksi Harga Rp 0: barang yang ditambahkan Staf (HPP/harga jual = 0) ditandai price_unset
            // dan C4-nya diisi nilai netral (median) di Tahap A2 agar tidak bias naik/turun.
            $costPerUnit = max(0.0, (float) $ing->cost_per_unit);
            $linkedPrice = $linked ? (float) $linked['price_eceran'] : 0.0;
            $priceUnset = $costPerUnit <= 0 || ($linked && $linkedPrice <= 0);
            if ($priceUnset) {
                $priceEceran = $linkedPrice;
                $marginPerSaleUnit = 0.0;
                $marginPct = 0.0;
                $saleUnit = $linked['sale_unit'] ?? $ing->unit;
            } elseif ($linked && $linkedPrice > 0) {
                $priceEceran = $linkedPrice;
                $unitHpp = (float) ($linked['hpp'] > 0 ? $linked['hpp'] : ($costPerUnit * $linked['ratio']));
                $marginPerSaleUnit = max(0.0, $priceEceran - $unitHpp);
                $marginPct = round(($marginPerSaleUnit / max($priceEceran, 1.0)) * 100, 1);
                $saleUnit = $linked['sale_unit'];
            } else {
                $priceEceran = round($costPerUnit * 1.25, 0);
                $marginPerSaleUnit = max(0.0, $priceEceran - $costPerUnit);
                $marginPct = 20.0;
                $saleUnit = $ing->unit;
            }
            $c4Raw = $priceUnset ? null : max(1.0, $marginPct);
            $c4Label = $priceUnset
                ? 'Harga Belum Diatur (nilai netral)'
                : 'Rp ' . number_format($marginPerSaleUnit, 0, ',', '.') . '/' . $saleUnit . ' (' . $marginPct . '%)';

            // Rekomendasi jumlah restock & estimasi biaya
            $recommendedQty = (float) ($ing->recommended_restock_qty > 0
                ? $ing->recommended_restock_qty
                : max(5, $minStock * 2));
            $estimatedCost = round($recommendedQty * $costPerUnit, 0);

            return (object) [
                'id' => $ing->id,
                'name' => $ing->name,
                'kategori' => $ing->kategori ?? 'Sayur Segar',
                'image_path' => $ing->image_path,
                'image_url' => $ing->image_url,
                'unit' => $ing->unit,
                'current_stock' => $currentStock,
                'minimum_stock' => $minStock,
                'stock_status' => $status,
                'cost_per_unit' => $costPerUnit,
                'price_eceran' => $priceEceran,
                'margin_rp' => $marginPerSaleUnit,
                'margin_pct' => $marginPct,
                'perishability_score' => (int) $c2Raw,
                'popularity_score' => (int) $popScore,
                'sold_qty' => $soldQty,
                'recommended_restock_qty' => $recommendedQty,
                'estimated_cost' => $estimatedCost,
                'is_fifo' => (bool) $ing->is_fifo,
                'price_unset' => $priceUnset,
                'fifo_note' => $ing->fifo_note,
                'bcg_category' => $linked['category'] ?? 'Puzzle',
                'bcg_category_label' => $linked['category_label'] ?? 'Potensial (Puzzle)',
                'confidence_score' => $linked['confidence_score'] ?? 65,
                'c1_raw' => $c1Raw,
                'c2_raw' => $c2Raw,
                'c3_raw' => $c3Raw,
                'c4_raw' => $c4Raw,
                'c1_label' => $c1Label,
                'c2_label' => $c2Label,
                'c3_label' => $c3Label,
                'c4_label' => $c4Label,
            ];
        });

        // Tahap A2: Isi C4 barang "Harga Belum Diatur" dengan median margin barang lain (netral, tanpa bias)
        $pricedC4 = $sawRaw->pluck('c4_raw')->filter(fn ($v) => $v !== null)->values();
        $neutralC4 = $pricedC4->isNotEmpty() ? (float) $pricedC4->median() : 20.0;
        $sawRaw = $sawRaw->map(function ($item) use ($neutralC4) {
            if ($item->c4_raw === null) {
                $item->c4_raw = $neutralC4;
            }
            return $item;
        });

        // Tahap B: Nilai Maksimum tiap kriteria untuk Normalisasi Benefit R_ij = X_ij / Max(X_j) (Aman dari Division by Zero)
        $maxC1 = max(1.0, (float) $sawRaw->max('c1_raw'));
        $maxC2 = max(1.0, (float) $sawRaw->max('c2_raw'));
        $maxC3 = max(1.0, (float) $sawRaw->max('c3_raw'));
        $maxC4 = max(1.0, (float) $sawRaw->max('c4_raw'));

        // Tahap C: Hitung Matriks Normalisasi (R) dan Nilai Preferensi Akhir (V_i)
        $sawItems = $sawRaw->map(function ($item) use ($maxC1, $maxC2, $maxC3, $maxC4, $weights) {
            $item->r1 = round($item->c1_raw / $maxC1, 4);
            $item->r2 = round($item->c2_raw / $maxC2, 4);
            $item->r3 = round($item->c3_raw / $maxC3, 4);
            $item->r4 = round($item->c4_raw / $maxC4, 4);

            $item->v1 = round($item->r1 * $weights['c1'], 4);
            $item->v2 = round($item->r2 * $weights['c2'], 4);
            $item->v3 = round($item->r3 * $weights['c3'], 4);
            $item->v4 = round($item->r4 * $weights['c4'], 4);

            $item->saw_score = round($item->v1 + $item->v2 + $item->v3 + $item->v4, 2);
            $item->saw_score_exact = round($item->v1 + $item->v2 + $item->v3 + $item->v4, 4);

            return $item;
        })->sortByDesc('saw_score_exact')->values();

        // Tahap D: Berikan Nomor Ranking, Badge Prioritas, dan "SARAN AKSI WARUNG"
        $sawItems = $sawItems->map(function ($item, $idx) {
            $rank = $idx + 1;
            $item->rank = $rank;
            $item->saw_rank = $rank;
            $item->peringkat = $rank;
            $item->estimated_restock_cost = $item->estimated_cost ?? 0;

            if ($item->stock_status === 'habis' || $item->saw_score >= 0.85) {
                $item->priority_level = 'utama';
                $item->priority_badge = '#' . $rank . ' PRIORITAS UTAMA';
                $item->card_border = 'border-red-500';
                $item->badge_class = 'bg-red-50 text-red-700 border-red-200';
                $item->score_badge_class = 'bg-red-600 text-white';
            } elseif ($item->stock_status === 'menipis' || $item->saw_score >= 0.60) {
                $item->priority_level = 'tinggi';
                $item->priority_badge = '#' . $rank . ' PRIORITAS TINGGI';
                $item->card_border = 'border-amber-500';
                $item->badge_class = 'bg-amber-50 text-amber-800 border-amber-200';
                $item->score_badge_class = 'bg-amber-500 text-slate-950';
            } else {
                $item->priority_level = 'aman';
                $item->priority_badge = '#' . $rank . ' STOK TERKENDALI';
                $item->card_border = 'border-emerald-500';
                $item->badge_class = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                $item->score_badge_class = 'bg-emerald-600 text-white';
            }

            // Narasi "SARAN AKSI WARUNG" kontekstual
            if ($item->stock_status === 'habis') {
                $item->saran_aksi = 'Wajib dibeli pagi ini di pasar sebanyak '
                    . rtrim(rtrim(number_format($item->recommended_restock_qty, 2, ',', '.'), '0'), ',') . ' ' . $item->unit
                    . ' karena stok gudang kosong (0 ' . $item->unit . ') dan tingkat permintaan pembeli tinggi (Kategori BCG: ' . $item->bcg_category . ').';
            } elseif ($item->is_fifo && $item->stock_status === 'menipis') {
                $item->saran_aksi = 'Belanja secukupnya ('
                    . rtrim(rtrim(number_format($item->recommended_restock_qty, 2, ',', '.'), '0'), ',') . ' ' . $item->unit
                    . '). ' . ($item->fifo_note ?: 'Pajang & jual stok sisa kemarin terlebih dahulu di etalase depan (Wajib FIFO) sebelum membuka karung/ikat baru.');
            } elseif ($item->stock_status === 'menipis') {
                $item->saran_aksi = 'Masukkan ke daftar belanja pasar pagi ini ('
                    . rtrim(rtrim(number_format($item->recommended_restock_qty, 2, ',', '.'), '0'), ',') . ' ' . $item->unit
                    . '). Sisa stok tinggal ' . rtrim(rtrim(number_format($item->current_stock, 2, ',', '.'), '0'), ',') . ' ' . $item->unit
                    . ' (di bawah batas aman ' . rtrim(rtrim(number_format($item->minimum_stock, 2, ',', '.'), '0'), ',') . ' ' . $item->unit . ').';
            } else {
                $item->saran_aksi = 'Stok gudang masih mencukupi ('
                    . rtrim(rtrim(number_format($item->current_stock, 2, ',', '.'), '0'), ',') . ' ' . $item->unit
                    . '). Tidak perlu kulakan mendesak pagi ini, alokasikan modal ke komoditas prioritas utama.';
            }

            return $item;
        });

        $urgentItems = $sawItems->filter(fn ($i) => in_array($i->stock_status, ['habis', 'menipis']));
        if ($urgentItems->isEmpty()) {
            $urgentItems = $sawItems->take(3);
        }

        $sawSummary = [
            'urgent_count' => $urgentItems->count(),
            'total_estimated_cost' => $urgentItems->sum('estimated_cost'),
            'all_estimated_cost' => $sawItems->sum('estimated_cost'),
            'top_item' => $sawItems->first(),
            'max_values' => [
                'c1' => $maxC1,
                'c2' => $maxC2,
                'c3' => $maxC3,
                'c4' => $maxC4,
            ],
        ];

        return [
            'sawItems' => $sawItems,
            'sawSummary' => $sawSummary,
            'menus' => $menus,
            'avg_mm' => $avg_mm,
            'avg_cm' => $avg_cm,
            'total_sold_all' => $total_sold_all,
            'total_cm_all' => $total_cm_all,
            'datasetConfidence' => $datasetConfidence,
        ];
    }
}
