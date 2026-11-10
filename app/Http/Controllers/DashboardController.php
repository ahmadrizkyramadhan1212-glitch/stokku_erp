<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DailySale;
use App\Models\DailySaleItem;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Purchase;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', 'today');

        $querySales     = DailySale::query();
        $queryPurchases = Purchase::query();
        $queryExpenses  = Expense::query();

        if ($period === 'yesterday') {
            $date = Carbon::yesterday();
            $querySales->whereDate('sale_date', $date);
            $queryPurchases->whereDate('purchase_date', $date);
            $queryExpenses->whereDate('expense_date', $date);
            $periodLabel = 'Kemarin';
        } elseif ($period === 'this_week') {
            $start = Carbon::now()->subDays(6)->startOfDay();
            $end   = Carbon::now()->endOfDay();
            $querySales->whereBetween('sale_date', [$start, $end]);
            $queryPurchases->whereBetween('purchase_date', [$start, $end]);
            $queryExpenses->whereBetween('expense_date', [$start, $end]);
            $periodLabel = 'Minggu Ini';
        } elseif ($period === 'this_month') {
            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfMonth();
            $querySales->whereBetween('sale_date', [$start, $end]);
            $queryPurchases->whereBetween('purchase_date', [$start, $end]);
            $queryExpenses->whereBetween('expense_date', [$start, $end]);
            $periodLabel = 'Bulan Ini';
        } else {
            // default: today
            $period = 'today';
            $date   = Carbon::today();
            $querySales->whereDate('sale_date', $date);
            $queryPurchases->whereDate('purchase_date', $date);
            $queryExpenses->whereDate('expense_date', $date);
            $periodLabel = 'Hari Ini';
        }

        $sales          = $querySales->get();
        $totalRev       = (float) $sales->sum('total_revenue');
        $totalHpp       = (float) $sales->sum('total_hpp');
        $grossProfit    = (float) $sales->sum('gross_profit');
        $totalPurchases = (float) $queryPurchases->sum('total_amount');
        $totalExpenses  = (float) $queryExpenses->sum('amount');

        // Total Belanja Struk Pasar & Pengeluaran = Purchase + Expense pada periode terpilih
        $totalPengeluaran = $totalPurchases + $totalExpenses;

        // Untung (Laba Bersih Warung) = Omzet dikurangi Belanja Struk Pasar & Pengeluaran
        $netProfit = $totalRev - $totalPengeluaran;

        $txCount = DailySaleItem::whereIn('daily_sale_id', $sales->pluck('id'))->count() ?: $sales->count();
        $purchaseCount = (clone $queryPurchases)->count();
        $marginPercent = $totalRev > 0 ? round(($netProfit / $totalRev) * 100, 1) : 0;

        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();
        $monthlyRev = (float) DailySale::whereBetween('sale_date', [$monthStart, $monthEnd])->sum('total_revenue');
        $monthlyPur = (float) Purchase::whereBetween('purchase_date', [$monthStart, $monthEnd])->sum('total_amount');
        $monthlyExp = (float) Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount');

        $kpi = [
            'revenue'         => $totalRev,
            'pengeluaran'     => $totalPengeluaran,
            'purchases_total' => $totalPurchases,
            'expenses_total'  => $totalExpenses,
            'profit'          => $netProfit,
            'gross_profit'    => $grossProfit,
            'monthly_revenue' => $monthlyRev,
            'monthly_profit'  => $monthlyRev - ($monthlyPur + $monthlyExp),
            'fc_percent'      => $totalRev > 0 ? round(($totalHpp / $totalRev) * 100, 2) : 0,
            'margin_percent'  => $marginPercent,
            'tx_count'        => $txCount,
            'avg_tx'          => $txCount > 0 ? round($totalRev / $txCount) : 0,
            'purchase_count'  => $purchaseCount,
            'current_period'  => $period,
            'period_label'    => $periodLabel,
        ];

        // Ambil seluruh komoditas warung & hitung skor SPK SAW untuk Prioritas Belanja Pagi Ini
        $ingredients = Ingredient::with(['supplier', 'recipeItems.recipe.menuItem'])
            ->where('is_active', true)
            ->get();

        $sawItems = $this->calculateSawPriorities($ingredients);

        $fifoItems = $sawItems->where('is_fifo', true)->values();
        $criticalItems = $sawItems->whereIn('stock_status', [Ingredient::STATUS_HABIS, Ingredient::STATUS_MENIPIS])->values();
        $estimatedModal = $criticalItems->sum('estimated_restock_cost');

        $menus = MenuItem::where('is_active', true)->take(6)->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        return view('dashboard', compact(
            'kpi',
            'sawItems',
            'fifoItems',
            'criticalItems',
            'estimatedModal',
            'menus',
            'suppliers',
            'customers'
        ));
    }

    /**
     * Hitung skor Simple Additive Weighting (SAW) untuk merangking prioritas belanja pagi.
     * Bobot kriteria:
     *  - C1 (0.40): Tingkat Kekosongan Stok (habis=100, menipis+FIFO=80, menipis=75, mendekati min=40, aman=10)
     *  - C2 (0.20): Tingkat Cepat Busuk (perishability_score 1..3)
     *  - C3 (0.25): Tingkat Kelarisan (popularity_score 1..5)
     *  - C4 (0.15): Rasio Margin Keuntungan Eceran
     */
    private function calculateSawPriorities($ingredients)
    {
        if ($ingredients->isEmpty()) {
            return collect();
        }

        $rows = $ingredients->map(function (Ingredient $ing) {
            $menu = $ing->recipeItems->first()?->recipe?->menuItem;

            // C1: Kekosongan Stok
            if ($ing->stock_status === Ingredient::STATUS_HABIS || (float) $ing->current_stock <= 0) {
                $c1 = 100.0;
            } elseif ($ing->stock_status === Ingredient::STATUS_MENIPIS || (float) $ing->current_stock <= (float) $ing->min_stock) {
                $c1 = $ing->is_fifo ? 80.0 : 75.0;
            } elseif ((float) $ing->current_stock <= 1.5 * (float) $ing->min_stock) {
                $c1 = 40.0;
            } else {
                $c1 = 10.0;
            }

            // C2: Perishability (1..3)
            $c2 = max(1.0, (float) $ing->perishability_score);

            // C3: Popularity (1..5)
            $c3 = max(1.0, (float) $ing->popularity_score);

            // C4: Margin Ratio % — null jika harga belum diatur (HPP/harga jual = 0), diisi median netral di bawah
            $priceUnset = (float) $ing->cost_per_unit <= 0 || ($menu && (float) $menu->price_eceran <= 0);
            $marginRatio = 20.0;
            if (! $priceUnset && $menu && (float) $menu->price_eceran > 0) {
                $marginRatio = max(5.0, (((float) $menu->price_eceran - (float) $menu->hpp) / (float) $menu->price_eceran) * 100);
            }
            $c4 = $priceUnset ? null : $marginRatio;
            $ing->price_unset = $priceUnset;

            return [
                'ingredient' => $ing,
                'menu'       => $menu,
                'c1'         => $c1,
                'c2'         => $c2,
                'c3'         => $c3,
                'c4'         => $c4,
            ];
        });

        $pricedC4 = $rows->pluck('c4')->filter(fn ($v) => $v !== null)->values();
        $neutralC4 = $pricedC4->isNotEmpty() ? (float) $pricedC4->median() : 20.0;
        $rows = $rows->map(function (array $row) use ($neutralC4) {
            $row['c4'] = $row['c4'] ?? $neutralC4;
            return $row;
        });

        $maxC1 = max(1.0, (float) $rows->max('c1'));
        $maxC2 = max(1.0, (float) $rows->max('c2'));
        $maxC3 = max(1.0, (float) $rows->max('c3'));
        $maxC4 = max(1.0, (float) $rows->max('c4'));

        $scored = $rows->map(function (array $row) use ($maxC1, $maxC2, $maxC3, $maxC4) {
            /** @var Ingredient $ing */
            $ing = $row['ingredient'];

            $r1 = $row['c1'] / $maxC1;
            $r2 = $row['c2'] / $maxC2;
            $r3 = $row['c3'] / $maxC3;
            $r4 = $row['c4'] / $maxC4;

            $vi = round((0.40 * $r1) + (0.20 * $r2) + (0.25 * $r3) + (0.15 * $r4), 3);

            // Tentukan ikon & saran aksi berdasarkan karakteristik komoditas
            $nameLower = strtolower($ing->name);
            if (str_contains($nameLower, 'cabai') || str_contains($nameLower, 'bawang')) {
                $icon = 'nutrition';
            } elseif (str_contains($nameLower, 'daun') || str_contains($nameLower, 'sayur')) {
                $icon = 'eco';
            } elseif (str_contains($nameLower, 'jagung') || str_contains($nameLower, 'tepung') || str_contains($nameLower, 'beras')) {
                $icon = 'grain';
            } elseif (str_contains($nameLower, 'minyak')) {
                $icon = 'water_drop';
            } else {
                $icon = 'inventory_2';
            }

            if ($ing->stock_status === Ingredient::STATUS_HABIS) {
                $urgencyLabel = $ing->perishability_score >= 3 ? 'Stok Habis & Cepat Busuk' : 'Stok Habis';
                $advice = "Wajib dibeli pagi ini di pasar induk. Stok habis total dan perputaran sangat tinggi (Star BCG). Pastikan cek fisik segar sebelum ditimbang.";
            } elseif ($ing->is_fifo) {
                $urgencyLabel = 'Menipis & Wajib FIFO';
                $advice = $ing->fifo_note ?: 'Kupas & bungkus plastik eceran sisa kemarin sebelum membongkar stok baru dari pasar agar terhindar dari susut busuk.';
            } elseif ($ing->stock_status === Ingredient::STATUS_MENIPIS) {
                $urgencyLabel = 'Stok Menipis';
                $advice = "Stok di bawah batas minimum ({$ing->min_stock} {$ing->unit}). Siapkan belanja restock {$ing->recommended_restock_qty} {$ing->unit} pada kulakan berikutnya.";
            } else {
                $urgencyLabel = 'Stok Aman';
                $advice = 'Stok gudang masih mencukupi untuk operasional harian warung.';
            }

            $ing->saw_score              = $vi;
            $ing->saw_percent            = (int) round($vi * 100);
            $ing->icon_name              = $icon;
            $ing->urgency_label          = $urgencyLabel;
            $ing->action_advice          = $advice;
            $ing->estimated_restock_cost = (float) $ing->recommended_restock_qty * (float) $ing->cost_per_unit;
            $ing->linked_menu            = $row['menu'];

            return $ing;
        });

        // Urutkan berdasarkan urgensi stok & skor SAW tertinggi
        $statusPriority = [
            Ingredient::STATUS_HABIS   => 3,
            Ingredient::STATUS_MENIPIS => 2,
            Ingredient::STATUS_AMAN    => 1,
        ];

        return $scored
            ->sort(function (Ingredient $a, Ingredient $b) use ($statusPriority) {
                $pa = $statusPriority[$a->stock_status] ?? 0;
                $pb = $statusPriority[$b->stock_status] ?? 0;
                if ($pa !== $pb) {
                    return $pb <=> $pa;
                }
                if ($a->is_fifo !== $b->is_fifo) {
                    return $b->is_fifo <=> $a->is_fifo;
                }
                return $b->saw_score <=> $a->saw_score;
            })
            ->values()
            ->map(function (Ingredient $ing, int $idx) {
                $ing->saw_rank = $idx + 1;
                return $ing;
            });
    }
}
