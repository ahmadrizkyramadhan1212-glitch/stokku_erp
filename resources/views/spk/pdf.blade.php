<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan SPK SAW & Matriks BCG - STOKKU ERP</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #0F172A;
            line-height: 1.45;
            margin: 0;
            padding: 18px;
            font-size: 11px;
        }
        .header {
            border-bottom: 3px solid #0D9488;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .header h1 {
            margin: 0;
            color: #0D9488;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 4px 0 0;
            color: #475569;
            font-size: 11px;
        }
        .summary-box {
            background-color: #F0FDFA;
            border: 1px solid #99F6E4;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-grid td {
            width: 25%;
            text-align: center;
            padding: 4px;
        }
        .summary-label {
            font-size: 10px;
            color: #0F766E;
            text-transform: uppercase;
            font-weight: bold;
        }
        .summary-value {
            font-size: 15px;
            font-weight: bold;
            color: #0F172A;
            margin-top: 2px;
        }
        h2.section-title {
            font-size: 13px;
            color: #0F172A;
            margin: 16px 0 6px;
            padding-left: 8px;
            border-left: 4px solid #0D9488;
            text-transform: uppercase;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #CBD5E1;
            padding: 7px 8px;
            text-align: left;
            vertical-align: top;
            font-size: 10.5px;
        }
        table.data-table th {
            background-color: #0F172A;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        .badge-habis { color: #DC2626; font-weight: bold; }
        .badge-menipis { color: #D97706; font-weight: bold; }
        .badge-aman { color: #059669; font-weight: bold; }
        .cat-Star { color: #059669; font-weight: bold; }
        .cat-Plowhorse { color: #2563EB; font-weight: bold; }
        .cat-Puzzle { color: #D97706; font-weight: bold; }
        .cat-Dog { color: #DC2626; font-weight: bold; }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9.5px;
            color: #64748B;
            border-top: 1px solid #CBD5E1;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>LAPORAN REKOMENDASI SPK (METODE SAW & MATRIKS BCG)</h1>
        <p>STOKKU ERP - Sistem Manajemen Warung Sembako & Sayur • Periode Analisis: {{ $days }} Hari Terakhir</p>
        <p>Dicetak pada: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }} WIB • Bobot SAW: C1 Stok ({{ $weights['c1_pct'] }}%), C2 Cepat Busuk ({{ $weights['c2_pct'] }}%), C3 Kelarisan ({{ $weights['c3_pct'] }}%), C4 Margin ({{ $weights['c4_pct'] }}%)</p>
    </div>

    <div class="summary-box">
        <table class="summary-grid">
            <tr>
                <td>
                    <div class="summary-label">Komoditas Wajib Beli</div>
                    <div class="summary-value">{{ $sawSummary['urgent_count'] }} Item</div>
                </td>
                <td>
                    <div class="summary-label">Estimasi Modal Pasar</div>
                    <div class="summary-value">Rp {{ number_format($sawSummary['total_estimated_cost'], 0, ',', '.') }}</div>
                </td>
                <td>
                    <div class="summary-label">Standar Laris BCG</div>
                    <div class="summary-value">{{ number_format($avg_mm, 1) }}%</div>
                </td>
                <td>
                    <div class="summary-label">Confidence Score</div>
                    <div class="summary-value">{{ $datasetConfidence }}%</div>
                </td>
            </tr>
        </table>
    </div>

    <h2 class="section-title">1. Peringkat Prioritas Belanja Pasar Pagi (Metode SAW)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th width="6%">Rank</th>
                <th width="20%">Komoditas & Kategori</th>
                <th width="14%">Kondisi Stok</th>
                <th width="10%">Skor SAW</th>
                <th width="16%">Saran Restock & Modal</th>
                <th width="34%">Saran Aksi Warung</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sawItems as $item)
                <tr>
                    <td style="font-weight: bold; text-align: center;">#{{ $item->rank }}</td>
                    <td>
                        <strong>{{ $item->name }}</strong><br>
                        <span style="color: #64748B; font-size: 9.5px;">{{ $item->kategori }} • BCG: {{ $item->bcg_category }}</span>
                    </td>
                    <td class="badge-{{ $item->stock_status }}">
                        {{ strtoupper($item->stock_status) }}<br>
                        <span style="color: #475569; font-weight: normal; font-size: 9.5px;">
                            Sisa: {{ rtrim(rtrim(number_format($item->current_stock, 2, ',', '.'), '0'), ',') }} {{ $item->unit }}
                        </span>
                    </td>
                    <td style="font-weight: bold; font-size: 12px; color: #0D9488;">
                        {{ number_format($item->saw_score, 2) }}
                    </td>
                    <td>
                        <strong>{{ rtrim(rtrim(number_format($item->recommended_restock_qty, 2, ',', '.'), '0'), ',') }} {{ $item->unit }}</strong><br>
                        <span style="color: #0F766E; font-size: 9.5px;">±Rp {{ number_format($item->estimated_cost, 0, ',', '.') }}</span>
                    </td>
                    <td>{{ $item->saran_aksi }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2 class="section-title">2. Evaluasi Portofolio Komoditas Warung (Matriks BCG)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th width="22%">Komoditas Etalase</th>
                <th width="16%">Kuadran BCG</th>
                <th width="14%">Menu Mix (Laris)</th>
                <th width="15%">Margin / Unit</th>
                <th width="33%">Rekomendasi Strategi BCG</th>
            </tr>
        </thead>
        <tbody>
            @foreach($menus as $menu)
                <tr>
                    <td>
                        <strong>{{ $menu->name }}</strong><br>
                        <span style="font-size: 9.5px; color: #64748B;">Confidence: {{ $menu->confidence_score }}%</span>
                    </td>
                    <td class="cat-{{ $menu->category }}">
                        {{ $menu->category_label ?? $menu->category }}
                    </td>
                    <td>
                        {{ number_format($menu->mm_percent, 1) }}% ({{ number_format($menu->sold_qty, 0, ',', '.') }} unit)
                    </td>
                    <td>
                        Rp {{ number_format($menu->cm_per_item, 0, ',', '.') }}
                    </td>
                    <td>{{ $menu->action }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh Sistem Pendukung Keputusan (SPK Metode SAW & Matriks BCG) STOKKU ERP.
    </div>

</body>
</html>
