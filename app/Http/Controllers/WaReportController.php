<?php

namespace App\Http\Controllers;

use App\Services\WaReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Laporan Tutup Warung ke WhatsApp Owner via direct link wa.me (gratis, tanpa gateway berbayar).
 */
class WaReportController extends Controller
{
    public function __construct(private WaReportService $service)
    {
    }

    /**
     * Pratinjau teks laporan terbaru (dipakai untuk refresh setelah status 1-Tap berubah).
     */
    public function preview()
    {
        $card = $this->service->cardData(Auth::user());

        return response()->json([
            'message'   => $card['message'],
            'last_sent' => $card['last_sent'],
        ]);
    }

    /**
     * Simpan log ke wa_report_logs lalu arahkan ke https://wa.me/{nomor}?text={pesan}.
     * Form dikirim dengan target="_blank" sehingga WhatsApp terbuka di tab baru.
     */
    public function send(Request $request)
    {
        $request->validate([
            'target_phone' => 'required|string|max:25',
        ]);

        $phone = $this->service->normalizePhone($request->input('target_phone'));

        if (! $this->service->isValidPhone($phone)) {
            $error = 'Nomor WA Owner tidak valid. Gunakan format 62xxxxxxxxxx (contoh: 6281234567890).';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $error], 422)
                : redirect()->back()->with('error', $error);
        }

        // Pesan selalu dibuat ulang saat dikirim agar status stok paling baru yang terkirim
        $report = $this->service->buildReport(Auth::user());
        $log    = $this->service->log($phone, $report['message'], $report['report_date']);
        $waUrl  = $this->service->buildWaUrl($phone, $report['message']);

        if ($request->expectsJson()) {
            return response()->json([
                'success'   => true,
                'log_id'    => $log->id,
                'wa_url'    => $waUrl,
                'message'   => $report['message'],
                'last_sent' => $log->sent_at->translatedFormat('d M Y, H:i') . ' WIB ke ' . $phone,
            ]);
        }

        return redirect()->away($waUrl);
    }
}

