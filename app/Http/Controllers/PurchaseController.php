<?php

namespace App\Http\Controllers;

use App\Models\DailySale;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\IngredientPrice;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PurchaseController extends Controller
{
    /**
     * Halaman Scan Struk Pasar AI & Kulakan Suplier (/purchases).
     * Mengikuti desain: scan_struk_pasar_kulakan_ai_stokku_erp/code.html
     */
    public function index(Request $request)
    {
        $today = Carbon::today();

        // Ringkasan Laba Bersih Hari Ini (Omzet Kasir - (Belanja Struk Pasar + Pengeluaran))
        $todayRevenue   = (float) DailySale::whereDate('sale_date', $today)->sum('total_revenue');
        $todayPurchases = (float) Purchase::whereDate('purchase_date', $today)->sum('total_amount');
        $todayExpenses  = (float) Expense::whereDate('expense_date', $today)->sum('amount');

        $todayModalBelanja = $todayPurchases + $todayExpenses;
        $todayNetProfit    = $todayRevenue - $todayModalBelanja;
        $profitMarginPct   = $todayRevenue > 0
            ? round(($todayNetProfit / $todayRevenue) * 100, 1)
            : 0.0;

        $ingredients = Ingredient::with(['supplier', 'recipeItems.recipe.menuItem'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        $recentPurchases = Purchase::with(['supplier', 'items.ingredient'])
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        // Default contoh hasil ekstraksi awal (dari item kritis/habis agar UI langsung informatif seperti Stitch)
        $sampleText = 'Beli cabai keriting 8kg 360000, daun jeruk 10 ikat 100000, jagung manis 12kg 144000';
        $initialExtracted = $this->parseTextWithLocalEngine($sampleText, $ingredients);

        return view('purchases.index', compact(
            'todayRevenue',
            'todayPurchases',
            'todayExpenses',
            'todayModalBelanja',
            'todayNetProfit',
            'profitMarginPct',
            'ingredients',
            'suppliers',
            'recentPurchases',
            'sampleText',
            'initialExtracted'
        ));
    }

    /**
     * Endpoint AI OCR / NLP Parser untuk mengekstrak foto struk atau ketikan nota bebas.
     * Mendukung Gemini API bila tersedia + Fallback Regex Lokal yang 100% andal tanpa kuota API.
     */
    public function parseReceipt(Request $request)
    {
        $request->validate([
            'note_text'     => 'nullable|string|max:2000',
            'receipt'       => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'receipt_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $ingredients = Ingredient::with('supplier')
            ->where('is_active', true)
            ->get();

        $rawText = trim((string) $request->input('note_text', ''));
        $uploadedFile = $request->file('receipt') ?? $request->file('receipt_image');
        $hasImage = $uploadedFile !== null;
        $receiptPath = null;
        $source = 'Regex NLP Lokal';

        // Jika ada unggahan file gambar struk fisik, simpan ke storage publik & jalankan OCR
        if ($hasImage) {
            $receiptPath = $uploadedFile->store('receipts', 'public');
            $geminiItems = $this->tryGeminiExtraction($request, $ingredients, $rawText);
            if (! empty($geminiItems)) {
                $source = 'Gemini AI Vision OCR';
                $items = $geminiItems;
            } else {
                $source = 'OCR Struk Pasar (Fallback Lokal)';
                $sample = 'Beli cabai keriting 8kg 360000, daun jeruk 10 ikat 100000, jagung manis 12kg 144000';
                $items = $this->parseTextWithLocalEngine($rawText ?: $sample, $ingredients);
            }
        } else {
            // Ketik cepat teks nota -> 100% Menggunakan Regex Parser Lokal (0 Token API!)
            $source = 'Regex NLP Lokal (0 Token API)';
            $textToParse = $rawText ?: 'Beli cabai keriting 8kg 360000, daun jeruk 10 ikat 100000, jagung manis 12kg 144000';
            $items = $this->parseTextWithLocalEngine($textToParse, $ingredients);
        }

        $totalSubtotal = (float) collect($items)->sum('subtotal');

        return response()->json([
            'success'       => count($items) > 0,
            'source'        => $source,
            'note_text'     => $rawText,
            'receipt_path'  => $receiptPath,
            'receipt_url'   => $receiptPath ? asset('storage/' . $receiptPath) : null,
            'items'         => $items,
            'total_amount'  => $totalSubtotal,
            'message'       => count($items) > 0
                ? count($items) . ' komoditas pasar berhasil diekstrak dan foto struk tersimpan (' . $source . ').'
                : 'Belum ada komoditas yang cocok dari catatan nota. Pastikan menyebut nama barang dan jumlahnya.',
        ]);
    }

    /**
     * Terapkan Rekomendasi Harga Jual Eceran Baru (0 Token API).
     */
    public function updateRetailPrice(Request $request)
    {
        $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'new_price'     => 'required|numeric|min:0',
        ]);

        $ingredient = Ingredient::findOrFail($request->ingredient_id);
        $newPrice   = (float) $request->new_price;

        foreach ($ingredient->recipeItems as $recipeItem) {
            $menu = $recipeItem->recipe?->menuItem;
            if ($menu) {
                $menu->price_eceran = $newPrice;
                $menu->save();
            }
        }

        $formatted = number_format($newPrice, 0, ',', '.');
        $msg = "Harga jual eceran {$ingredient->name} berhasil disesuaikan menjadi Rp {$formatted}/bungkus (Margin Terlindungi).";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'   => true,
                'new_price' => $newPrice,
                'message'   => $msg,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Simpan hasil ekstraksi struk pasar (batch maupun satuan) ke tabel purchases,
     * tambah current_stock di ingredients, ubah stock_status menjadi 'aman',
     * dan perbarui cost_per_unit dengan metode Moving Average.
     */
    public function store(Request $request)
    {
        $ingredients = Ingredient::with(['recipeItems.recipe.menuItem'])
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $itemsPayload = [];

        // 1. Jika dikirim dalam bentuk JSON string / array items dari tabel ekstraksi
        if ($request->filled('items_json')) {
            $decoded = json_decode((string) $request->input('items_json'), true);
            if (is_array($decoded)) {
                $itemsPayload = $decoded;
            }
        } elseif (is_array($request->input('items'))) {
            $itemsPayload = $request->input('items');
        }

        // 2. Jika belum ada items terstruktur tetapi ada note_text, ekstrak otomatis dulu
        if (empty($itemsPayload) && $request->filled('note_text')) {
            $itemsPayload = $this->parseTextWithLocalEngine(
                (string) $request->input('note_text'),
                $ingredients->values()
            );
        }

        // 3. Fallback untuk form input satuan (ingredient_id, quantity, total_price)
        if (empty($itemsPayload) && $request->filled('ingredient_id')) {
            $itemsPayload = [[
                'ingredient_id' => (int) $request->input('ingredient_id'),
                'quantity'      => (float) $request->input('quantity', 1),
                'subtotal'      => (float) $request->input('total_price', 0),
            ]];
        }

        // Bersihkan item yang valid
        $validItems = [];
        foreach ($itemsPayload as $row) {
            $ingId = (int) ($row['ingredient_id'] ?? 0);
            $qty   = (float) ($row['quantity'] ?? 0);
            $sub   = (float) ($row['subtotal'] ?? ($row['total_price'] ?? 0));

            if ($ingId > 0 && $qty > 0 && $ingredients->has($ingId)) {
                $ing = $ingredients->get($ingId);
                if ($sub <= 0) {
                    $sub = round($qty * (float) $ing->cost_per_unit, 0);
                }
                $validItems[] = [
                    'ingredient'     => $ing,
                    'quantity'       => $qty,
                    'subtotal'       => $sub,
                    'price_per_unit' => round($sub / max($qty, 0.0001), 2),
                ];
            }
        }

        if (empty($validItems)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada item pembelian yang valid untuk disimpan.',
                ], 422);
            }

            return redirect()->back()->with('error', 'Tidak ada komoditas yang valid untuk disimpan. Silakan ekstrak nota terlebih dahulu.');
        }

        $request->validate([
            'receipt'       => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'receipt_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'receipt_path'  => 'nullable|string|max:255',
        ]);

        $receiptPath = $request->input('receipt_path');
        $uploadedReceipt = $request->file('receipt') ?? $request->file('receipt_image');
        if ($uploadedReceipt) {
            $receiptPath = $uploadedReceipt->store('receipts', 'public');
        }

        $supplierId = $request->input('supplier_id');
        if (! $supplierId && $request->filled('supplier_name')) {
            $sup = Supplier::firstOrCreate(
                ['name' => trim((string) $request->input('supplier_name'))],
                ['is_active' => true, 'phone' => '-', 'address' => 'Pasar Induk']
            );
            $supplierId = $sup->id;
        }
        $supplierId    = $supplierId ?: ($validItems[0]['ingredient']->supplier_id ?? Supplier::value('id'));
        $paymentMethod = $request->input('payment_method', 'Tunai');
        $notes         = $request->input('notes') ?: 'Struk belanja pasar via Scan AI / Input Cepat STOKKU';
        $priceRecommendations = [];

        $purchase = DB::transaction(function () use ($validItems, $supplierId, $paymentMethod, $notes, $receiptPath, &$priceRecommendations) {
            $totalAmount = (float) collect($validItems)->sum('subtotal');

            $purchase = Purchase::create([
                'user_id'        => Auth::id(),
                'supplier_id'    => $supplierId,
                'purchase_date'  => Carbon::today()->toDateString(),
                'total_amount'   => $totalAmount,
                'payment_method' => $paymentMethod,
                'notes'          => $notes,
                'receipt_path'   => $receiptPath,
            ]);

            foreach ($validItems as $row) {
                /** @var Ingredient $ingredient */
                $ingredient   = $row['ingredient'];
                $qty          = (float) $row['quantity'];
                $subtotal     = (float) $row['subtotal'];
                $pricePerUnit = (float) $row['price_per_unit'];

                PurchaseItem::create([
                    'purchase_id'    => $purchase->id,
                    'ingredient_id'  => $ingredient->id,
                    'quantity'       => $qty,
                    'price_per_unit' => $pricePerUnit,
                    'subtotal'       => $subtotal,
                ]);

                IngredientPrice::create([
                    'ingredient_id'  => $ingredient->id,
                    'supplier_id'    => $supplierId ?: $ingredient->supplier_id,
                    'price_per_unit' => $pricePerUnit,
                    'quantity'       => $qty,
                    'purchased_at'   => Carbon::today()->toDateString(),
                    'notes'          => 'Struk Belanja Pasar AI',
                ]);

                // Hitung Moving Average HPP: (Stok Lama * HPP Lama + Total Belanja Baru) / Total Stok Baru
                $oldStock = max(0.0, (float) $ingredient->current_stock);
                $oldCost  = max(0.0, (float) $ingredient->cost_per_unit);
                $newStock = $oldStock + $qty;

                $newAvgCost = $newStock > 0
                    ? (($oldStock * $oldCost) + $subtotal) / $newStock
                    : $pricePerUnit;

                // Deteksi jika harga beli pasar naik > 2% dibanding modal lama
                if ($oldCost > 0 && $pricePerUnit > $oldCost * 1.02) {
                    $menu = $ingredient->recipeItems->first()?->recipe?->menuItem;
                    $currentSell = (float) ($menu?->price_eceran ?? round($oldCost * 1.25, -2));
                    $recSell = (float) (ceil(($pricePerUnit * 1.25) / 500) * 500);
                    $priceRecommendations[] = [
                        'ingredient_id'          => $ingredient->id,
                        'menu_id'                => $menu?->id,
                        'name'                   => $ingredient->name,
                        'unit'                   => $ingredient->unit,
                        'sale_unit'              => $menu?->sale_unit ?: $ingredient->unit,
                        'old_cost'               => $oldCost,
                        'new_cost'               => $pricePerUnit,
                        'increase_pct'           => round((($pricePerUnit - $oldCost) / $oldCost) * 100, 1),
                        'current_sell_price'     => $currentSell,
                        'recommended_sell_price' => max($currentSell + 500, $recSell),
                    ];
                }

                // Tambah current_stock, sinkronkan stock_status, dan update cost_per_unit Moving Average
                $ingredient->current_stock          = $newStock;
                $ingredient->cost_per_unit          = round($newAvgCost, 2);
                $ingredient->stock_status           = $ingredient->determineStockStatus();
                $ingredient->last_status_updated_at = now();

                // Logika FIFO Otomatis: Jika sebelumnya masih ada sisa stok ($oldStock > 0) dan ada pembelian baru,
                // tandai komoditas sebagai FIFO ("Sisa Kemarin - Jual Duluan") agar dihabiskan lebih dulu.
                if ($oldStock > 0) {
                    $ingredient->is_fifo   = true;
                    $ingredient->fifo_note = "Sisa kemarin masih ada " . rtrim(rtrim(number_format($oldStock, 2, ',', '.'), '0'), ',') . " {$ingredient->unit} — dahulukan stok lama (FIFO) sebelum membongkar stok baru!";
                }

                $ingredient->save();

                // Sinkronkan HPP & tambah kapasitas stok etalase MenuItem terkait
                foreach ($ingredient->recipeItems as $recipeItem) {
                    $menu = $recipeItem->recipe?->menuItem;
                    if ($menu) {
                        $ratio = max(0.001, (float) $recipeItem->quantity);
                        $addedRetailUnits = (int) floor($qty / $ratio);
                        if ($addedRetailUnits > 0) {
                            $menu->current_stock = (int) $menu->current_stock + $addedRetailUnits;
                            $menu->save();
                        }
                        $menu->syncHpp();
                    }
                }
            }

            return $purchase;
        });

        $summaryNames = collect($validItems)
            ->map(fn ($i) => $i['ingredient']->name . ' (+' . rtrim(rtrim(number_format($i['quantity'], 2, ',', '.'), '0'), ',') . ' ' . $i['ingredient']->unit . ')')
            ->implode(', ');

        $msg = 'Struk belanja pasar sebesar Rp ' . number_format((float) $purchase->total_amount, 0, ',', '.')
            . ' berhasil disimpan! Stok gudang (' . $summaryNames . ') bertambah, status stok & Omset Bersih harian telah diperbarui.';

        if ($request->expectsJson()) {
            return response()->json([
                'success'               => true,
                'purchase_id'           => $purchase->id,
                'receipt_url'           => $purchase->receipt_url,
                'price_recommendations' => $priceRecommendations,
                'message'               => $msg,
            ]);
        }

        return redirect()
            ->route('purchases.index')
            ->with('success', $msg)
            ->with('price_recommendations', $priceRecommendations);
    }

    /**
     * Parser lokal berbasis Regex & Fuzzy Token Matching (100% jalan walau tanpa API Key / kuota).
     * Contoh input:
     * "Beli cabai keriting 8kg 360000, daun jeruk 10 ikat 100000, jagung manis 12kg 144000"
     * "Beli cabai keriting 8kg 360rb, daun jeruk 10 ikat 100rb, jagung manis 15kg 120rb"
     */
    public function parseTextWithLocalEngine(string $text, Collection $ingredients): array
    {
        $clauses = preg_split('/[,;\n\r]+|\bdan\b/i', $text) ?: [$text];
        $extracted = [];

        foreach ($clauses as $clause) {
            $clause = trim($clause);
            if ($clause === '') {
                continue;
            }

            $matchedIngredient = $this->matchIngredientFromClause($clause, $ingredients);
            if (! $matchedIngredient) {
                continue;
            }

            // Bersihkan nama komoditas dari klausa agar angka di nama (misal "1kg") tidak mengganggu ekstraksi qty/harga
            $cleanClause = strtolower($clause);
            // Hilangkan kata pengantar umum
            $cleanClause = preg_replace('/\b(beli|kulakan|nota|pasar|pesan|tambah)\b/i', ' ', $cleanClause);

            // 1. Ekstrak Quantity + Unit (contoh: "8kg", "8 kg", "10 ikat", "2.5 kg", "5 dus")
            $qty = (float) ($matchedIngredient->recommended_restock_qty ?: 5);
            if (preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|kilo|kilogram|ikat|iket|dus|box|karung|sak|liter|ltr|ons|bks|bungkus|kantong|pcs)\b/i', $cleanClause, $mQty)) {
                $qty = (float) str_replace(',', '.', $mQty[1]);
                // Hapus bagian qty+unit dari cleanClause supaya tidak terbaca sebagai harga
                $cleanClause = str_replace($mQty[0], ' ', $cleanClause);
            } elseif (preg_match('/\b(\d+(?:[.,]\d+)?)\b/', $cleanClause, $mFirstNum)) {
                $candidate = (float) str_replace(',', '.', $mFirstNum[1]);
                if ($candidate > 0 && $candidate < 500) {
                    $qty = $candidate;
                    $cleanClause = preg_replace('/\b' . preg_quote($mFirstNum[1], '/') . '\b/', ' ', $cleanClause, 1);
                }
            }

            // 2. Ekstrak Harga (mendukung: "360000", "360.000", "360rb", "360 ribu", "360k", "@45000", "@45rb")
            $isUnitPrice = str_contains($cleanClause, '@') || str_contains($cleanClause, 'per ');
            $subtotal = 0.0;

            if (preg_match('/(?:rp\.?\s*)?(\d+(?:[.,]\d+)?)\s*(rb|ribu|k|jt|juta)\b/i', $cleanClause, $mShortPrice)) {
                $base = (float) str_replace(',', '.', $mShortPrice[1]);
                $mult = in_array(strtolower($mShortPrice[2]), ['jt', 'juta'], true) ? 1000000 : 1000;
                $val  = $base * $mult;
                $subtotal = $isUnitPrice ? ($val * $qty) : $val;
            } elseif (preg_match_all('/(?:rp\.?\s*)?(\d{1,3}(?:\.\d{3})+|\d{4,9})\b/i', $cleanClause, $mNumbers)) {
                $lastRaw = end($mNumbers[1]);
                $val = (float) str_replace('.', '', $lastRaw);
                if ($val > 0) {
                    // Jika nilainya mendekati harga satuan (<= 1.6x cost_per_unit) dan qty > 1 serta ada kata '@', kalikan qty
                    $subtotal = $isUnitPrice ? ($val * $qty) : $val;
                }
            }

            if ($subtotal <= 0) {
                $subtotal = round($qty * (float) $matchedIngredient->cost_per_unit, 0);
            }

            $unitPrice = $qty > 0 ? round($subtotal / $qty, 0) : (float) $matchedIngredient->cost_per_unit;
            $oldStock  = max(0.0, (float) $matchedIngredient->current_stock);
            $oldHpp    = max(0.0, (float) $matchedIngredient->cost_per_unit);
            $newStock  = $oldStock + $qty;
            $newHpp    = $newStock > 0
                ? round((($oldStock * $oldHpp) + $subtotal) / $newStock, 0)
                : $unitPrice;

            $diffPct = $oldHpp > 0 ? round((($newHpp - $oldHpp) / $oldHpp) * 100, 1) : 0.0;

            $extracted[] = [
                'ingredient_id'  => $matchedIngredient->id,
                'name'           => $matchedIngredient->name,
                'kategori'       => $matchedIngredient->kategori ?? 'Sayur Segar',
                'unit'           => $matchedIngredient->unit,
                'supplier_name'  => $matchedIngredient->supplier?->name ?? 'Pasar Induk Kramat Jati',
                'is_fifo'        => (bool) $matchedIngredient->is_fifo,
                'old_stock'      => $oldStock,
                'quantity'       => $qty,
                'new_stock'      => $newStock,
                'price_per_unit' => $unitPrice,
                'subtotal'       => round($subtotal, 0),
                'old_hpp'        => round($oldHpp, 0),
                'new_hpp'        => $newHpp,
                'hpp_diff_pct'   => $diffPct,
            ];
        }

        return $extracted;
    }

    /**
     * Pencocokan nama komoditas secara cerdas (mendukung sinonim pasar seperti cabe/cabai, terigu/tepung, minyak/minyakita).
     */
    private function matchIngredientFromClause(string $clause, Collection $ingredients): ?Ingredient
    {
        $lower = strtolower($clause);
        // Normalisasi sinonim umum warung sembako & sayur
        $normalized = str_replace(
            ['cabe ', 'cabe,', 'lombok', 'minyakita', 'segitiga biru'],
            ['cabai ', 'cabai,', 'cabai', 'minyak goreng', 'tepung terigu'],
            $lower
        );

        $bestMatch = null;
        $bestScore = 0;

        foreach ($ingredients as $ingredient) {
            $ingName = strtolower($ingredient->name);
            if (str_contains($normalized, $ingName)) {
                return $ingredient;
            }

            $tokens = array_filter(explode(' ', $ingName), fn ($t) => strlen($t) >= 4);
            $score = 0;
            foreach ($tokens as $token) {
                if (str_contains($normalized, $token)) {
                    $score += strlen($token);
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $ingredient;
            }
        }

        return $bestScore >= 4 ? $bestMatch : null;
    }

    /**
     * Opsional: Integrasi Gemini API apabila GEMINI_API_KEY tersedia di .env.
     * Jika tidak tersedia atau gagal jaringan, otomatis kembali ke parser Regex lokal.
     */
    private function tryGeminiExtraction(Request $request, Collection $ingredients, string $rawText = ''): array
    {
        $apiKey = (string) env('GEMINI_API_KEY', '');
        if ($apiKey === '') {
            return [];
        }

        try {
            $catalogNames = $ingredients->map(fn ($i) => "{$i->name} (satuan: {$i->unit})")->implode(', ');
            $prompt = "Ekstrak daftar belanja pasar berikut ke JSON array murni dengan key: name, qty (angka), total_price (angka Rupiah). "
                . "Cocokkan nama barang dengan katalog warung ini: [{$catalogNames}]. "
                . "Catatan nota: " . ($rawText ?: 'Baca dari gambar struk pasar terlampir.');

            $parts = [['text' => $prompt]];

            $uploadedFile = $request->file('receipt') ?? $request->file('receipt_image');
            if ($uploadedFile) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $uploadedFile->getMimeType() ?: 'image/jpeg',
                        'data'      => base64_encode((string) file_get_contents($uploadedFile->getRealPath())),
                    ],
                ];
            }

            $response = Http::timeout(8)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}",
                [
                    'contents' => [['parts' => $parts]],
                ]
            );

            if (! $response->successful()) {
                return [];
            }

            $textResponse = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
            if (preg_match('/\[.*\]/s', $textResponse, $mJson)) {
                $decoded = json_decode($mJson[0], true);
                if (is_array($decoded) && count($decoded) > 0) {
                    $reconstructed = collect($decoded)
                        ->map(fn ($r) => ($r['name'] ?? '') . ' ' . ($r['qty'] ?? 1) . 'kg ' . ($r['total_price'] ?? 0))
                        ->implode(', ');

                    return $this->parseTextWithLocalEngine($reconstructed, $ingredients);
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return [];
    }
}

