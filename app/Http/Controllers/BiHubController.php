<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailySale;
use App\Models\DailySaleItem;
use App\Models\MenuItem;
use App\Models\ProductionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BiHubController extends Controller
{
    private function getPeriodDates($period)
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        if ($period == 'this_month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        } elseif ($period == 'last_month') {
            $startDate = Carbon::now()->subMonth()->startOfMonth();
            $endDate = Carbon::now()->subMonth()->endOfMonth();
        }

        return [$startDate, $endDate];
    }

    public function index(Request $request)
    {
        if (! $request->user()?->isOwner()) {
            return redirect()->route('dashboard')->with('error', 'Akses khusus Owner');
        }

        return redirect()->route('reports.sales_trend', $request->query());
    }

    public function salesTrend(Request $request)
    {
        if (! $request->user()?->isOwner()) {
            return redirect()->route('dashboard')->with('error', 'Akses khusus Owner');
        }

        $period = $request->query('period', 'last_30_days');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        $salesTrend = DailySale::select(
                DB::raw('DATE(sale_date) as date'),
                DB::raw('SUM(total_revenue) as revenue')
            )
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(sale_date)'))
            ->orderBy('date', 'asc')
            ->get();

        $chartDates = $salesTrend->pluck('date')->map(function($date) {
            return Carbon::parse($date)->format('d M');
        });
        $chartRevenues = $salesTrend->pluck('revenue');

        return view('reports.sales_trend', compact('period', 'chartDates', 'chartRevenues'));
    }

    public function menuRanking(Request $request)
    {
        if (! $request->user()?->isOwner()) {
            return redirect()->route('dashboard')->with('error', 'Akses khusus Owner');
        }

        $period = $request->query('period', 'last_30_days');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        $topMenus = DailySaleItem::select(
                'menu_item_id',
                DB::raw('SUM(qty_sold) as total_qty'),
                DB::raw('SUM(contribution_margin) as total_cm')
            )
            ->whereHas('dailySale', function($q) use ($startDate, $endDate) {
                $q->whereBetween('sale_date', [$startDate, $endDate]);
            })
            ->with('menuItem')
            ->groupBy('menu_item_id')
            ->orderBy('total_qty', 'desc')
            ->take(10)
            ->get();

        return view('reports.menu_ranking', compact('period', 'topMenus'));
    }

    public function wasteAnalysis(Request $request)
    {
        if (! $request->user()?->isOwner()) {
            return redirect()->route('dashboard')->with('error', 'Akses khusus Owner');
        }

        $period = $request->query('period', 'last_30_days');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        // Ambil menu yang terjual dulu
        $topMenus = DailySaleItem::select(
                'menu_item_id',
                DB::raw('SUM(qty_sold) as total_qty')
            )
            ->whereHas('dailySale', function($q) use ($startDate, $endDate) {
                $q->whereBetween('sale_date', [$startDate, $endDate]);
            })
            ->with('menuItem')
            ->groupBy('menu_item_id')
            ->orderBy('total_qty', 'desc')
            ->take(10)
            ->get();

        $productions = ProductionLog::select(
                'menu_item_id',
                DB::raw('SUM(quantity) as total_prod')
            )
            ->whereBetween('production_date', [$startDate, $endDate])
            ->groupBy('menu_item_id')
            ->get()->keyBy('menu_item_id');

        $stackedLabels = [];
        $stackedSales = [];
        $stackedWaste = [];

        foreach ($topMenus as $menu) {
            $menuId = $menu->menu_item_id;
            $salesQty = $menu->total_qty;
            $prodQty = isset($productions[$menuId]) ? $productions[$menuId]->total_prod : 0;
            
            $waste = max(0, $prodQty - $salesQty);
            $actualSalesForChart = min($salesQty, $prodQty > 0 ? $prodQty : $salesQty);
            if ($prodQty == 0) {
                $actualSalesForChart = $salesQty;
            }
            
            if($menu->menuItem) {
                $stackedLabels[] = $menu->menuItem->name;
                $stackedSales[] = $actualSalesForChart;
                $stackedWaste[] = $waste;
            }
        }

        return view('reports.waste_analysis', compact('period', 'stackedLabels', 'stackedSales', 'stackedWaste'));
    }
}
