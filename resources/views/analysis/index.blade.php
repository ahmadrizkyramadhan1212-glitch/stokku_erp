<x-app-layout>
    <div class="px-3 pt-4 pb-24">
        {{-- Header --}}
        <div class="flex items-center space-x-3 mb-6">
            <a href="{{ route('reports.index') }}" class="p-2 bg-gray-100 rounded-full text-gray-600 hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Analisis Data <span class="text-purple-600">AI</span></h2>
                <p class="text-sm text-gray-500 mt-1">Decision Tree & Linear Regression</p>
            </div>
        </div>

        {{-- =============================== --}}
        {{-- WARNING: DATA CONFIDENCE SCORE --}}
        {{-- =============================== --}}
        @php $c = $dataScore['color']; @endphp
        <div class="bg-{{ $c }}-50 border border-{{ $c }}-200 rounded-2xl p-4 mb-6">
            <div class="flex items-start space-x-3">
                <div class="bg-{{ $c }}-100 p-2 rounded-xl flex-shrink-0">
                    @if($dataScore['level'] === 'low')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-red-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    @elseif($dataScore['level'] === 'moderate')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-amber-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-emerald-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-{{ $c }}-900 text-sm">Tingkat Kepercayaan Data: {{ $dataScore['label'] }} ({{ $dataScore['overall'] }}%)</h4>
                    <p class="text-xs text-{{ $c }}-700 mt-1 leading-relaxed">{{ $dataScore['message'] }}</p>

                    {{-- Progress bars --}}
                    <div class="mt-3 space-y-2">
                        @foreach(['days' => 'Hari Penjualan', 'records' => 'Record Transaksi', 'menus' => 'Variasi Menu'] as $key => $label)
                        <div>
                            <div class="flex justify-between text-[10px] font-bold text-{{ $c }}-800 mb-0.5">
                                <span>{{ $label }}</span>
                                <span>{{ $dataScore['detail'][$key]['value'] }} / {{ $dataScore['detail'][$key]['target'] }}</span>
                            </div>
                            <div class="w-full bg-{{ $c }}-100 rounded-full h-1.5">
                                <div class="bg-{{ $c }}-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ min($dataScore['detail'][$key]['percent'], 100) }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @if($dataScore['level'] === 'low')
            <div class="mt-3 bg-red-100 rounded-xl p-3 border border-red-200">
                <p class="text-[10px] text-red-800 font-bold leading-relaxed">
                    ⚠️ <strong>PERHATIAN:</strong> Dengan data yang masih sedikit, hasil prediksi dan klasifikasi di bawah ini memiliki <strong>akurasi rendah</strong> dan <strong>tidak merepresentasikan kondisi bisnis sebenarnya</strong>. Gunakan aplikasi POS dan Inventori secara rutin untuk mengakumulasi data yang lebih banyak.
                </p>
            </div>
            @endif
        </div>

        {{-- =============================== --}}
        {{-- DECISION TREE — KLASIFIKASI     --}}
        {{-- =============================== --}}
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mb-5">
            <div class="flex items-center space-x-3 mb-4">
                <div class="bg-purple-50 p-3 rounded-2xl">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-purple-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">🌲 Decision Tree</h3>
                    <p class="text-[10px] text-gray-500">Klasifikasi otomatis performa menu</p>
                </div>
            </div>

            {{-- Penjelasan model --}}
            <div class="bg-purple-50 rounded-xl p-3 mb-4 border border-purple-100">
                <p class="text-[10px] text-purple-800 leading-relaxed">
                    <strong>Tujuan:</strong> Mengklasifikasi setiap menu ke kategori <strong>Star, Plowhorse, Puzzle, Dog</strong> menggunakan pohon keputusan berdasarkan Popularitas (Menu Mix%) dan Profitabilitas (Contribution Margin). Hasil dibandingkan dengan metode BCG konvensional.
                </p>
            </div>

            @if(empty($classificationResult['menus']))
                <div class="text-center py-6 text-gray-400">
                    <p class="text-sm font-semibold">Belum ada data penjualan</p>
                    <p class="text-xs mt-1">Mulai catat penjualan di halaman Kasir</p>
                </div>
            @else
                {{-- Match rate --}}
                <div class="flex items-center justify-between bg-gray-50 rounded-xl p-3 mb-4 border">
                    <span class="text-xs font-bold text-gray-600">Kecocokan DT vs BCG:</span>
                    <span class="text-sm font-black {{ $classificationResult['match_percent'] >= 80 ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ $classificationResult['match_percent'] }}%
                    </span>
                </div>

                {{-- Table --}}
                <div class="space-y-3">
                    @foreach($classificationResult['menus'] as $menu)
                    @php
                        $catColors = [
                            'Star' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'icon' => '⭐'],
                            'Plowhorse' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'icon' => '🐴'],
                            'Puzzle' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => '🧩'],
                            'Dog' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => '🐶'],
                        ];
                        $cat = $catColors[$menu['dt_category']] ?? $catColors['Dog'];
                    @endphp
                    <div class="border border-gray-100 rounded-2xl p-4 {{ $cat['bg'] }}/30">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">{{ $menu['name'] }}</h4>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold {{ $cat['bg'] }} {{ $cat['text'] }}">
                                        {{ $cat['icon'] }} {{ $menu['dt_category'] }}
                                    </span>
                                    <span class="text-[10px] text-gray-500 font-semibold">
                                        Confidence: {{ $menu['dt_confidence'] }}%
                                    </span>
                                </div>
                            </div>
                            @if($menu['match'])
                                <span class="text-[10px] bg-emerald-100 text-emerald-700 font-bold px-2 py-1 rounded-lg">✓ BCG Match</span>
                            @else
                                <span class="text-[10px] bg-red-100 text-red-700 font-bold px-2 py-1 rounded-lg">≠ BCG: {{ $menu['bcg_category'] }}</span>
                            @endif
                        </div>
                        {{-- Decision path --}}
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach($menu['dt_path'] as $step)
                            <span class="text-[9px] bg-white/80 border border-gray-200 text-gray-600 px-2 py-0.5 rounded-md font-mono">{{ $step }}</span>
                            @endforeach
                        </div>
                        <div class="mt-2 grid grid-cols-3 gap-2 text-center">
                            <div class="bg-white/60 rounded-lg p-1.5">
                                <p class="text-[9px] text-gray-500 font-bold">QTY</p>
                                <p class="text-xs font-black text-gray-800">{{ number_format($menu['qty']) }}</p>
                            </div>
                            <div class="bg-white/60 rounded-lg p-1.5">
                                <p class="text-[9px] text-gray-500 font-bold">CM/item</p>
                                <p class="text-xs font-black text-gray-800">Rp{{ number_format($menu['cm_per_item']) }}</p>
                            </div>
                            <div class="bg-white/60 rounded-lg p-1.5">
                                <p class="text-[9px] text-gray-500 font-bold">MM%</p>
                                <p class="text-xs font-black text-gray-800">{{ $menu['mm_percent'] }}%</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- =============================== --}}
        {{-- LINEAR REGRESSION — PREDIKSI    --}}
        {{-- =============================== --}}
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mb-5">
            <div class="flex items-center space-x-3 mb-4">
                <div class="bg-sky-50 p-3 rounded-2xl">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-sky-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">📈 Linear Regression</h3>
                    <p class="text-[10px] text-gray-500">Prediksi volume penjualan harian</p>
                </div>
            </div>

            {{-- Penjelasan model --}}
            <div class="bg-sky-50 rounded-xl p-3 mb-4 border border-sky-100">
                <p class="text-[10px] text-sky-800 leading-relaxed">
                    <strong>Tujuan:</strong> Memprediksi jumlah penjualan harian setiap menu berdasarkan tren historis. Formula: <strong>Y = a + bX</strong>. Berguna untuk perencanaan produksi dan pengadaan bahan baku.
                </p>
            </div>

            @if(empty($regressionResult['predictions']))
                <div class="text-center py-6 text-gray-400">
                    <p class="text-sm font-semibold">Belum ada data penjualan</p>
                    <p class="text-xs mt-1">Mulai catat penjualan di halaman Kasir</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($regressionResult['predictions'] as $pred)
                    <div class="border border-gray-100 rounded-2xl p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">{{ $pred['name'] }}</h4>
                                <p class="text-[10px] text-gray-500 font-mono mt-0.5">{{ $pred['formula'] }}</p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold
                                    {{ $pred['trend'] === 'naik' ? 'bg-emerald-100 text-emerald-700' : ($pred['trend'] === 'turun' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700') }}">
                                    {{ $pred['trend'] === 'naik' ? '📈 Naik' : ($pred['trend'] === 'turun' ? '📉 Turun' : '➡️ Stabil') }}
                                </span>
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="grid grid-cols-3 gap-2 mb-3">
                            <div class="bg-gray-50 rounded-xl p-2 text-center">
                                <p class="text-[9px] text-gray-500 font-bold">Data Points</p>
                                <p class="text-sm font-black text-gray-800">{{ $pred['data_points'] }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-2 text-center">
                                <p class="text-[9px] text-gray-500 font-bold">Rata-rata/hari</p>
                                <p class="text-sm font-black text-gray-800">{{ $pred['avg_daily'] }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-2 text-center">
                                <p class="text-[9px] text-gray-500 font-bold">R² Score</p>
                                <p class="text-sm font-black {{ $pred['r_squared_percent'] >= 50 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $pred['r_squared_percent'] }}%</p>
                            </div>
                        </div>

                        {{-- Predictions --}}
                        <div class="bg-sky-50 rounded-xl p-3 border border-sky-100">
                            <p class="text-[10px] font-bold text-sky-800 mb-2">Prediksi Mendatang:</p>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($pred['predictions'] as $p)
                                <div class="bg-white rounded-lg p-2 text-center border border-sky-100">
                                    <p class="text-[9px] text-sky-600 font-bold">{{ $p['day_label'] }}</p>
                                    <p class="text-base font-black text-sky-800">{{ $p['predicted_qty'] }}</p>
                                    <p class="text-[9px] text-gray-400">pcs</p>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        @if($pred['r_squared_percent'] < 50)
                        <p class="text-[9px] text-amber-600 font-semibold mt-2 text-center">
                            ⚠️ R² rendah — prediksi kurang akurat karena data terbatas
                        </p>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- =============================== --}}
        {{-- DOKUMENTASI MODEL               --}}
        {{-- =============================== --}}
        <div class="bg-gray-50 rounded-3xl p-6 border border-gray-200">
            <h3 class="text-sm font-bold text-gray-900 mb-3">📚 Tentang Model yang Digunakan</h3>
            <div class="space-y-3">
                <div class="bg-white rounded-xl p-3 border">
                    <h4 class="text-xs font-bold text-purple-700">🌲 Decision Tree (Klasifikasi)</h4>
                    <p class="text-[10px] text-gray-600 mt-1 leading-relaxed">Pohon keputusan yang mengklasifikasi menu berdasarkan dua variabel utama: <strong>Menu Mix %</strong> (popularitas) dan <strong>Contribution Margin</strong> (profitabilitas). Setiap node memecah data berdasarkan threshold yang dipelajari dari pola penjualan historis.</p>
                </div>
                <div class="bg-white rounded-xl p-3 border">
                    <h4 class="text-xs font-bold text-sky-700">📈 Linear Regression (Regresi)</h4>
                    <p class="text-[10px] text-gray-600 mt-1 leading-relaxed">Model regresi linier sederhana <strong>Y = a + bX</strong> yang memprediksi jumlah penjualan (Y) berdasarkan urutan waktu (X). Nilai <strong>R²</strong> menunjukkan seberapa baik model menjelaskan variasi data (0% = buruk, 100% = sempurna).</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
