<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Keuangan & Laba Rugi</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.5; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #10B981; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #111827; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #6B7280; font-size: 14px; }
        
        .summary-box { background-color: #ECFDF5; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #D1FAE5; }
        .summary-box h3 { margin: 0 0 10px 0; font-size: 16px; color: #065F46; text-align: center; }
        .stats-grid { width: 100%; border-collapse: collapse; }
        .stats-grid td { width: 33.33%; text-align: center; padding: 10px; }
        .stats-label { font-size: 11px; color: #6B7280; text-transform: uppercase; font-weight: bold; }
        .stats-value { font-size: 18px; font-weight: bold; color: #111827; margin-top: 5px; }
        
        .section-title { font-size: 16px; color: #111827; margin-bottom: 10px; border-bottom: 1px solid #E5E7EB; padding-bottom: 5px; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data-table th, table.data-table td { border: 1px solid #E5E7EB; padding: 8px; text-align: left; font-size: 12px; }
        table.data-table th { background-color: #F9FAFB; font-weight: bold; color: #374151; }
        table.data-table td.amount { text-align: right; font-weight: bold; }
        
        .profit-final { background-color: #10B981; color: white; padding: 15px; text-align: center; border-radius: 8px; margin-top: 20px; }
        .profit-final h2 { margin: 0; font-size: 24px; }
        .profit-final p { margin: 5px 0 0; font-size: 12px; opacity: 0.9; }

        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 10px; }
        .text-red { color: #DC2626; }
        .text-green { color: #059669; }
    </style>
</head>
<body>

    <div class="header">
        <h1>LAPORAN KEUANGAN &amp; LABA RUGI BERSIH WARUNG</h1>
        <p>STOKKU ERP (Sistem Manajemen Warung Sembako &amp; Sayur) - Periode: {{ $periodLabel }}</p>
        <p style="font-size: 11px;">Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }} WIB</p>
    </div>

    <div class="summary-box">
        <h3>Ringkasan Arus Kas Utama</h3>
        <table class="stats-grid">
            <tr>
                <td>
                    <div class="stats-label">Total Omset Masuk</div>
                    <div class="stats-value text-green">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
                </td>
                <td style="border-left: 1px solid #D1FAE5; border-right: 1px solid #D1FAE5;">
                    <div class="stats-label">Total Beli Bahan</div>
                    <div class="stats-value text-red">- Rp {{ number_format($totalPurchases, 0, ',', '.') }}</div>
                </td>
                <td>
                    <div class="stats-label">Total Operasional</div>
                    <div class="stats-value text-red">- Rp {{ number_format($totalExpenses, 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="profit-final">
        <p>LABA BERSIH RIIL (KAS DITANGAN)</p>
        <h2>Rp {{ number_format($totalRevenue - ($totalPurchases + $totalExpenses), 0, ',', '.') }}</h2>
    </div>

    <div style="margin-top: 30px;">
        <h3 class="section-title">Rincian Pengeluaran Operasional (Cacat / Overhead / Dll)</h3>
        @if($expenses->isEmpty())
            <p style="font-size: 12px; color: #6B7280; font-style: italic;">Tidak ada catatan pengeluaran operasional pada periode ini.</p>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="15%">Tanggal</th>
                        <th width="25%">Kategori</th>
                        <th width="40%">Keterangan</th>
                        <th width="20%" style="text-align: right;">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $ex)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($ex->expense_date)->format('d/m/Y') }}</td>
                            <td>{{ $ex->category }}</td>
                            <td>{{ $ex->description }}</td>
                            <td class="amount text-red">Rp {{ number_format($ex->amount, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh STOKKU ERP (Warung Sembako &amp; Sayur). Data yang ditampilkan adalah rekapitulasi riil dari transaksi Kasir POS, Belanja Struk Pasar, dan Beban Operasional.
    </div>

</body>
</html>
