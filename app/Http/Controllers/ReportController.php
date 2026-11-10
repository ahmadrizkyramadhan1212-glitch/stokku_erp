<?php

namespace App\Http\Controllers;

use App\Models\DailySale;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\MenuItem;
use App\Models\Purchase;
use App\Services\WaReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Halaman Pusat Laporan, Pencatatan Pengeluaran Operasional, WA 23:00 & BI Hub.
     */
    public function index(Request $request)
    {
        if (! $request->user()?->isOwner()) {
            return redirect()->route('dashboard')->with('error', 'Akses khusus Owner');
        }

        $period = $request->query('period', 'today');
        $today  = Carbon::today();

        // 1. Ringkasan Hari Ini (Omset Kotor & Omset Bersih Real-Time)
        $todayRevenue   = (float) DailySale::whereDate('sale_date', $today)->sum('total_revenue');
        $todayHpp       = (float) DailySale::whereDate('sale_date', $today)->sum('total_hpp');
        $todayPurchases = (float) Purchase::whereDate('purchase_date', $today)->sum('total_amount');
        $todayExpenses  = (float) Expense::whereDate('expense_date', $today)->sum('amount');
        $todaySpoilage  = (float) Expense::whereDate('expense_date', $today)
            ->where('category', 'like', '%Busuk%')
            ->sum('amount');
        $todayOperationalExpenses = max(0.0, $todayExpenses - $todaySpoilage);
        $todayNetProfit = $todayRevenue - ($todayPurchases + $todayExpenses);

        // 2. Ringkasan Bulan Ini (30 Hari Terakhir / Bulan Berjalan)
        $startOfMonth   = Carbon::now()->startOfMonth();
        $endOfMonth     = Carbon::now()->endOfMonth();
        $monthRevenue   = (float) DailySale::whereBetween('sale_date', [$startOfMonth, $endOfMonth])->sum('total_revenue');
        $monthHpp       = (float) DailySale::whereBetween('sale_date', [$startOfMonth, $endOfMonth])->sum('total_hpp');
        $monthGross     = (float) DailySale::whereBetween('sale_date', [$startOfMonth, $endOfMonth])->sum('gross_profit');
        $monthPurchases = (float) Purchase::whereBetween('purchase_date', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $monthExpenses  = (float) Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->sum('amount');
        $monthSpoilage  = (float) Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->where('category', 'like', '%Busuk%')
            ->sum('amount');
        $monthNetProfit = $monthRevenue - ($monthPurchases + $monthExpenses);

        // 3. Nilai Aset Gudang & Rasio Rotasi Stok (ITR) + Margin Rata-Rata
        $ingredients = Ingredient::where('is_active', true)->orderBy('name')->get();
        $totalAssetValue = (float) $ingredients->sum(fn (Ingredient $i) => (float) $i->current_stock * (float) $i->cost_per_unit);

        $avgMarginPct = $monthRevenue > 0
            ? round((($monthRevenue - $monthHpp) / $monthRevenue) * 100, 1)
            : 28.4;

        $itrScore = $totalAssetValue > 0
            ? round(max(1.2, ($monthHpp / max($totalAssetValue, 1)) / 2), 1)
            : 4.2;

        // 4. Data Analisis SPK SAW & Matriks BCG untuk Kartu 1
        $spkController = app(SpkController::class);
        $spkData = $spkController->buildSpkData(30, $spkController->resolveWeights($request));
        $topSawItems = ($spkData['sawItems'] ?? collect())->take(3);
        $menusBcg = $spkData['menus'] ?? collect();
        $bcgSummary = [
            'Star'      => $menusBcg->where('bcg_category', 'Star')->count(),
            'Plowhorse' => $menusBcg->where('bcg_category', 'Plowhorse')->count(),
            'Puzzle'    => $menusBcg->where('bcg_category', 'Puzzle')->count(),
            'Dog'       => $menusBcg->where('bcg_category', 'Dog')->count(),
        ];

        // 5. Daftar Pengeluaran Operasional Terbaru
        $recentExpenses = Expense::orderByDesc('expense_date')
            ->orderByDesc('id')
            ->take(8)
            ->get();

        // 6. Data Kartu Laporan Tutup Warung via WhatsApp (23:00 WIB)
        $waCard = app(WaReportService::class)->cardData($request->user());

        return view('reports.index', compact(
            'period',
            'todayRevenue',
            'todayHpp',
            'todayPurchases',
            'todayExpenses',
            'todaySpoilage',
            'todayOperationalExpenses',
            'todayNetProfit',
            'monthRevenue',
            'monthHpp',
            'monthGross',
            'monthPurchases',
            'monthExpenses',
            'monthSpoilage',
            'monthNetProfit',
            'totalAssetValue',
            'avgMarginPct',
            'itrScore',
            'topSawItems',
            'bcgSummary',
            'recentExpenses',
            'waCard'
        ));
    }

    /**
     * Unduh Laporan Keuangan Laba Rugi Bersih (PDF).
     */
    public function exportFinance(Request $request)
    {
        if (! $request->user()?->isOwner()) {
            return redirect()->route('dashboard')->with('error', 'Akses khusus Owner');
        }

        $period = $request->query('period', 'this_month');

        $querySales     = DailySale::query();
        $queryPurchases = Purchase::query();
        $queryExpenses  = Expense::query();

        if ($period === 'today') {
            $date = Carbon::today();
            $querySales->whereDate('sale_date', $date);
            $queryPurchases->whereDate('purchase_date', $date);
            $queryExpenses->whereDate('expense_date', $date);
            $periodLabel = 'Hari Ini (' . $date->translatedFormat('d F Y') . ')';
        } elseif ($period === 'this_week') {
            $start = Carbon::now()->subDays(6)->startOfDay();
            $end   = Carbon::now()->endOfDay();
            $querySales->whereBetween('sale_date', [$start, $end]);
            $queryPurchases->whereBetween('purchase_date', [$start, $end]);
            $queryExpenses->whereBetween('expense_date', [$start, $end]);
            $periodLabel = 'Minggu Ini (' . $start->translatedFormat('d M Y') . ' - ' . $end->translatedFormat('d M Y') . ')';
        } elseif ($period === 'this_month') {
            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfMonth();
            $querySales->whereBetween('sale_date', [$start, $end]);
            $queryPurchases->whereBetween('purchase_date', [$start, $end]);
            $queryExpenses->whereBetween('expense_date', [$start, $end]);
            $periodLabel = 'Bulan Ini (' . Carbon::now()->translatedFormat('F Y') . ')';
        } else {
            $periodLabel = 'Semua Waktu';
        }

        $sales     = $querySales->get();
        $purchases = $queryPurchases->get();
        $expenses  = $queryExpenses->get();

        $totalRevenue   = $sales->sum('total_revenue');
        $totalHpp       = $sales->sum('total_hpp');
        $grossProfit    = $sales->sum('gross_profit');
        $totalPurchases = $purchases->sum('total_amount');
        $totalExpenses  = $expenses->sum('amount');
        $netProfit      = $grossProfit - $totalExpenses;

        $pdf = Pdf::loadView('reports.finance_pdf', compact(
            'periodLabel',
            'sales',
            'purchases',
            'expenses',
            'totalRevenue',
            'totalHpp',
            'grossProfit',
            'totalPurchases',
            'totalExpenses',
            'netProfit'
        ));

        return $pdf->download('Laporan_Keuangan_STOKKU_ERP_' . Carbon::now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Unduh Laporan Aset Inventori Gudang & Etalase (PDF).
     */
    public function exportInventory(Request $request)
    {
        if (! $request->user()?->isOwner()) {
            return redirect()->route('dashboard')->with('error', 'Akses khusus Owner');
        }

        $ingredients = Ingredient::orderBy('name')->get();
        $menus       = MenuItem::orderBy('name')->get();

        $totalAssetValue = 0;
        foreach ($ingredients as $item) {
            $totalAssetValue += ($item->current_stock * $item->cost_per_unit);
        }

        $pdf = Pdf::loadView('reports.inventory_pdf', compact('ingredients', 'menus', 'totalAssetValue'));

        return $pdf->download('Laporan_Aset_Inventori_STOKKU_ERP_' . Carbon::now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Simpan catatan pengeluaran operasional warung (listrik, plastik kresek, bensin pasar, dll).
     */
    public function storeExpense(Request $request)
    {
        $request->validate([
            'category'     => 'required|string|max:100',
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:1',
            'expense_date' => 'required|date',
        ]);

        Expense::create([
            'user_id'      => auth()->id(),
            'category'     => $request->category,
            'description'  => $request->description,
            'amount'       => $request->amount,
            'expense_date' => $request->expense_date,
        ]);

        return redirect()->back()->with('success', 'Pengeluaran operasional warung berhasil dicatat!');
    }
}
