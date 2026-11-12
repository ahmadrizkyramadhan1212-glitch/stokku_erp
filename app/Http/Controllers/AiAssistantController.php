<?php

namespace App\Http\Controllers;

use App\Models\DailySale;
use App\Models\DailySaleItem;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Purchase;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiAssistantController extends Controller
{
    /**
     * Asisten Virtual Interaktif "Tanya Stokku AI" (Falah) berbasis Google Gemini AI 100%.
     * Menghubungkan kecerdasan Google Gemini dengan data real-time database Warung Adijaya.
     */
    public function chat(Request $request, GeminiService $geminiService): JsonResponse
    {
        $message = trim((string) $request->input('message', ''));
        $action  = trim((string) $request->input('action', ''));

        if ($message === '' && $action === '') {
            return response()->json([
                'success' => false,
                'reply'   => 'Silakan ketik pertanyaan atau klik salah satu tombol Quick Prompt di bawah.',
            ]);
        }

        $userMessage = $message !== '' ? $message : $action;

        // 1. Eksekusi Ubah Status Stok dengan Deteksi Fleksibel (AI Intent)
        // Contoh: "touge aman, jeruk aman", "toge aman", "cabai rawit habis", "tomat menipis"
        $appliedUpdates = $this->applyFlexibleStockUpdates($userMessage);
        $systemActionNote = null;
        if (!empty($appliedUpdates)) {
            $updateLines = [];
            foreach ($appliedUpdates as $up) {
                $statusUpper = strtoupper($up['status']);
                $updateLines[] = "- {$up['name']}: Status berhasil diubah menjadi {$statusUpper} (Sisa fisik: {$up['current_stock']} {$up['unit']})";
            }
            $systemActionNote = "Sistem telah mengeksekusi perintah user dan memperbarui tabel database stok:\n"
                . implode("\n", $updateLines) . "\n"
                . "Mohon sampaikan konfirmasi pembaruan stok di atas kepada user dengan gaya ramah dan ceria khas Falah.";
        }

        // 2. Kumpulkan Konteks Real-Time Database Warung Adijaya
        $today = Carbon::today();
        $startOfMonth = Carbon::today()->startOfMonth();
        $endOfMonth = Carbon::today()->endOfMonth();

        $todayRevenue   = (float) DailySale::whereDate('sale_date', $today)->sum('total_revenue');
        $todayPurchases = (float) Purchase::whereDate('purchase_date', $today)->sum('total_amount');
        $todayExpenses  = (float) Expense::whereDate('expense_date', $today)->sum('amount');
        $todayTxCount   = (int) DailySale::whereDate('sale_date', $today)->count();
        $todayNetProfit = $todayRevenue - ($todayPurchases + $todayExpenses);

        $monthRevenue = (float) DailySale::whereBetween('sale_date', [$startOfMonth, $endOfMonth])->sum('total_revenue');
        $monthTxCount = (int) DailySale::whereBetween('sale_date', [$startOfMonth, $endOfMonth])->count();
        $monthLabel   = Carbon::now()->translatedFormat('F Y');

        // Komoditas HABIS (Stok 0)
        $habisItems = Ingredient::where('is_active', true)
            ->where(function ($q) {
                $q->where('stock_status', 'habis')->orWhere('current_stock', '<=', 0);
            })
            ->orderBy('name')
            ->get()
            ->map(fn ($i) => "{$i->name} (Sisa: {$i->current_stock} {$i->unit}, Status: HABIS)")
            ->toArray();

        // Komoditas MENIPIS
        $menipisItems = Ingredient::where('is_active', true)
            ->where('stock_status', 'menipis')
            ->where('current_stock', '>', 0)
            ->orderBy('current_stock')
            ->get()
            ->map(fn ($i) => "{$i->name} (Sisa: {$i->current_stock} {$i->unit}, Batas Aman: {$i->min_stock} {$i->unit}, Status: MENIPIS)")
            ->toArray();

        // Antrean FIFO & Cepat Busuk (Sisa Kemarin / Rawan Busuk)
        $fifoBatches = Ingredient::where('is_active', true)
            ->where('current_stock', '>', 0)
            ->where(function ($q) {
                $q->where('is_fifo', true)->orWhere('perishability_score', '>=', 4);
            })
            ->orderByDesc('is_fifo')
            ->orderByDesc('perishability_score')
            ->take(8)
            ->get()
            ->map(fn ($i) => "{$i->name} (Sisa: {$i->current_stock} {$i->unit}, Skor Cepat Busuk: {$i->perishability_score}/5" . ($i->is_fifo ? ', Label: Sisa Kemarin' : '') . ")")
            ->toArray();

        // Top 5 Prioritas Belanja SPK SAW
        $sawTopPriorities = [];
        try {
            $spkController = app(SpkController::class);
            $weights = $spkController->resolveWeights($request);
            $spkData = $spkController->buildSpkData(30, $weights);
            $sawItems = $spkData['sawItems'] ?? collect();

            $sawTopPriorities = $sawItems->take(5)->map(function ($it, $idx) {
                $rank = $idx + 1;
                $skor = round((float) ($it->saw_score_exact ?? $it->saw_score ?? 0), 3);
                return "#{$rank} {$it->name} (Skor SAW: {$skor}, Rekomendasi Kulakan: +{$it->recommended_restock_qty} {$it->unit}, Status: " . strtoupper($it->stock_status) . ")";
            })->toArray();
        } catch (\Throwable $e) {
            $sawTopPriorities = [];
        }

        // Top Barang Laris Hari Ini
        $topSellingToday = DailySaleItem::whereHas('dailySale', fn ($q) => $q->whereDate('sale_date', $today))
            ->select('menu_item_id', DB::raw('SUM(qty_sold) as total_qty'))
            ->groupBy('menu_item_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->with('menuItem')
            ->get()
            ->map(fn ($si) => ($si->menuItem?->name ?? 'Barang') . ' (' . (float) $si->total_qty . ' terjual)')
            ->toArray();

        $context = [
            'today_revenue'      => $todayRevenue,
            'today_tx_count'     => $todayTxCount,
            'today_net_profit'   => $todayNetProfit,
            'month_revenue'      => $monthRevenue,
            'month_tx_count'     => $monthTxCount,
            'month_label'        => $monthLabel,
            'habis_items'        => $habisItems,
            'menipis_items'      => $menipisItems,
            'fifo_batches'       => $fifoBatches,
            'saw_top_priorities' => $sawTopPriorities,
            'top_selling_today'  => $topSellingToday,
            'system_action_note' => $systemActionNote,
        ];

        // 3. Eksekusi Jawaban LANGSUNG melalui Google Gemini AI
        $reply = $geminiService->chatAssistant($userMessage, $context);
        $hasError = str_contains($reply, 'Error Koneksi ke Gemini API');

        return response()->json([
            'success'        => ! $hasError,
            'reply'          => $reply,
            'updated_items'  => $appliedUpdates,
            'source'         => $hasError ? 'gemini_error' : 'Google Gemini AI',
        ]);
    }

    /**
     * Deteksi dan eksekusi perubahan status stok secara fleksibel (Multi-Item & Alias).
     * Mendukung kalimat seperti: "touge aman, jeruk aman", "toge aman", "cabai rawit habis", "tomat menipis"
     */
    protected function applyFlexibleStockUpdates(string $message): array
    {
        $lower = strtolower($message);
        // Pecah pesan berdasarkan koma, titik koma, baris baru, atau kata penghubung "dan" / "serta"
        $clauses = preg_split('/[,;\n]+|\b(dan|serta)\b/i', $lower) ?: [$lower];
        $allIngredients = Ingredient::where('is_active', true)->get();
        $applied = [];

        foreach ($clauses as $clause) {
            $clause = trim($clause);
            if ($clause === '') {
                continue;
            }

            // 1. Tentukan target status
            $targetStatus = null;
            if (preg_match('/\b(habis|kosong|nol)\b/i', $clause)) {
                $targetStatus = 'habis';
            } elseif (preg_match('/\b(menipis|sedikit|mau habis|kritis)\b/i', $clause)) {
                $targetStatus = 'menipis';
            } elseif (preg_match('/\b(aman|banyak|penuh|cukup|ready|ada)\b/i', $clause)) {
                $targetStatus = 'aman';
            }

            if ($targetStatus === null) {
                continue;
            }

            // 2. Cocokkan nama komoditas secara fleksibel
            $matched = $this->matchCommodityFlexibly($clause, $allIngredients);
            if (! $matched) {
                continue;
            }

            // Hindari duplikasi pembaruan untuk item yang sama dalam satu request
            if (collect($applied)->pluck('id')->contains($matched->id)) {
                continue;
            }

            // 3. Update database
            $oldStatus = $matched->stock_status;
            $matched->stock_status = $targetStatus;

            if ($targetStatus === 'habis') {
                $matched->current_stock = 0;
                $matched->is_fifo = false;
            } elseif ($targetStatus === 'menipis') {
                $safeMin = (float) ($matched->min_stock ?: 5);
                if ((float) $matched->current_stock > $safeMin || (float) $matched->current_stock <= 0) {
                    $matched->current_stock = $safeMin;
                }
            } elseif ($targetStatus === 'aman') {
                $safeMin = (float) ($matched->min_stock ?: 5);
                if ((float) $matched->current_stock <= $safeMin) {
                    $matched->current_stock = max($safeMin * 2, 10);
                }
            }

            $matched->last_status_updated_at = Carbon::now();
            $matched->save();

            $applied[] = [
                'id'            => $matched->id,
                'name'          => $matched->name,
                'old_status'    => $oldStatus,
                'status'        => $targetStatus,
                'current_stock' => $matched->current_stock,
                'unit'          => $matched->unit,
            ];
        }

        return $applied;
    }

    /**
     * Pencocokan komoditas fleksibel berdasarkan kata kunci, alias, dan kemiripan nama.
     */
    protected function matchCommodityFlexibly(string $text, $allIngredients): ?Ingredient
    {
        $clean = strtolower($text);

        // Buang kata-kata stopwords
        $stopwords = [
            'stok', 'status', 'kondisi', 'barang', 'buat', 'bikin', 'tolong',
            'dong', 'udah', 'sudah', 'lagi', 'aman', 'habis', 'menipis',
            'kosong', 'sedikit', 'banyak', 'cukup', 'ready', 'ada', 'itu',
            'sisa', 'tolong', 'yaa', 'ya', 'min'
        ];
        foreach ($stopwords as $w) {
            $clean = preg_replace('/\b' . preg_quote($w, '/') . '\b/i', ' ', $clean);
        }
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        if ($clean === '') {
            return null;
        }

        // Kamus Alias umum warung sayur & sembako Adijaya
        $aliases = [
            'toge'          => 'Tauge / Toge',
            'touge'         => 'Tauge / Toge',
            'tauge'         => 'Tauge / Toge',
            'kecambah'      => 'Tauge / Toge',
            'jeruk'         => 'Jeruk Makan',
            'jeruk makan'   => 'Jeruk Makan',
            'jeruk nipis'   => 'Jeruk Nipis',
            'daun jeruk'    => 'Daun Jeruk Purut',
            'cabe rawit'    => 'Cabai Kecil / Rawit',
            'cabai rawit'   => 'Cabai Kecil / Rawit',
            'cabe kecil'    => 'Cabai Kecil / Rawit',
            'cabai kecil'   => 'Cabai Kecil / Rawit',
            'cabe merah'    => 'Cabai Besar',
            'cabai merah'   => 'Cabai Besar',
            'cabe keriting' => 'Cabai Keriting',
            'cabai keriting'=> 'Cabai Keriting',
            'cabe'          => 'Cabai Kecil / Rawit',
            'cabai'         => 'Cabai Kecil / Rawit',
            'tomat'         => 'Tomat',
            'bawang merah'  => 'Bawang Merah',
            'bawang putih'  => 'Bawang Putih',
            'bawang bombay' => 'Bawang Bombay',
            'telur'         => 'Telur Ayam',
            'telor'         => 'Telur Ayam',
            'minyak'        => 'Minyak Goreng Refill 1 Liter',
            'minyak goreng' => 'Minyak Goreng Refill 1 Liter',
            'timun'         => 'Mentimun',
            'mentimun'      => 'Mentimun',
            'gula'          => 'Gula Pasir 1 Kg',
            'garam'         => 'Garam Halus',
            'beras'         => 'Beras',
        ];

        // 1. Cek kamus alias langsung
        if (isset($aliases[$clean])) {
            $target = $aliases[$clean];
            $found = $allIngredients->firstWhere('name', $target);
            if ($found) return $found;
        }

        // 2. Cek apakah string mengandung key alias
        foreach ($aliases as $aliasKey => $targetName) {
            if (str_contains($clean, $aliasKey) || str_contains($aliasKey, $clean)) {
                $found = $allIngredients->firstWhere('name', $targetName);
                if ($found) return $found;
            }
        }

        // 3. Cek pencocokan langsung pada nama katalog Ingredient
        $bestMatch = null;
        $longest = 0;
        foreach ($allIngredients as $ing) {
            $ingLower = strtolower($ing->name);
            if ($clean === $ingLower) {
                return $ing;
            }
            if (str_contains($ingLower, $clean) || str_contains($clean, $ingLower)) {
                $len = strlen($ingLower);
                if ($len > $longest) {
                    $longest = $len;
                    $bestMatch = $ing;
                }
            }
        }

        return $bestMatch;
    }
}
