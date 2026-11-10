<x-app-layout>
    <div class="space-y-6 pb-24">
        {{-- Clean White Header --}}
        <div class="bg-white rounded-3xl p-4 sm:p-6 text-[#0F172A] shadow-xs border border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 dark:text-slate-100">
            <div class="flex items-start gap-3">
                <a href="{{ route('spk.index') }}"
                    class="w-10 h-10 rounded-full bg-[#F1F5F9] hover:bg-slate-200 flex items-center justify-center text-[#0F172A] shrink-0 transition dark:text-slate-100">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <div>
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-teal-50 text-[#0D9488] border border-slate-200 text-[11px] font-bold mb-1">
                        Regresi Linier Permintaan • Confidence Score {{ $dataScore['score_percent'] ?? 80 }}%
                    </div>
                    <h1 class="text-lg sm:text-2xl font-extrabold tracking-tight text-[#0F172A] dark:text-slate-100">Prediksi Permintaan Komoditas Warung</h1>
                    <p class="text-xs text-[#64748B] mt-0.5">
                        Estimasi kebutuhan stok 3 hari ke depan agar kulakan sayur &amp; sembako tepat sasaran dan tidak membusuk.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('spk.index') }}"
                    class="px-3.5 py-2.5 rounded-xl bg-[#0D9488] text-white font-extrabold text-xs shadow-xs hover:bg-[#0F766E] inline-flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px]">calculate</span>
                    Ke Ranking SAW
                </a>
            </div>
        </div>

        {{-- Status Confidence Score --}}
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm flex items-start gap-3">
            <div class="w-10 h-10 rounded-2xl bg-teal-50 text-[#0D9488] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined">insights</span>
            </div>
            <div>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">
                    Status Kecerdasan Prediksi: {{ $dataScore['label'] }} (Confidence Score {{ $dataScore['score_percent'] ?? 80 }}%)
                </h3>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed dark:text-slate-400">{{ $dataScore['message'] }}</p>
            </div>
        </div>

        {{-- Daftar Hasil Prediksi --}}
        @if(empty($regressionResult['predictions']))
            <div class="text-center py-12 bg-white rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-sm font-bold text-slate-600 dark:text-slate-400">Belum ada komoditas terdaftar</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($regressionResult['predictions'] as $pred)
                    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-6 flex flex-col justify-between gap-4">
                        <div>
                            <div class="flex justify-between items-start gap-2 mb-3">
                                <div>
                                    <h4 class="font-extrabold text-slate-900 text-base dark:text-slate-100">{{ $pred['name'] }}</h4>
                                    <p class="text-xs text-slate-500 mt-0.5 dark:text-slate-400">
                                        Basis data: {{ $pred['data_points'] }} hari aktif • Rata-rata: <strong>{{ $pred['avg_daily'] }} {{ $pred['unit'] ?? 'unit' }}/hari</strong>
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-extrabold
                                    {{ $pred['trend'] === 'naik' ? 'bg-emerald-100 text-emerald-700' : ($pred['trend'] === 'turun' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700') }}">
                                    {{ $pred['trend'] === 'naik' ? '📈 Tren Naik' : ($pred['trend'] === 'turun' ? '📉 Tren Turun' : '➡️ Permintaan Stabil') }}
                                </span>
                            </div>

                            <div class="grid grid-cols-3 gap-3 mt-3">
                                @foreach($pred['predictions'] as $p)
                                    <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-200">
                                        <p class="text-[11px] text-[#0D9488] font-bold mb-1">{{ $p['day_label'] }}</p>
                                        <div class="text-lg font-extrabold text-slate-900 dark:text-slate-100">{{ $p['predicted_qty'] }}</div>
                                        <p class="text-[10px] text-slate-500 font-semibold mt-0.5 dark:text-slate-400">{{ $pred['unit'] ?? 'unit' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span class="text-slate-500 dark:text-slate-400">Confidence Score Prediksi:</span>
                            <span class="font-extrabold text-[#0D9488]">{{ $pred['confidence'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
