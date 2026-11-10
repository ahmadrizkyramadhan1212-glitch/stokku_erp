<?php

namespace App\Http\Controllers;

use App\Models\DailySale;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    /**
     * Endpoint untuk Asisten AI "Falah" Warung Adhi Jaya.
     * Mendukung pertanyaan cepat dari database lokal, perintah update stok via chat/suara,
     * serta integrasi Gemini API dengan fallback lokal cerdas dalam bahasa warung yang ramah.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $message = trim((string) $request->input('message', ''));
        $action = trim((string) $request->input('action', ''));

        if ($message === '' && $action === '') {
            return response()->json([
                'success' => false,
                'reply' => 'Silakan ketik pertanyaan atau pilih salah satu tombol pertanyaan cepat di bawah.',
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // 1. PERTANYAAN CEPAT DARI DATABASE LOKAL WARUNG
        // ─────────────────────────────────────────────────────────────────────
        if ($action === 'wajib_beli' || $this->matchesIntent($message, ['wajib dibeli', 'rekomendasi belanja', 'belanja besok', 'kulakan besok', 'pasar besok'])) {
            return response()->json([
                'success' => true,
                'reply' => $this->getWajibBeliResponse(),
                'source' => 'local_db',
            ]);
        }

        if ($action === 'stok_habis' || $this->matchesIntent($message, ['stok habis', 'barang habis', 'barang yang habis', 'komoditas habis', 'daftar habis'])) {
            return response()->json([
                'success' => true,
                'reply' => $this->getStokHabisResponse(),
                'source' => 'local_db',
            ]);
        }

        if ($action === 'sisa_kemarin' || $this->matchesIntent($message, ['sisa kemarin', 'jual duluan', 'dijual duluan', 'stok kemarin', 'stok lama'])) {
            return response()->json([
                'success' => true,
                'reply' => $this->getSisaKemarinResponse(),
                'source' => 'local_db',
            ]);
        }

        if ($action === 'untung_hari_ini' || $this->matchesIntent($message, ['untung hari ini', 'keuntungan bersih', 'laba hari ini', 'omzet hari ini', 'keuangan hari ini', 'laba bersih'])) {
            return response()->json([
                'success' => true,
                'reply' => $this->getUntungHariIniResponse(),
                'source' => 'local_db',
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // 2. DETEKSI PERINTAH UPDATE STATUS STOK LEWAT CHAT / SUARA
        // Contoh: "stok cabai rawit habis", "tomat menipis", "bawang merah aman"
        // ─────────────────────────────────────────────────────────────────────
        $updateResult = $this->tryAutoUpdateStockStatus($message);
        if ($updateResult !== null) {
            return response()->json([
                'success' => true,
                'reply' => $updateResult['reply'],
                'updated_item' => $updateResult['item'],
                'source' => 'auto_stock_update',
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // 3. PENCARIAN INFORMASI SPESIFIK BARANG WARUNG
        // Contoh: "cek stok telur", "berapa harga cabai", "stok beras"
        // ─────────────────────────────────────────────────────────────────────
        $itemInquiry = $this->tryGetItemInfo($message);
        if ($itemInquiry !== null) {
            return response()->json([
                'success' => true,
                'reply' => $itemInquiry,
                'source' => 'local_db_catalog',
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // 4. PERTANYAAN UMUM: GEMINI API DENGAN FALLBACK LOKAL CERDAS
        // ─────────────────────────────────────────────────────────────────────
        $geminiReply = $this->askGeminiApi($message);
        if ($geminiReply !== null) {
            return response()->json([
                'success' => true,
                'reply' => $geminiReply,
                'source' => 'gemini_api',
            ]);
        }

        return response()->json([
            'success' => true,
            'reply' => $this->getLocalFallbackResponse($message),
            'source' => 'local_fallback',
        ]);
    }

    /**
     * 1A. Respons: Apa yang wajib dibeli besok?
     */
    private function getWajibBeliResponse(): string
    {
        $urgentItems = Ingredient::whereIn('stock_status', ['habis', 'menipis'])
            ->orWhereColumn('current_stock', '<=', 'min_stock')
            ->orderByRaw("CASE WHEN stock_status = 'habis' THEN 1 WHEN stock_status = 'menipis' THEN 2 ELSE 3 END")
            ->orderBy('cost_per_unit', 'desc')
            ->take(8)
            ->get();

        if ($urgentItems->isEmpty()) {
            return "*Semua Stok Barang Masih Aman*\nAlhamdulillah, saat ini tidak ada barang yang kosong atau menipis di rak warung. Modal belanja besok dapat difokuskan untuk menambah barang yang paling laris.";
        }

        $totalEstimatedCost = $urgentItems->sum(fn ($i) => (float) $i->recommended_restock_qty * (float) $i->cost_per_unit);
        $count = $urgentItems->count();

        $lines = ["*Daftar Prioritas Belanja Pasar Besok Pagi:*"];
        $lines[] = "Ada *{$count} barang utama* yang perlu segera dibeli ke pasar:";
        $lines[] = "";

        foreach ($urgentItems as $idx => $item) {
            $num = $idx + 1;
            $statusLabel = strtoupper($item->stock_status);
            $qty = rtrim(rtrim(number_format($item->recommended_restock_qty, 2, ',', '.'), '0'), ',');
            $estRp = number_format($item->recommended_restock_qty * $item->cost_per_unit, 0, ',', '.');
            $lines[] = "{$num}. *{$item->name}* [{$statusLabel}]";
            $lines[] = "   - Saran Beli: *+{$qty} {$item->unit}* (sekitar Rp {$estRp})";
        }

        $lines[] = "";
        $lines[] = "*Total Estimasi Modal Belanja:* Rp " . number_format($totalEstimatedCost, 0, ',', '.');
        $lines[] = "*Saran Falah:* Berangkat belanja subuh agar mendapatkan sayur dan bumbu dengan kualitas paling segar.";

        return implode("\n", $lines);
    }

    /**
     * 1B. Respons: Cek barang yang habis malam ini
     */
    private function getStokHabisResponse(): string
    {
        $habisItems = Ingredient::where('stock_status', 'habis')
            ->orWhere('current_stock', '<=', 0)
            ->orderBy('name')
            ->get();

        if ($habisItems->isEmpty()) {
            return "*Alhamdulillah, Tidak Ada Barang yang Habis*\nSeluruh barang dagangan di rak dan gudang warung saat ini masih tersedia.";
        }

        $count = $habisItems->count();
        $lines = ["*Daftar Barang yang Habis Malam Ini ({$count} Barang):*"];
        $lines[] = "Barang berikut tercatat kosong (0) di rak dan wajib masuk daftar belanja besok subuh:";
        $lines[] = "";

        foreach ($habisItems as $idx => $item) {
            $num = $idx + 1;
            $restockQty = rtrim(rtrim(number_format($item->recommended_restock_qty, 2, ',', '.'), '0'), ',');
            $lines[] = "{$num}. *{$item->name}* ({$item->kategori})";
            $lines[] = "   - Sisa Rak: 0 {$item->unit} | Saran Kulakan: +{$restockQty} {$item->unit}";
        }

        $lines[] = "";
        $lines[] = "Seluruh barang di atas sudah Falah masukkan ke urutan teratas daftar belanja besok pagi.";

        return implode("\n", $lines);
    }

    /**
     * 1C. Respons: Barang sisa kemarin yang harus dijual duluan
     */
    private function getSisaKemarinResponse(): string
    {
        $sisaItems = Ingredient::where('current_stock', '>', 0)
            ->where(function ($q) {
                $q->where('perishability_score', '>=', 4)
                    ->orWhereIn('kategori', ['Sayur & Buah Segar', 'Bumbu Dapur & Rempah']);
            })
            ->orderByDesc('perishability_score')
            ->orderByDesc('current_stock')
            ->take(8)
            ->get();

        if ($sisaItems->isEmpty()) {
            return "*Semua Stok di Rak Merupakan Barang Baru*\nSaat ini tidak ada stok sayur atau bumbu sisa kemarin yang rawan layu.";
        }

        $lines = ["*Barang Sisa Kemarin yang Harus Dijual Duluan:*"];
        $lines[] = "Letakkan barang segar berikut di bagian paling depan rak agar laku lebih dulu sebelum membuka stok baru:";
        $lines[] = "";

        foreach ($sisaItems as $idx => $item) {
            $num = $idx + 1;
            $stockStr = rtrim(rtrim(number_format($item->current_stock, 2, ',', '.'), '0'), ',');
            $lines[] = "{$num}. *{$item->name}* (Sisa: *{$stockStr} {$item->unit}*)";
            $lines[] = "   - Petunjuk Rak: Taruh di depan meja pajangan dan tawarkan lebih dulu ke pembeli pagi.";
        }

        $lines[] = "";
        $lines[] = "*Saran Falah:* Pisahkan tumpukan stok kemarin dan stok baru agar sayur tidak mudah busuk atau menyusut.";

        return implode("\n", $lines);
    }

    /**
     * 1D. Respons: Berapa keuntungan bersih hari ini?
     */
    private function getUntungHariIniResponse(): string
    {
        $today = Carbon::today();
        $todayRevenue = (float) DailySale::whereDate('sale_date', $today)->sum('total_revenue');
        $todayPurchases = (float) Purchase::whereDate('purchase_date', $today)->sum('total_amount');
        $todayExpenses = (float) Expense::whereDate('expense_date', $today)->sum('amount');
        $todaySpoilage = (float) Expense::whereDate('expense_date', $today)->where('category', 'LIKE', '%Susut%')->sum('amount');
        $todayOperational = max(0.0, $todayExpenses - $todaySpoilage);
        $todayNetProfit = $todayRevenue - ($todayPurchases + $todayExpenses);

        $statusStr = $todayNetProfit >= 0 ? 'Alhamdulillah Untung / Surplus' : 'Sedang Kulakan Besar (Modal Masuk Stok)';

        $lines = ["*Ringkasan Keuntungan Warung Hari Ini:*"];
        $lines[] = "Tanggal: " . $today->translatedFormat('l, d F Y');
        $lines[] = "";
        $lines[] = "- Pemasukan Kotor (Omzet): *Rp " . number_format($todayRevenue, 0, ',', '.') . "*";
        $lines[] = "- Modal Belanja Pasar: *- Rp " . number_format($todayPurchases, 0, ',', '.') . "*";
        $lines[] = "- Biaya Operasional Warung: *- Rp " . number_format($todayOperational, 0, ',', '.') . "*";
        if ($todaySpoilage > 0) {
            $lines[] = "- Susut Barang Layu/Rusak: *- Rp " . number_format($todaySpoilage, 0, ',', '.') . "*";
        }
        $lines[] = "------------------------------------";
        $lines[] = "*Keuntungan Bersih Hari Ini: Rp " . number_format($todayNetProfit, 0, ',', '.') . "*";
        $lines[] = "Kondisi Kas: *{$statusStr}*";

        return implode("\n", $lines);
    }

    /**
     * 2. Otomatis Update Status Stok via Chat (Ketik atau Suara)
     * Contoh: "stok cabai rawit habis", "tomat menipis", "bawang merah aman"
     */
    private function tryAutoUpdateStockStatus(string $message): ?array
    {
        $lower = strtolower($message);

        $statusTarget = null;
        if (preg_match('/\b(habis|kosong)\b/i', $lower)) {
            $statusTarget = 'habis';
        } elseif (preg_match('/\b(menipis|sedikit|kritis|hampir habis)\b/i', $lower)) {
            $statusTarget = 'menipis';
        } elseif (preg_match('/\b(aman|banyak|cukup|penuh)\b/i', $lower)) {
            $statusTarget = 'aman';
        }

        if (! $statusTarget) {
            return null;
        }

        $ingredients = Ingredient::all();
        $matchedIngredient = null;
        $longestMatchLen = 0;

        foreach ($ingredients as $ing) {
            $ingNameLower = strtolower($ing->name);
            if (str_contains($lower, $ingNameLower)) {
                if (strlen($ingNameLower) > $longestMatchLen) {
                    $matchedIngredient = $ing;
                    $longestMatchLen = strlen($ingNameLower);
                }
            }
        }

        if (! $matchedIngredient) {
            $words = explode(' ', preg_replace('/[^a-z0-9 ]/', '', $lower));
            $ignoreWords = ['stok', 'barang', 'toko', 'warung', 'habis', 'kosong', 'menipis', 'sedikit', 'aman', 'tolong', 'ubah', 'jadi', 'sudah', 'bikin', 'set'];
            foreach ($words as $w) {
                if (strlen($w) >= 4 && ! in_array($w, $ignoreWords)) {
                    $candidate = Ingredient::where('name', 'LIKE', "%{$w}%")->first();
                    if ($candidate) {
                        $matchedIngredient = $candidate;
                        break;
                    }
                }
            }
        }

        if (! $matchedIngredient) {
            return null;
        }

        $matchedIngredient->stock_status = $statusTarget;
        if ($statusTarget === 'habis') {
            $matchedIngredient->current_stock = 0;
        } elseif ($statusTarget === 'menipis') {
            $matchedIngredient->current_stock = max(1.0, round((float) $matchedIngredient->minimum_stock * 0.5, 1));
        } elseif ($statusTarget === 'aman' && (float) $matchedIngredient->current_stock <= 0) {
            $matchedIngredient->current_stock = max(1.0, (float) $matchedIngredient->minimum_stock * 2);
        }
        $matchedIngredient->save();

        $statusUpper = strtoupper($statusTarget);
        $sisaStr = rtrim(rtrim(number_format($matchedIngredient->current_stock, 2, ',', '.'), '0'), ',');

        $reply = "*Status Stok Berhasil Diperbarui oleh Falah*\n\n"
            . "- Nama Barang: *{$matchedIngredient->name}*\n"
            . "- Status Baru: *{$statusUpper}*\n"
            . "- Sisa di Rak: *{$sisaStr} {$matchedIngredient->unit}*\n\n"
            . "Perubahan ini sudah otomatis tersimpan di halaman Stok dan masuk ke urutan belanja pasar besok pagi.";

        return [
            'reply' => $reply,
            'item' => [
                'id' => $matchedIngredient->id,
                'name' => $matchedIngredient->name,
                'status' => $statusTarget,
                'current_stock' => $matchedIngredient->current_stock,
            ],
        ];
    }

    /**
     * 3. Cari Info Spesifik Barang dari Katalog Warung
     */
    private function tryGetItemInfo(string $message): ?string
    {
        $lower = strtolower($message);
        $checkKeywords = ['berapa', 'cek', 'harga', 'stok', 'info', 'ada', 'lihat', 'sisa'];
        $hasInquiryKeyword = false;
        foreach ($checkKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $hasInquiryKeyword = true;
                break;
            }
        }

        if (! $hasInquiryKeyword) {
            return null;
        }

        $ingredients = Ingredient::all();
        $matched = null;
        $longest = 0;

        foreach ($ingredients as $ing) {
            $ingLower = strtolower($ing->name);
            if (str_contains($lower, $ingLower) && strlen($ingLower) > $longest) {
                $matched = $ing;
                $longest = strlen($ingLower);
            }
        }

        if (! $matched) {
            return null;
        }

        $statusLabel = strtoupper($matched->stock_status ?: 'aman');
        $stock = rtrim(rtrim(number_format($matched->current_stock, 2, ',', '.'), '0'), ',');
        $min = rtrim(rtrim(number_format($matched->minimum_stock, 2, ',', '.'), '0'), ',');
        $restock = rtrim(rtrim(number_format($matched->recommended_restock_qty, 2, ',', '.'), '0'), ',');
        $cost = number_format($matched->cost_per_unit, 0, ',', '.');

        $lines = ["*Rincian Stok Barang: {$matched->name}*"];
        $lines[] = "- Kelompok Rak: {$matched->kategori}";
        $lines[] = "- Kondisi Saat Ini: *{$statusLabel}*";
        $lines[] = "- Sisa Stok Fisik: *{$stock} {$matched->unit}* (Batas Aman: {$min} {$matched->unit})";
        $lines[] = "- Harga Modal Pasar: Rp {$cost} / {$matched->unit}";
        $lines[] = "- Saran Beli Besok: *+{$restock} {$matched->unit}*";

        if ((int) ($matched->perishability_score ?? 1) >= 4 && (float) $matched->current_stock > 0) {
            $lines[] = "- Pengingat: *Sisa Kemarin - Jual Duluan* (taruh di bagian depan rak).";
        }

        return implode("\n", $lines);
    }

    /**
     * 4. Integrasi Gemini API (Dengan Fallback Lokal Cerdas & Bebas Emoji/Istilah Teknis)
     */
    private function askGeminiApi(string $userMessage): ?string
    {
        $apiKey = (string) env('GEMINI_API_KEY', '');
        if ($apiKey === '') {
            return null;
        }

        try {
            $habisCount = Ingredient::where('stock_status', 'habis')->count();
            $menipisCount = Ingredient::where('stock_status', 'menipis')->count();
            $systemInstruction = "Anda adalah Falah, Asisten AI Warung Sembako & Sayur Adhi Jaya (STOKKU). "
                . "Warung menjual 96 barang sayur, bumbu dapur, sembako, dan minyak. "
                . "Saat ini terdapat {$habisCount} barang habis dan {$menipisCount} barang menipis di rak. "
                . "Jawablah dengan ringkas (maksimal 3-4 kalimat/poin), sopan, ramah, dan mudah dipahami pedagang warung dalam bahasa Indonesia. "
                . "ATURAN PENTING: DILARANG menggunakan emoji sama sekali dan DILARANG menyebut istilah rumus/teknis seperti SAW, SPK, FIFO, BCG, atau Regresi.";

            $response = Http::timeout(6)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => "{$systemInstruction}\n\nPertanyaan pedagang: \"{$userMessage}\""],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 300,
                    ],
                ]
            );

            if ($response->successful()) {
                $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
                $cleanText = $this->stripEmojis(trim($text));
                if ($cleanText !== '') {
                    return $cleanText;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini API Falah error/timeout: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Fallback Lokal Cerdas jika Gemini API tidak aktif / sedang offline
     */
    private function getLocalFallbackResponse(string $userMessage): string
    {
        $habisCount = Ingredient::where('stock_status', 'habis')->count();
        $menipisCount = Ingredient::where('stock_status', 'menipis')->count();

        return "Assalamu'alaikum, Falah siap membantu kelancaran stok dan belanja Warung Adhi Jaya.\n\n"
            . "*Kondisi Rak Saat Ini:* Tercatat *{$habisCount} barang habis* dan *{$menipisCount} barang menipis*.\n\n"
            . "*Panduan Cepat Menggunakan Falah:*\n"
            . "1. Ketuk salah satu tombol pertanyaan cepat di bawah untuk melihat daftar belanja besok, barang habis, sisa kemarin, atau keuntungan bersih.\n"
            . "2. Ketik nama barang untuk cek sisa stok (contoh: *cek stok cabai rawit* atau *harga telur*).\n"
            . "3. Ketik perintah langsung untuk ubah status rak (contoh: *stok tomat habis* atau *bawang merah menipis*).";
    }

    /**
     * Bersihkan emoji dari string jika ada dari respons eksternal
     */
    private function stripEmojis(string $text): string
    {
        return trim((string) preg_replace('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}]/u', '', $text));
    }

    /**
     * Pembantu pencocokan kata kunci
     */
    private function matchesIntent(string $text, array $keywords): bool
    {
        $lower = strtolower($text);
        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) {
                return true;
            }
        }
        return false;
    }
}
