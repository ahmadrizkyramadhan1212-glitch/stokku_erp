<?php

namespace App\Services;

use App\Http\Controllers\SpkController;
use App\Models\Ingredient;
use App\Models\User;
use App\Models\WaReportLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Generator Laporan Tutup Warung (23:00 WIB) untuk WhatsApp Owner via direct link wa.me (gratis, tanpa gateway).
 *
 * Isi laporan:
 *  - Barang berstatus HABIS & MENIPIS malam ini
 *  - Pengingat barang FIFO (is_fifo = true)
 *  - Top 5 rekomendasi belanja pasar besok pagi (hasil SAW) + total estimasi modal
 */
class WaReportService
{
    public const DEFAULT_OWNER_PHONE = '6281234567890';
    public const SAW_DAYS = 30;
    public const TOP_LIMIT = 5;

    /**
     * Nomor WA Owner default: nomor terakhir yang dipakai -> config/.env -> 6281234567890.
     */
    public function defaultOwnerPhone(): string
    {
        $last = WaReportLog::latest('id')->value('target_phone');

        return $this->normalizePhone(
            $last ?: (string) config('services.stokku.owner_wa', self::DEFAULT_OWNER_PHONE)
        ) ?: self::DEFAULT_OWNER_PHONE;
    }

    /**
     * Normalisasi nomor ke format internasional tanpa "+" (0812... / +62812... / 812... -> 62812...).
     */
    public function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return $digits;
    }

    public function isValidPhone(string $normalized): bool
    {
        return (bool) preg_match('/^62\d{8,13}$/', $normalized);
    }

    /**
     * Susun data + teks laporan tutup warung.
     *
     * @return array{message: string, report_date: Carbon, habis: Collection, menipis: Collection, fifo: Collection, top: Collection, total_cost: float}
     */
    public function buildReport(?User $sender = null): array
    {
        $now = Carbon::now();

        $ingredients = Ingredient::where('is_active', true)->orderBy('name')->get();
        $habis   = $ingredients->where('stock_status', Ingredient::STATUS_HABIS)->values();
        $menipis = $ingredients->where('stock_status', Ingredient::STATUS_MENIPIS)->values();
        $fifo    = $ingredients->where('is_fifo', true)->values();

        // Top 5 hasil SAW (memakai logika SpkController apa adanya, bobot default 35/30/20/15)
        $spk     = app(SpkController::class);
        $weights = $spk->resolveWeights(new Request());
        $sawData = $spk->buildSpkData(self::SAW_DAYS, $weights);
        $top       = $sawData['sawItems']->take(self::TOP_LIMIT)->values();
        $totalCost = (float) $top->sum('estimated_cost');

        $lines   = [];
        $lines[] = '*LAPORAN TUTUP WARUNG - STOKKU ERP*';
        $lines[] = '📅 ' . $now->translatedFormat('l, d F Y') . ' • Tutup 23:00 WIB';
        $lines[] = '🕒 Dibuat: ' . $now->format('H:i') . ' WIB' . ($sender ? ' oleh ' . $sender->name : '');
        $lines[] = '';

        // 1. Stok HABIS & MENIPIS
        $lines[] = '🔴 *STOK HABIS (' . $habis->count() . '):*';
        if ($habis->isEmpty()) {
            $lines[] = '- Tidak ada';
        }
        foreach ($habis as $item) {
            $lines[] = '- ' . $item->name . ' (0 ' . $item->unit . ')';
        }
        $lines[] = '';

        $lines[] = '🟡 *STOK MENIPIS (' . $menipis->count() . '):*';
        if ($menipis->isEmpty()) {
            $lines[] = '- Tidak ada';
        }
        foreach ($menipis as $item) {
            $lines[] = '- ' . $item->name . ' (sisa ' . $this->qty($item->current_stock) . ' ' . $item->unit
                . ', min ' . $this->qty($item->min_stock) . ' ' . $item->unit . ')';
        }
        $lines[] = '';

        // 2. Pengingat FIFO
        $lines[] = '⚠️ *PENGINGAT FIFO (Jual/Kemas Duluan Besok):*';
        if ($fifo->isEmpty()) {
            $lines[] = '- Tidak ada barang sisa kemarin';
        }
        foreach ($fifo as $item) {
            $lines[] = '- ' . $item->name . ' (sisa ' . $this->qty($item->current_stock) . ' ' . $item->unit . ')'
                . ($item->fifo_note ? ': ' . $item->fifo_note : '');
        }
        $lines[] = '';

        // 3. Top 5 Belanja Pasar Besok Pagi (SAW)
        $isOwner = $sender ? $sender->isOwner() : true;
        $lines[] = '🛒 *TOP ' . self::TOP_LIMIT . ' BELANJA PASAR BESOK PAGI (SAW):*';
        foreach ($top as $item) {
            $costText = $isOwner ? (' (±' . $this->rupiah($item->estimated_cost) . ')') : '';
            $lines[] = $item->rank . '. ' . $item->name
                . ' - beli ' . $this->qty($item->recommended_restock_qty) . ' ' . $item->unit
                . $costText
                . ' • Skor ' . number_format($item->saw_score, 2, ',', '.');
        }
        $lines[] = '';
        if ($isOwner) {
            $lines[] = '💰 *Total Estimasi Modal Pasar:* ' . $this->rupiah($totalCost);
            $lines[] = '';
        }
        $lines[] = '_Pesan otomatis dari STOKKU ERP_';

        return [
            'message'     => implode("\n", $lines),
            'report_date' => $now,
            'habis'       => $habis,
            'menipis'     => $menipis,
            'fifo'        => $fifo,
            'top'         => $top,
            'total_cost'  => $totalCost,
        ];
    }

    /**
     * Direct link WhatsApp gratis (rawurlencode agar spasi menjadi %20, aman untuk emoji & baris baru).
     */
    public function buildWaUrl(string $phone, string $message): string
    {
        return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
    }

    /**
     * Simpan riwayat pengiriman ke wa_report_logs.
     */
    public function log(string $phone, string $message, ?Carbon $reportDate = null): WaReportLog
    {
        return WaReportLog::create([
            'report_date'     => ($reportDate ?? Carbon::now())->toDateString(),
            'target_phone'    => $phone,
            'message_content' => $message,
            'status'          => WaReportLog::STATUS_MANUAL_LINK,
            'sent_at'         => Carbon::now(),
        ]);
    }

    /**
     * Data siap pakai untuk kartu WA di Blade (/spk & /inventory/materials).
     */
    public function cardData(?User $sender = null): array
    {
        $report = $this->buildReport($sender);
        $latest = WaReportLog::latest('id')->first();

        return [
            'message'      => $report['message'],
            'phone'        => $this->defaultOwnerPhone(),
            'habis_count'  => $report['habis']->count(),
            'menipis_count'=> $report['menipis']->count(),
            'fifo_count'   => $report['fifo']->count(),
            'total_cost'   => $report['total_cost'],
            'last_sent'    => $latest?->sent_at
                ? $latest->sent_at->translatedFormat('d M Y, H:i') . ' WIB ke ' . $latest->target_phone
                : null,
        ];
    }

    private function qty($value): string
    {
        $num = (float) $value;

        return rtrim(rtrim(number_format($num, 2, ',', '.'), '0'), ',');
    }

    private function rupiah($value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}

