<x-app-layout>
    <div x-data="regressionAnalysisManager()"
        class="space-y-6 pb-24">
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

        {{-- Interpretasi Tren & Saran Stok AI (Google Gemini 2.5 Flash) --}}
        <section class="bg-gradient-to-br from-white via-teal-50/20 to-white dark:from-slate-900 dark:via-teal-950/20 dark:to-slate-900 rounded-3xl p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/40 border border-teal-200/80 dark:border-teal-500/30 flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-teal-100 dark:border-teal-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-500/20 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">trending_up</span>
                    </div>
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-teal-100/80 dark:bg-teal-950/80 text-[#0D9488] dark:text-teal-300 text-[10px] font-black uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse"></span>
                            AI Predictive Analytics &bull; Google Gemini 2.5 Flash
                        </div>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F172A] dark:text-slate-100 mt-0.5">
                            Interpretasi Tren &amp; Saran Stok AI
                        </h2>
                    </div>
                </div>

                <button type="button"
                        @click="generatePrediksiInsight()"
                        :disabled="isGeneratingPrediksi"
                        class="py-2.5 px-5 rounded-2xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white text-xs sm:text-sm font-extrabold shadow-md shadow-teal-500/30 flex items-center justify-center gap-2 transition active:scale-95 cursor-pointer disabled:opacity-50 shrink-0">
                    <template x-if="isGeneratingPrediksi">
                        <span class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Menerjemahkan Tren...</span>
                        </span>
                    </template>
                    <template x-if="!isGeneratingPrediksi">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[19px]">auto_awesome</span>
                            <span>Analisis Tren Permintaan AI</span>
                        </span>
                    </template>
                </button>
            </div>

            <!-- Kontainer Hasil Interpretasi AI Regresi -->
            <div x-show="prediksiInsight" x-cloak class="p-5 rounded-2xl bg-white dark:bg-slate-950 border border-teal-200 dark:border-teal-900/60 shadow-inner">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-teal-600">verified</span>
                        <span class="text-xs font-extrabold text-slate-900 dark:text-slate-100">Interpretasi Matematis Regresi Linier (Bahasa Warung &amp; Aksi Kulakan)</span>
                    </div>
                    <button type="button"
                            @click="navigator.clipboard.writeText(prediksiInsight); alert('Interpretasi tren AI berhasil disalin!')"
                            class="text-[11px] font-bold text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">content_copy</span>
                        <span>Salin Teks</span>
                    </button>
                </div>
                <div x-html="formatAiMarkdown(prediksiInsight)" class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed space-y-2"></div>
            </div>
        </section>

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

    <script>
        window.regressionAnalysisManager = function() {
            return {
                isGeneratingPrediksi: false,
                prediksiInsight: '',
                formatAiMarkdown(text) {
                    if (!text) return '';
                    let escaped = text
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                    escaped = escaped.replace(/^#### (.*$)/gim, '<h5 class="text-xs font-black text-teal-800 dark:text-teal-300 mt-2 mb-1">$1</h5>');
                    escaped = escaped.replace(/^### (.*$)/gim, '<h4 class="text-sm font-black text-teal-700 dark:text-teal-300 mt-2.5 mb-1">$1</h4>');
                    escaped = escaped.replace(/^## (.*$)/gim, '<h3 class="text-base font-black text-slate-900 dark:text-slate-100 mt-3 mb-1.5">$1</h3>');
                    escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900 dark:text-slate-100">$1</strong>');
                    escaped = escaped.replace(/\*(.*?)\*/g, '<em class="italic text-slate-600 dark:text-slate-400">$1</em>');
                    escaped = escaped.replace(/^\s*-\s+(.*$)/gim, '<li class="ml-4 list-disc text-xs leading-relaxed text-slate-700 dark:text-slate-300">$1</li>');
                    escaped = escaped.replace(/\n/g, '<br>');
                    return escaped;
                },
                async generatePrediksiInsight() {
                    if (this.isGeneratingPrediksi) return;
                    this.isGeneratingPrediksi = true;
                    try {
                        const res = await fetch('{{ route('analysis.ai_prediksi') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            }
                        });
                        const data = await res.json();
                        if (data && data.interpretation) {
                            this.prediksiInsight = data.interpretation;
                        }
                    } catch (e) {
                        this.prediksiInsight = 'Terjadi kendala saat menganalisis interpretasi tren permintaan.';
                    } finally {
                        this.isGeneratingPrediksi = false;
                    }
                }
            };
        };

        document.addEventListener('alpine:init', () => {
            if (typeof Alpine !== 'undefined' && typeof window.regressionAnalysisManager === 'function') {
                Alpine.data('regressionAnalysisManager', window.regressionAnalysisManager);
            }
        });
    </script>
</x-app-layout>
