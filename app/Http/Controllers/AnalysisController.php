<?php

namespace App\Http\Controllers;

use App\Models\DailySale;
use App\Models\DailySaleItem;
use App\Models\MenuItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalysisController extends Controller
{
    public function klasifikasi()
    {
        $dataScore = $this->calculateDataConfidence();
        $classificationResult = $this->runClassification();

        return view('analysis.klasifikasi', compact('dataScore', 'classificationResult'));
    }

    public function prediksi()
    {
        $dataScore = $this->calculateDataConfidence();
        $regressionResult = $this->runRegression();

        return view('analysis.prediksi', compact('dataScore', 'regressionResult'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INDIKATOR KECERDASAN DATA (Data Confidence Score)
    // ─────────────────────────────────────────────────────────────────────────
    private function calculateDataConfidence(): array
    {
        $totalTransactions = DailySale::count();
        $distinctDays = DailySale::select(DB::raw('DATE(created_at) as sale_date'))
            ->distinct()
            ->count();

        // Hitung persentase confidence (0 - 98%) dengan proteksi penuh
        $dayRatio = min(1.0, $distinctDays / 30);
        $txRatio = min(1.0, $totalTransactions / 60);
        $scorePercent = (int) round(25 + ($dayRatio * 45) + ($txRatio * 28));

        if ($distinctDays < 7 || $totalTransactions < 10) {
            return [
                'level' => 'low',
                'label' => 'Data Baru / Estimasi Awal',
                'color' => 'red',
                'days' => $distinctDays,
                'transactions' => $totalTransactions,
                'score_percent' => max(25, $scorePercent),
                'message' => "Warung baru memiliki {$totalTransactions} nota transaksi dari {$distinctDays} hari aktif. Komoditas baru yang belum terjual otomatis dianalisis menggunakan estimasi harga jual eceran & HPP tanpa risiko error.",
            ];
        } elseif ($distinctDays < 30 || $totalTransactions < 30) {
            return [
                'level' => 'moderate',
                'label' => 'Cukup Akurat (Berkembang)',
                'color' => 'amber',
                'days' => $distinctDays,
                'transactions' => $totalTransactions,
                'score_percent' => max(65, $scorePercent),
                'message' => "Terdapat {$totalTransactions} nota transaksi dari {$distinctDays} hari operasional warung. Klasifikasi Matriks BCG sudah cukup akurat untuk memetakan komoditas unggulan.",
            ];
        } else {
            return [
                'level' => 'high',
                'label' => 'Sangat Akurat (Data Matang)',
                'color' => 'emerald',
                'days' => $distinctDays,
                'transactions' => $totalTransactions,
                'score_percent' => min(98, max(85, $scorePercent)),
                'message' => "Basis data sangat kuat ({$totalTransactions} nota transaksi dari {$distinctDays} hari operasional). Hasil klasifikasi Matriks BCG & prediksi permintaan memiliki tingkat kepercayaan tinggi.",
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KLASIFIKASI MATRIKS BCG (4 Kuadran: Star, Plowhorse, Puzzle, Dog)
    // Dilengkapi Confidence Score per-item & Proteksi Division by Zero
    // ─────────────────────────────────────────────────────────────────────────
    private function runClassification(): array
    {
        $allMenus = MenuItem::with('recipe.items.ingredient')->get();

        if ($allMenus->isEmpty()) {
            return [
                'items' => [],
                'threshold_qty' => 0,
                'threshold_margin' => 0,
                'avg_mm' => 0,
                'avg_cm' => 0,
                'total_sold_all' => 0,
                'quadrant_counts' => [
                    'Star' => 0,
                    'Plowhorse' => 0,
                    'Puzzle' => 0,
                    'Dog' => 0,
                ],
            ];
        }

        // Ambil agregasi penjualan per MenuItem (bisa 0 untuk barang baru)
        $salesByMenu = DailySaleItem::select(
            'menu_item_id',
            DB::raw('SUM(qty_sold) as total_qty'),
            DB::raw('SUM(subtotal_revenue) as total_revenue'),
            DB::raw('SUM(subtotal_hpp) as total_hpp'),
            DB::raw('COUNT(DISTINCT daily_sale_id) as tx_count'),
            DB::raw('COUNT(DISTINCT DATE(created_at)) as active_days')
        )
            ->groupBy('menu_item_id')
            ->get()
            ->keyBy('menu_item_id');

        $totalQtyAll = (float) $salesByMenu->sum('total_qty');
        $totalProfitAll = 0.0;
        $menuStats = [];

        foreach ($allMenus as $menu) {
            $sale = $salesByMenu->get($menu->id);
            $totalQty = $sale ? (float) $sale->total_qty : 0.0;
            $totalRevenue = $sale ? (float) $sale->total_revenue : 0.0;
            $totalHpp = $sale ? (float) $sale->total_hpp : 0.0;
            $txCount = $sale ? (int) $sale->tx_count : 0;
            $activeDays = $sale ? (int) $sale->active_days : 0;
            $isNewItem = ($totalQty <= 0);

            // Proteksi Division by Zero untuk barang baru yang belum punya histori penjualan:
            // Gunakan selisih harga jual eceran - HPP sebagai estimasi margin per unit
            $theoreticalMargin = max(0.0, (float) $menu->price_eceran - (float) $menu->hpp);
            $profitPerUnit = $totalQty > 0
                ? (($totalRevenue - $totalHpp) / max($totalQty, 1.0))
                : $theoreticalMargin;

            $totalProfit = $totalQty > 0 ? ($totalRevenue - $totalHpp) : 0.0;
            $totalProfitAll += $totalProfit;

            // Menu Mix (%) dengan proteksi penuh Division by Zero
            $mmPercent = $totalQtyAll > 0
                ? (($totalQty / max($totalQtyAll, 1.0)) * 100.0)
                : 0.0;

            // Confidence Score per komoditas (0-100%)
            if ($isNewItem) {
                $confidenceScore = 25;
                $confidenceBadge = '25% • Barang Baru (Estimasi Harga)';
            } else {
                $dayFactor = min(1.0, $activeDays / 14);
                $txFactor = min(1.0, $txCount / 10);
                $confidenceScore = (int) round(45 + ($dayFactor * 35) + ($txFactor * 18));
                $confidenceBadge = $confidenceScore . '% • ' . ($confidenceScore >= 80 ? 'Data Historis Kuat' : 'Data Cukup');
            }

            $ingredient = $menu->primary_ingredient;

            $menuStats[$menu->id] = [
                'id' => $menu->id,
                'name' => $menu->name,
                'kategori' => $ingredient?->kategori ?? ($menu->category ?: 'Komoditas Warung'),
                'unit' => $menu->sale_unit ?: ($ingredient?->unit ?? 'unit'),
                'price_eceran' => (float) $menu->price_eceran,
                'hpp' => (float) $menu->hpp,
                'total_qty' => $totalQty,
                'mm_percent' => round($mmPercent, 2),
                'total_profit' => $totalProfit,
                'profit_per_unit' => $profitPerUnit,
                'is_new_item' => $isNewItem,
                'confidence_score' => $confidenceScore,
                'confidence_badge' => $confidenceBadge,
                'active_days' => $activeDays,
                'tx_count' => $txCount,
            ];
        }

        // Hitung Threshold BCG (Menu Engineering Benchmark) dengan proteksi Division by Zero
        $numberOfMenus = max($allMenus->count(), 1);
        $avgMenuMixRatio = (1.0 / $numberOfMenus) * 0.70;
        $thresholdQty = $totalQtyAll > 0 ? ($totalQtyAll * $avgMenuMixRatio) : 1.0;
        $avgMmPercent = $avgMenuMixRatio * 100.0;

        $thresholdMargin = $totalQtyAll > 0
            ? ($totalProfitAll / max($totalQtyAll, 1.0))
            : (float) collect($menuStats)->avg('profit_per_unit');

        $quadrantCounts = [
            'Star' => 0,
            'Plowhorse' => 0,
            'Puzzle' => 0,
            'Dog' => 0,
        ];

        $classified = [];
        foreach ($menuStats as $stat) {
            $highVolume = ($totalQtyAll > 0) && ($stat['total_qty'] >= $thresholdQty);
            $highMargin = $stat['profit_per_unit'] >= $thresholdMargin;

            if ($highVolume && $highMargin) {
                $bcgQuadrant = 'Star';
                $category = 'Unggulan';
                $badge = '⭐ Star (Unggulan)';
                $advice = 'Komoditas primadona warung (Laris & Untung Besar)! Pastikan stok selalu tersedia setiap pagi dan letakkan di etalase paling depan.';
                $color = 'emerald';
            } elseif ($highVolume && !$highMargin) {
                $bcgQuadrant = 'Plowhorse';
                $category = 'Kuda Pekerja (Laris)';
                $badge = '🐴 Plowhorse (Penarik Pembeli)';
                $advice = 'Sangat laris dicari pelanggan namun margin tipis. Pertahankan sebagai penarik pembeli dan tawarkan bundel dengan bumbu margin tinggi.';
                $color = 'sky';
            } elseif (!$highVolume && $highMargin) {
                $bcgQuadrant = 'Puzzle';
                $category = 'Potensial (Untung Besar)';
                $badge = '🧩 Puzzle (Potensial)';
                $advice = $stat['is_new_item']
                    ? 'Barang baru dengan margin keuntungan tinggi (belum ada histori transaksi). Pajang di area kasir agar pelanggan mulai membeli.'
                    : 'Margin keuntungan per unit tebal, namun volume terjual masih rendah. Buat paket belanja hemat agar perputaran lebih cepat.';
                $color = 'amber';
            } else {
                $bcgQuadrant = 'Dog';
                $category = 'Kurang Berkembang';
                $badge = '🐶 Dog (Evaluasi Stok)';
                $advice = $stat['is_new_item']
                    ? 'Barang baru dengan margin standar dan belum ada histori transaksi. Pantau penjualan minggu pertama sebelum menambah kulakan.'
                    : 'Kurang laris dan margin tipis. Kurangi kuantitas kulakan pasar agar modal tidak mengendap atau layu di gudang.';
                $color = 'red';
            }

            $quadrantCounts[$bcgQuadrant]++;

            $classified[] = array_merge($stat, [
                'bcg_quadrant' => $bcgQuadrant,
                'category' => $category,
                'badge' => $badge,
                'advice' => $advice,
                'color' => $color,
            ]);
        }

        // Urutkan: Star -> Plowhorse -> Puzzle -> Dog, lalu berdasarkan total_qty
        $order = ['Star' => 4, 'Plowhorse' => 3, 'Puzzle' => 2, 'Dog' => 1];
        usort($classified, function ($a, $b) use ($order) {
            $diff = ($order[$b['bcg_quadrant']] ?? 0) <=> ($order[$a['bcg_quadrant']] ?? 0);
            return $diff !== 0 ? $diff : ($b['total_qty'] <=> $a['total_qty']);
        });

        return [
            'items' => $classified,
            'threshold_qty' => round($thresholdQty, 1),
            'threshold_margin' => round($thresholdMargin, 0),
            'avg_mm' => round($avgMmPercent, 1),
            'avg_cm' => round($thresholdMargin, 0),
            'total_sold_all' => $totalQtyAll,
            'quadrant_counts' => $quadrantCounts,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PREDIKSI PERMINTAAN KOMODITAS (Simple Linear Regression + Proteksi 0 Data)
    // ─────────────────────────────────────────────────────────────────────────
    private function runRegression(): array
    {
        $menus = MenuItem::with('recipe.items.ingredient')->get();
        $predictions = [];

        foreach ($menus as $menu) {
            $ingredient = $menu->primary_ingredient;
            $unit = $menu->sale_unit ?: ($ingredient?->unit ?? 'unit');

            $dailySales = DailySaleItem::select(
                'daily_sales.sale_date as sale_date',
                DB::raw('SUM(daily_sale_items.qty_sold) as daily_qty')
            )
                ->join('daily_sales', 'daily_sales.id', '=', 'daily_sale_items.daily_sale_id')
                ->where('daily_sale_items.menu_item_id', $menu->id)
                ->groupBy('daily_sales.sale_date')
                ->orderBy('daily_sales.sale_date', 'asc')
                ->get();

            $n = $dailySales->count();

            // Penanganan aman untuk barang baru (< 2 titik data penjualan)
            if ($n < 2) {
                $fallbackQty = $n === 1
                    ? (int) round((float) $dailySales->first()->daily_qty)
                    : max(1, (int) round(($ingredient?->recommended_restock_qty ?? 4) / 2));

                $nextDays = [];
                for ($i = 1; $i <= 3; $i++) {
                    $nextDays[] = [
                        'day_label' => Carbon::now()->addDays($i)->translatedFormat('D, d M'),
                        'predicted_qty' => $fallbackQty,
                    ];
                }

                $predictions[] = [
                    'name' => $menu->name,
                    'unit' => $unit,
                    'avg_daily' => $n === 1 ? round((float) $dailySales->first()->daily_qty, 1) : 0,
                    'slope' => 0,
                    'trend' => 'stabil',
                    'confidence' => $n === 0 ? '25% (Barang Baru - Estimasi Awal)' : '40% (Data Awal)',
                    'data_points' => $n,
                    'predictions' => $nextDays,
                    'is_new_item' => ($n === 0),
                ];
                continue;
            }

            $sumX = 0.0;
            $sumY = 0.0;
            $sumXY = 0.0;
            $sumX2 = 0.0;

            foreach ($dailySales->values() as $index => $row) {
                $x = $index + 1;
                $y = (float) $row->daily_qty;
                $sumX += $x;
                $sumY += $y;
                $sumXY += ($x * $y);
                $sumX2 += ($x * $x);
            }

            $denominator = ($n * $sumX2) - ($sumX * $sumX);
            if (abs($denominator) < 0.0001) {
                $b = 0.0;
                $a = $sumY / max($n, 1);
            } else {
                $b = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
                $a = ($sumY - ($b * $sumX)) / max($n, 1);
            }

            $nextDays = [];
            for ($i = 1; $i <= 3; $i++) {
                $futureX = $n + $i;
                $predicted = max(0, (int) round($a + ($b * $futureX)));
                $nextDays[] = [
                    'day_label' => Carbon::now()->addDays($i)->translatedFormat('D, d M'),
                    'predicted_qty' => $predicted,
                ];
            }

            $meanY = $sumY / max($n, 1);
            $ssTot = 0.0;
            $ssRes = 0.0;
            foreach ($dailySales->values() as $index => $row) {
                $x = $index + 1;
                $y = (float) $row->daily_qty;
                $yHat = $a + ($b * $x);
                $ssTot += pow($y - $meanY, 2);
                $ssRes += pow($y - $yHat, 2);
            }
            $rSquared = $ssTot > 0 ? max(0, round((1 - ($ssRes / $ssTot)) * 100, 0)) : 50;

            $predictions[] = [
                'name' => $menu->name,
                'unit' => $unit,
                'avg_daily' => round($meanY, 1),
                'slope' => round($b, 2),
                'trend' => $b > 0.3 ? 'naik' : ($b < -0.3 ? 'turun' : 'stabil'),
                'confidence' => min(98, max(45, $rSquared)) . '%',
                'data_points' => $n,
                'predictions' => $nextDays,
                'is_new_item' => false,
            ];
        }

        return ['predictions' => $predictions];
    }
}
