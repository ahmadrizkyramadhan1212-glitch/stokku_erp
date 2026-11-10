<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Aset Inventori Dapur</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.5; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #F59E0B; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #111827; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #6B7280; font-size: 14px; }
        
        .summary-box { background-color: #FEF3C7; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #FDE68A; text-align: center; }
        .summary-box h3 { margin: 0 0 5px 0; font-size: 14px; color: #92400E; text-transform: uppercase; }
        .summary-box .value { font-size: 24px; font-weight: bold; color: #B45309; }
        
        .section-title { font-size: 16px; color: #111827; margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #E5E7EB; padding-bottom: 5px; }
        
        table.data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data-table th, table.data-table td { border: 1px solid #E5E7EB; padding: 8px; text-align: left; font-size: 12px; }
        table.data-table th { background-color: #F9FAFB; font-weight: bold; color: #374151; }
        table.data-table td.amount { text-align: right; font-weight: bold; }
        table.data-table td.center { text-align: center; }
        
        .text-red { color: #DC2626; font-weight: bold; }
        .text-green { color: #059669; font-weight: bold; }
        
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>LAPORAN ASET INVENTORI GUDANG &amp; ETALASE WARUNG</h1>
        <p>STOKKU ERP (Sistem Manajemen Warung Sembako &amp; Sayur) - Kondisi Riil Saat Ini</p>
        <p style="font-size: 11px;">Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }} WIB</p>
    </div>

    <div class="summary-box">
        <h3>Total Valuasi Uang Mengendap di Gudang Bahan Mentah</h3>
        <div class="value">Rp {{ number_format($totalAssetValue, 0, ',', '.') }}</div>
    </div>

    <h3 class="section-title">1. Stok Fisik Bahan Baku Mentah</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th width="35%">Nama Bahan</th>
                <th width="15%" class="center">Sisa Stok</th>
                <th width="15%" class="center">Batas Aman</th>
                <th width="15%" style="text-align: right;">HPP/Satuan</th>
                <th width="20%" style="text-align: right;">Nilai Aset</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ingredients as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td class="center {{ $item->current_stock <= $item->min_stock ? 'text-red' : 'text-green' }}">
                        {{ $item->current_stock }} {{ $item->unit }}
                        @if($item->current_stock <= $item->min_stock)
                            <br><span style="font-size: 9px;">(Kritis)</span>
                        @endif
                    </td>
                    <td class="center" style="color: #6B7280;">{{ $item->min_stock }} {{ $item->unit }}</td>
                    <td class="amount">Rp{{ number_format($item->cost_per_unit, 0, ',', '.') }}</td>
                    <td class="amount">Rp{{ number_format($item->current_stock * $item->cost_per_unit, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 class="section-title">2. Stok Etalase Siap Jual</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th width="50%">Nama Komoditas</th>
                <th width="25%" class="center">Stok Etalase Siap Jual</th>
                <th width="25%" class="center">Stok Gudang Tersedia</th>
            </tr>
        </thead>
        <tbody>
            @foreach($menus as $menu)
                @php
                    $saleUnit = $menu->sale_unit ?: ($menu->recipe?->items?->first()?->ingredient?->unit ?? 'satuan');
                @endphp
                <tr>
                    <td>{{ $menu->name }}</td>
                    <td class="center" style="font-weight: bold; color: {{ $menu->current_stock > 0 ? '#0D9488' : '#6B7280' }};">
                        {{ $menu->current_stock }} {{ $saleUnit }}
                    </td>
                    <td class="center" style="color: #6B7280;">
                        {{ $menu->production_capacity }} {{ $saleUnit }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh STOKKU ERP (Warung Sembako &amp; Sayur). Data yang ditampilkan adalah rekapitulasi riil dari stok gudang dan etalase kasir.
    </div>

</body>
</html>
