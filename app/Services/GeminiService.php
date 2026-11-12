<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected int $timeout;
    protected ?string $lastError = null;

    /**
     * Daftar kandidat model Gemini yang aktif dan mendukung generateContent di tahun 2026.
     * Model utama: gemini-3.5-flash-lite (sangat cepat ~2 detik, hemat kuota, cerdas).
     */
    protected array $candidateModels = [
        'gemini-3.5-flash-lite',
        'gemini-3-flash-preview',
        'gemini-3.5-flash',
    ];

    public function __construct()
    {
        $this->apiKey = (string) (config('services.gemini.key') ?: env('GEMINI_API_KEY', ''));
        $this->timeout = 30;
    }

    /**
     * Cek apakah API Key Gemini telah terkonfigurasi.
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Dapatkan detail pesan error teknis terakhir dari server Google Gemini atau cURL.
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Kirim permintaan ke Gemini API secara terpusat, aman, tanpa SSL verification issue di Laragon.
     */
    public function callApi(
        array $contents,
        ?string $systemInstruction = null,
        float $temperature = 0.4,
        int $maxTokens = 1500
    ): ?string {
        if (! $this->isAvailable()) {
            $this->lastError = 'GEMINI_API_KEY belum dikonfigurasi di file .env';
            Log::warning('GeminiService: ' . $this->lastError);
            return null;
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
            ],
        ];

        if ($systemInstruction !== null && trim($systemInstruction) !== '') {
            $payload['system_instruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        $lastErrorMsg = null;

        foreach ($this->candidateModels as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";
            try {
                $response = Http::withoutVerifying()
                    ->timeout($this->timeout)
                    ->withHeaders([
                        'x-goog-api-key' => $this->apiKey,
                        'Content-Type'   => 'application/json',
                    ])
                    ->post($url, $payload);

                if ($response->successful()) {
                    $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
                    if (trim($text) !== '') {
                        $this->lastError = null;
                        return trim($text);
                    }
                }

                $errBody = substr($response->body(), 0, 400);
                $lastErrorMsg = "Google API Error [Model: {$model}, Status: {$response->status()}]: {$errBody}";
                Log::warning("GeminiService [{$model}] failed: {$lastErrorMsg}");

                // Jika model 404 (misal deprecated), lanjutkan ke kandidat model berikutnya
                if ($response->status() === 404) {
                    continue;
                }

                // Jika ada error otentikasi (400 / 401 / 403), simpan error dan hentikan perulangan
                $this->lastError = $lastErrorMsg;
                return null;
            } catch (\Throwable $e) {
                $lastErrorMsg = "cURL / Connection Error [Model: {$model}]: " . $e->getMessage();
                Log::warning("GeminiService [{$model}] exception: {$lastErrorMsg}");
                continue;
            }
        }

        $this->lastError = $lastErrorMsg ?: 'Koneksi ke semua endpoint model Gemini tidak menghasilkan teks jawaban.';
        return null;
    }

    /**
     * Kirim prompt teks biasa dengan instruksi sistem opsional.
     */
    public function generateText(
        string $prompt,
        ?string $systemInstruction = null,
        float $temperature = 0.4,
        int $maxTokens = 1500
    ): ?string {
        $contents = [
            [
                'role'  => 'user',
                'parts' => [
                    ['text' => $prompt],
                ],
            ],
        ];

        return $this->callApi($contents, $systemInstruction, $temperature, $maxTokens);
    }

    /**
     * Kirim prompt teks beserta gambar (Base64) untuk AI Vision OCR.
     */
    public function generateWithImage(
        string $prompt,
        string $imageBase64,
        string $mimeType = 'image/jpeg',
        ?string $systemInstruction = null,
        float $temperature = 0.2,
        int $maxTokens = 1500
    ): ?string {
        $parts = [
            [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data'      => $imageBase64,
                ],
            ],
            [
                'text' => $prompt,
            ],
        ];

        $contents = [
            [
                'role'  => 'user',
                'parts' => $parts,
            ],
        ];

        return $this->callApi($contents, $systemInstruction, $temperature, $maxTokens);
    }

    /**
     * Fitur 1: AI Vision & NLP Scanner Nota Kulakan Pasar Induk.
     */
    public function parseReceiptImageAndText(
        ?string $imageBase64 = null,
        ?string $mimeType = 'image/jpeg',
        ?string $noteText = null,
        array $catalogNames = []
    ): ?array {
        $catalogContext = !empty($catalogNames)
            ? "DAFTAR REFERENSI NAMA KOMODITAS WARUNG ADIJAYA (Gunakan nama yang paling mendekati):\n" . implode(', ', array_slice($catalogNames, 0, 96)) . "\n\n"
            : "";

        $systemInstruction = "Anda adalah asisten AI OCR & NLP Logistik cerdas untuk Warung Sembako & Sayur Adijaya (STOKKU ERP).\n"
            . "Tugas Anda: Ekstrak daftar barang belanja/kulakan pasar induk dari gambar foto nota/struk dan/atau teks catatan tengkulak.\n"
            . $catalogContext
            . "ATURAN OUTPUT MUTLAK:\n"
            . "1. Output WAJIB berupa JSON array murni tanpa pembuka/penutup markdown ```json atau penjelasan teks apapun.\n"
            . "2. Skema setiap objek item:\n"
            . "   {\"nama\": \"Nama Barang\", \"qty\": 0.0, \"satuan\": \"kg/ikat/bks/dus/karung/unit\", \"harga_satuan\": 0, \"total_harga\": 0}\n"
            . "3. 'qty' adalah kuantitas numerik (contoh: 5 atau 2.5).\n"
            . "4. 'harga_satuan' adalah harga per satu unit (misal Rp/kg), dan 'total_harga' adalah harga_satuan * qty.\n"
            . "Contoh JSON:\n"
            . "[{\"nama\": \"Cabai Kecil / Rawit\", \"qty\": 10, \"satuan\": \"kg\", \"harga_satuan\": 42000, \"total_harga\": 420000}]";

        $promptText = "Silakan ekstrak semua item barang belanja pasar berikut:\n";
        if (!empty($noteText)) {
            $promptText .= "Catatan teks nota: \"{$noteText}\"\n";
        }
        if ($imageBase64 !== null) {
            $promptText .= "Juga baca dan ekstrak tulisan tangan / cetak pada gambar nota struk terlampir.\n";
        }

        $rawResponse = null;
        if ($imageBase64 !== null) {
            $rawResponse = $this->generateWithImage($promptText, $imageBase64, $mimeType ?: 'image/jpeg', $systemInstruction, 0.1);
        } else {
            $rawResponse = $this->generateText($promptText, $systemInstruction, 0.1);
        }

        if ($rawResponse !== null) {
            $cleanJson = $rawResponse;
            if (preg_match('/\[\s*\{.*\}\s*\]/s', $rawResponse, $match)) {
                $cleanJson = $match[0];
            }
            $decoded = json_decode($cleanJson, true);
            if (is_array($decoded) && count($decoded) > 0) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Fitur 2: AI Smart Restock & Asisten Alokasi Modal Belanja (Integrasi SPK SAW).
     */
    public function recommendBudgetRestock(float $budget, array $sawTopItems): string
    {
        $systemInstruction = "Anda adalah Konsultan Kulakan Pasar Ahli untuk Warung Sembako & Sayur Adijaya.\n"
            . "Tugas Anda: Berdasarkan peringkat Prioritas Belanja SPK SAW dan total modal yang disiapkan Owner (Rp " . number_format($budget, 0, ',', '.') . "), rancang alokasi belanja pasar besok subuh yang paling optimal.\n"
            . "PRINSIP ALOKASI:\n"
            . "1. Prioritaskan komoditas dengan peringkat SAW tertinggi dan berstatus HABIS atau MENIPIS.\n"
            . "2. BATASI kuota belanja untuk komoditas sayur segar yang cepat layu/busuk (perishability tinggi) secukupnya (maks 1-2 hari jual) agar tidak terjadi kerugian busuk (waste).\n"
            . "3. Alokasikan sisa modal pada komoditas tahan lama (sembako, bumbu kering) yang perputarannya stabil.\n"
            . "4. Total belanja TIDAK BOLEH melebihi modal yang disiapkan (Rp " . number_format($budget, 0, ',', '.') . ").\n"
            . "5. Format jawaban terstruktur, ramah, gunakan markdown bullet points, cantumkan perkiraan rupiah per barang dan total estimasi pengeluaran.";

        $itemsSummary = "DATA PRIORITAS BELANJA SPK SAW SAAT INI:\n";
        foreach (array_slice($sawTopItems, 0, 15) as $idx => $it) {
            $rank = $idx + 1;
            $nama = $it['name'] ?? $it['nama'] ?? 'Item';
            $status = strtoupper($it['stock_status'] ?? 'AMAN');
            $stok = $it['current_stock'] ?? 0;
            $unit = $it['unit'] ?? 'unit';
            $saranQty = $it['recommended_restock_qty'] ?? 5;
            $hpp = number_format((float) ($it['cost_per_unit'] ?? 0), 0, ',', '.');
            $skorSaw = $it['saw_score'] ?? 0;
            $perish = $it['perishability_score'] ?? 3;
            $itemsSummary .= "{$rank}. {$nama} [{$status}] - Skor SAW: {$skorSaw} | Sisa: {$stok} {$unit} | Rekomendasi: {$saranQty} {$unit} @ Rp {$hpp} | Tingkat Cepat Busuk (1-5): {$perish}\n";
        }

        $prompt = "Modal belanja yang disiapkan Owner: Rp " . number_format($budget, 0, ',', '.') . "\n\n"
            . $itemsSummary . "\n\n"
            . "Mohon berikan racikan daftar belanja pasar konkret lengkap dengan kuantitas belanja (kg/ikat/unit), estimasi subtotal rupiah, dan tips belanja subuh di pasar induk.";

        $aiResult = $this->generateText($prompt, $systemInstruction, 0.3, 1400);
        if ($aiResult !== null && trim($aiResult) !== '') {
            return $aiResult;
        }

        // Tampilkan error teknis jujur dari Gemini API
        $err = $this->getLastError() ?: 'Gagal menghubungi server Gemini API.';
        return "⚠️ **Gagal Meracik Rekomendasi Belanja (Gemini AI Offline)**\n\n"
            . "Detail Kendala Teknis: `{$err}`\n\n"
            . "Silakan pastikan internet aktif dan GEMINI_API_KEY di .env valid.";
    }

    /**
     * Fitur 3: AI Business Insight pada Matriks BCG.
     */
    public function analyzeBcgPortfolio(array $bcgSummary): string
    {
        $systemInstruction = "Anda adalah Konsultan Manajemen Retail & Strategi Bisnis untuk Warung Sembako & Sayur Adijaya.\n"
            . "Tugas Anda: Berikan 'AI Strategic Insight' berdasarkan pengelompokan Matriks BCG (Star, Cash Cow/Plowhorse, Question Mark/Puzzle, Dog) dan risiko kadaluwarsa/busuk produk etalase warung.\n"
            . "STRUKTUR ANALISIS WAJIB:\n"
            . "1. **Strategi Jam Sibuk Pagi (Star & Plowhorse/Cash Cow)**: Sebutkan produk unggulan yang wajib siap di rak/etalase depan sebelum jam sibuk ibu-ibu belanja pagi.\n"
            . "2. **Peringatan Dini & Anti-Waste (Dog & Puzzle/Question Mark)**: Sebutkan produk yang stoknya lambat dan rawan susut/layu, lalu berikan 2-3 ide paket bundling konkret (contoh: Paket Sayur Sop Komplit, Paket Sayur Asem Segar, atau Flash Sale Diskon Sore Hari) untuk mengamankan modal.\n"
            . "Gunakan bahasa Indonesia yang lugas, taktis, praktis bagi pedagang warung, dan rapi dengan format markdown.";

        $dataText = "RINGKASAN DATA PORTOFOLIO MATRIKS BCG WARUNG:\n";
        $dataText .= "- Standar Penjualan Laris (Threshold Qty): " . ($bcgSummary['threshold_qty'] ?? 0) . " unit\n";
        $dataText .= "- Standar Margin Untung (Threshold Margin): Rp " . number_format($bcgSummary['threshold_margin'] ?? 0, 0, ',', '.') . "\n";
        $dataText .= "- Jumlah Komoditas: Star (" . ($bcgSummary['quadrant_counts']['Star'] ?? 0) . "), "
            . "Plowhorse (" . ($bcgSummary['quadrant_counts']['Plowhorse'] ?? 0) . "), "
            . "Puzzle (" . ($bcgSummary['quadrant_counts']['Puzzle'] ?? 0) . "), "
            . "Dog (" . ($bcgSummary['quadrant_counts']['Dog'] ?? 0) . ")\n\n";

        if (!empty($bcgSummary['items_sample'])) {
            $dataText .= "SAMPEL KOMODITAS PER KUADRAN:\n";
            foreach ($bcgSummary['items_sample'] as $quadrant => $itemList) {
                $dataText .= "Kuadran {$quadrant}: " . implode(', ', $itemList) . "\n";
            }
        }

        $prompt = $dataText . "\n\nBerikan analisis strategi bisnis praktis untuk Owner Warung Adijaya.";

        $aiResult = $this->generateText($prompt, $systemInstruction, 0.4, 1400);
        if ($aiResult !== null && trim($aiResult) !== '') {
            return $aiResult;
        }

        // Tampilkan error teknis jujur dari Gemini API
        $err = $this->getLastError() ?: 'Gagal menghubungi server Gemini API.';
        return "⚠️ **Gagal Menghasilkan Analisis BCG (Gemini AI Offline)**\n\n"
            . "Detail Kendala Teknis: `{$err}`\n\n"
            . "Silakan pastikan internet aktif dan GEMINI_API_KEY di .env valid.";
    }

    /**
     * Fitur 4: AI Predictive Analytics & Interpretasi Regresi Linier (Y = a + bX).
     */
    public function interpretRegressionTrend(array $regressionData): string
    {
        $systemInstruction = "Anda adalah Data Analyst & Perencana Stok Retail Sayur & Sembako Warung Adijaya.\n"
            . "Tugas Anda: Terjemahkan angka matematis peramalan Regresi Linier (Y = a + bX, nilai kemiringan slope b, rata-rata harian, tren naik/turun) ke dalam bahasa pedagang warung yang lugas dan berikan saran aksi kulakan harian & persiapan akhir pekan.\n"
            . "Format dengan markdown yang rapi, berikan kesimpulan tren keseluruhan, komoditas yang permintaannya sedang melonjak, dan komoditas yang sedang lesu.";

        $dataText = "DATA HASIL ANALISIS REGRESI LINIER PERMINTAAN KOMODITAS:\n";
        foreach (array_slice($regressionData, 0, 12) as $row) {
            $name = $row['name'] ?? 'Item';
            $unit = $row['unit'] ?? 'unit';
            $avg = $row['avg_daily'] ?? 0;
            $slope = $row['slope'] ?? 0;
            $trend = strtoupper($row['trend'] ?? 'STABIL');
            $prediksi = isset($row['predictions']) ? collect($row['predictions'])->pluck('predicted_qty')->implode(', ') : '-';
            $conf = $row['confidence'] ?? '80%';
            $dataText .= "- {$name}: Rata-rata {$avg} {$unit}/hari | Kemiringan Slope (b): {$slope} | Status: {$trend} | Prediksi 3 hari ke depan: [{$prediksi}] {$unit} (Tingkat Keyakinan: {$conf})\n";
        }

        $prompt = $dataText . "\n\nTerjemahkan hasil regresi di atas menjadi panduan belanja dan manajemen stok praktis untuk Owner.";

        $aiResult = $this->generateText($prompt, $systemInstruction, 0.3, 1400);
        if ($aiResult !== null && trim($aiResult) !== '') {
            return $aiResult;
        }

        // Tampilkan error teknis jujur dari Gemini API
        $err = $this->getLastError() ?: 'Gagal menghubungi server Gemini API.';
        return "⚠️ **Gagal Menginterpretasikan Tren Permintaan (Gemini AI Offline)**\n\n"
            . "Detail Kendala Teknis: `{$err}`\n\n"
            . "Silakan pastikan internet aktif dan GEMINI_API_KEY di .env valid.";
    }

    /**
     * Fitur 5: Asisten Virtual Interaktif "Tanya Stokku AI" (Contextual AI Chatbot - Falah).
     * 100% menggunakan Google Gemini AI asli dengan injeksi data real-time database MySQL.
     * Tidak ada fallback template statis!
     */
    public function chatAssistant(string $userMessage, array $context): string
    {
        $omzetFormatted = number_format((float) ($context['today_revenue'] ?? 0), 0, ',', '.');
        $transaksiCount = (int) ($context['today_tx_count'] ?? 0);
        $monthOmzetFormatted = number_format((float) ($context['month_revenue'] ?? 0), 0, ',', '.');
        $monthTxCount = (int) ($context['month_tx_count'] ?? 0);
        $untungFormatted = number_format((float) ($context['today_net_profit'] ?? 0), 0, ',', '.');
        $monthLabel = (string) ($context['month_label'] ?? date('F Y'));

        $habisList = !empty($context['habis_items'])
            ? implode(', ', $context['habis_items'])
            : 'Tidak ada (semua stok tersedia)';

        $menipisList = !empty($context['menipis_items'])
            ? implode(', ', $context['menipis_items'])
            : 'Tidak ada (semua stok di atas batas aman)';

        $fifoList = !empty($context['fifo_batches'])
            ? implode(', ', $context['fifo_batches'])
            : 'Tidak ada stok sisa kemarin yang mendesak';

        $sawList = !empty($context['saw_top_priorities'])
            ? implode(', ', $context['saw_top_priorities'])
            : 'Belum ada data';

        $systemActionNote = !empty($context['system_action_note'])
            ? "\n[CATATAN AKSI SISTEM TERBARU]:\n" . $context['system_action_note'] . "\n"
            : "";

        $systemInstruction = "Kamu adalah Falah (Tanya Stokku AI), asisten AI pintar berbasis Google Gemini untuk Warung Sembako & Sayur Adijaya (STOKKU ERP).\n"
            . "Kamu bebas menjawab pertanyaan apa saja dari user secara luwes, cerdas, ramah, dan natural layaknya asisten Google Gemini pada umumnya (kamu bisa berpantun, bercanda, mengobrol santai, menjelaskan topik umum, maupun menganalisis bisnis warung).\n\n"
            . "Jika user bertanya mengenai warung, stok barang, omzet, keuangan, atau strategi dagang, gunakan DATA REAL-TIME DATABASE WARUNG ADIJAYA berikut:\n\n"
            . "1. PERFORMA KEUANGAN & OMZET:\n"
            . "- Omzet Penjualan Hari Ini: Rp {$omzetFormatted} ({$transaksiCount} transaksi)\n"
            . "- Total Omzet Bulan Ini ({$monthLabel}): Rp {$monthOmzetFormatted} ({$monthTxCount} transaksi)\n"
            . "- Estimasi Laba Bersih Hari Ini: Rp {$untungFormatted}\n\n"
            . "2. STATUS STOK GUDANG & ETALASE:\n"
            . "- Komoditas HABIS (Stok 0): {$habisList}\n"
            . "- Komoditas MENIPIS (Mendekati Batas Aman): {$menipisList}\n\n"
            . "3. BARANG FIFO & SISA KEMARIN (WAJIB DIJUAL DULUAN / RAWAN BUSUK):\n"
            . "- {$fifoList}\n\n"
            . "4. TOP PRIORITAS KULAKAN PASAR INDUK (SPK SAW):\n"
            . "- {$sawList}\n"
            . $systemActionNote . "\n"
            . "PANDUAN MENJAWAB:\n"
            . "- Jawablah secara natural, cerdas, informatif, dan membantu dengan gaya khas Falah.\n"
            . "- Gunakan format markdown yang rapi (gunakan bold untuk angka/nama barang, serta bullet points bila menampilkan daftar).\n"
            . "- Jangan mengarang angka omzet atau stok jika sudah tersedia di data di atas. Jika data kosong (misal Rp 0 / belum ada transaksi), jelaskan secara jujur dan berikan semangat dagang.\n"
            . "- Jika terdapat CATATAN AKSI SISTEM di atas (misal pembaruan status stok baru saja dieksekusi), sampaikan konfirmasinya kepada user dengan nada ceria dan ramah.";

        $aiResult = $this->generateText($userMessage, $systemInstruction, 0.4, 1500);
        if ($aiResult !== null && trim($aiResult) !== '') {
            return $aiResult;
        }

        // Tampilkan error teknis jujur dari Google Gemini / cURL
        $err = $this->getLastError() ?: 'Koneksi ke endpoint Google Gemini tidak mengembalikan teks jawaban.';
        return "⚠️ **Error Koneksi ke Gemini API**\n\n"
            . "Pesan Error Asli: `{$err}`\n\n"
            . "Mohon periksa sambungan internet atau periksa kembali konfigurasi GEMINI_API_KEY di file .env.";
    }
}
