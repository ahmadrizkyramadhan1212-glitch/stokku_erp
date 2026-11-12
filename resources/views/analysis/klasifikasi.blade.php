<x-app-layout>
    <div x-data="bcgAnalysisManager()"
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
                        Matriks BCG (Boston Consulting Group) • Confidence Score {{ $dataScore['score_percent'] ?? 80 }}%
                    </div>
                    <h1 class="text-lg sm:text-2xl font-extrabold tracking-tight text-[#0F172A] dark:text-slate-100">Klasifikasi Portofolio Barang (Matriks BCG)</h1>
                    <p class="text-xs text-[#64748B] mt-0.5">
                        Pengelompokan 4 kuadran komoditas etalase warung (Star, Plowhorse, Puzzle, Dog) berdasarkan kelarisan &amp; margin keuntungan.
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

        {{-- Indikator Confidence Score Sistem --}}
        @php
            $confStyles = match($dataScore['level']) {
                'low' => ['bg' => 'bg-red-50 border-red-200 text-red-900', 'badge' => 'bg-red-600 text-white'],
                'moderate' => ['bg' => 'bg-amber-50 border-amber-200 text-amber-900', 'badge' => 'bg-amber-500 text-slate-950'],
                default => ['bg' => 'bg-emerald-50 border-emerald-200 text-emerald-900', 'badge' => 'bg-emerald-600 text-white'],
            };
        @endphp
        <div class="rounded-2xl border p-4 sm:p-5 {{ $confStyles['bg'] }} flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-11 h-11 rounded-2xl bg-white/80 flex items-center justify-center shrink-0 shadow-sm">
                    <span class="material-symbols-outlined text-[#0D9488]">verified</span>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-sm font-extrabold">Indikator Confidence Score: {{ $dataScore['label'] }}</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold {{ $confStyles['badge'] }}">
                            Confidence Score: {{ $dataScore['score_percent'] ?? 80 }}%
                        </span>
                    </div>
                    <p class="text-xs mt-1 leading-relaxed opacity-90">{{ $dataScore['message'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 shrink-0 text-center">
                <div class="bg-white/80 rounded-xl px-3 py-2 border border-black/5">
                    <p class="text-[10px] font-bold uppercase opacity-70">Standar Laris</p>
                    <p class="text-sm font-extrabold">{{ $classificationResult['threshold_qty'] }} unit ({{ $classificationResult['avg_mm'] ?? 0 }}%)</p>
                </div>
                <div class="bg-white/80 rounded-xl px-3 py-2 border border-black/5">
                    <p class="text-[10px] font-bold uppercase opacity-70">Standar Margin</p>
                    <p class="text-sm font-extrabold">Rp {{ number_format($classificationResult['threshold_margin'], 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Ringkasan 4 Kuadran BCG --}}
        @php $qc = $classificationResult['quadrant_counts'] ?? ['Star'=>0,'Plowhorse'=>0,'Puzzle'=>0,'Dog'=>0]; @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl p-4 border border-slate-200 border-t-4 border-t-emerald-500 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-emerald-700">⭐ STAR (Unggulan)</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-800">{{ $qc['Star'] ?? 0 }}</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1 dark:text-slate-400">Volume penjualan tinggi & margin keuntungan tebal.</p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-slate-200 border-t-4 border-t-blue-500 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-blue-700">🐴 PLOWHORSE (Laris)</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-100 text-blue-800">{{ $qc['Plowhorse'] ?? 0 }}</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1 dark:text-slate-400">Penarik trafik pelanggan, laris namun margin tipis.</p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-slate-200 border-t-4 border-t-amber-500 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-amber-700">🧩 PUZZLE (Potensial)</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-800">{{ $qc['Puzzle'] ?? 0 }}</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1 dark:text-slate-400">Margin keuntungan tinggi namun volume masih rendah.</p>
            </div>
            <div class="bg-white rounded-2xl p-4 border border-slate-200 border-t-4 border-t-red-500 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold text-red-700">🐶 DOG (Evaluasi)</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-red-100 text-red-800">{{ $qc['Dog'] ?? 0 }}</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1 dark:text-slate-400">Perputaran lambat & margin tipis, kurangi kulakan.</p>
            </div>
        </div>

        {{-- Panel AI Strategic Insight (BCG) --}}
        <section class="bg-gradient-to-br from-white via-teal-50/20 to-white dark:from-slate-900 dark:via-teal-950/20 dark:to-slate-900 rounded-3xl p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/40 border border-teal-200/80 dark:border-teal-500/30 flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-teal-100 dark:border-teal-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-500/20 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">psychology</span>
                    </div>
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-teal-100/80 dark:bg-teal-950/80 text-[#0D9488] dark:text-teal-300 text-[10px] font-black uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse"></span>
                            AI Strategic Insight (BCG) &bull; Google Gemini 2.5 Flash
                        </div>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F172A] dark:text-slate-100 mt-0.5">
                            Konsultan Strategi Portofolio &amp; Anti-Waste Warung
                        </h2>
                    </div>
                </div>

                <button type="button"
                        @click="generateBcgInsight()"
                        :disabled="isGeneratingBcg"
                        class="py-2.5 px-5 rounded-2xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white text-xs sm:text-sm font-extrabold shadow-md shadow-teal-500/30 flex items-center justify-center gap-2 transition active:scale-95 cursor-pointer disabled:opacity-50 shrink-0">
                    <template x-if="isGeneratingBcg">
                        <span class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Menganalisis Portofolio...</span>
                        </span>
                    </template>
                    <template x-if="!isGeneratingBcg">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[19px]">auto_awesome</span>
                            <span>Generate Analisis AI</span>
                        </span>
                    </template>
                </button>
            </div>

            <!-- Kontainer Hasil Analisis AI BCG -->
            <div x-show="bcgInsight" x-cloak class="p-5 rounded-2xl bg-white dark:bg-slate-950 border border-teal-200 dark:border-teal-900/60 shadow-inner">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-teal-600">verified</span>
                        <span class="text-xs font-extrabold text-slate-900 dark:text-slate-100">Arahan Bisnis Owner: Jam Sibuk &amp; Bundling Anti-Waste</span>
                    </div>
                    <button type="button"
                            @click="navigator.clipboard.writeText(bcgInsight); alert('Analisis BCG berhasil disalin!')"
                            class="text-[11px] font-bold text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">content_copy</span>
                        <span>Salin Teks</span>
                    </button>
                </div>
                <div x-html="formatAiMarkdown(bcgInsight)" class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed space-y-2"></div>
            </div>
        </section>

        {{-- Daftar Kartu Komoditas Hasil Klasifikasi BCG --}}
        @if(empty($classificationResult['items']))
            <div class="text-center py-12 bg-white rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-sm font-bold text-slate-600 dark:text-slate-400">Belum ada komoditas terdaftar di etalase warung.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($classificationResult['items'] as $item)
                    @php
                        $borderClass = match($item['bcg_quadrant']) {
                            'Star' => 'border-l-emerald-500',
                            'Plowhorse' => 'border-l-blue-500',
                            'Puzzle' => 'border-l-amber-500',
                            default => 'border-l-red-500',
                        };
                        $badgeClass = match($item['bcg_quadrant']) {
                            'Star' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'Plowhorse' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'Puzzle' => 'bg-amber-100 text-amber-800 border-amber-200',
                            default => 'bg-red-100 text-red-800 border-red-200',
                        };
                    @endphp
                    <div class="bg-white rounded-2xl p-6 border border-slate-200 border-l-4 {{ $borderClass }} shadow-sm flex flex-col justify-between gap-4">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-extrabold border {{ $badgeClass }}">
                                            {{ $item['badge'] }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:text-slate-200">
                                            {{ $item['kategori'] }}
                                        </span>
                                        @if($item['is_new_item'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-800">
                                                BARANG BARU (0 Transaksi)
                                            </span>
                                        @endif
                                    </div>
                                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900 mt-1.5 dark:text-slate-100">{{ $item['name'] }}</h3>
                                </div>

                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-[11px] font-extrabold bg-teal-50 text-[#0D9488] border border-slate-200 shrink-0">
                                    Confidence: {{ $item['confidence_score'] }}%
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3 bg-slate-50 rounded-xl p-3 border border-slate-100">
                                <div>
                                    <p class="text-[11px] text-slate-400 font-semibold">Total Terjual (Menu Mix)</p>
                                    <p class="text-base font-extrabold text-slate-900 mt-0.5 dark:text-slate-100">
                                        {{ number_format($item['total_qty'], 0, ',', '.') }} {{ $item['unit'] }}
                                        <span class="text-xs font-bold text-[#0D9488]">({{ number_format($item['mm_percent'], 1) }}%)</span>
                                    </p>
                                </div>
                                <div class="border-l border-slate-200 pl-3">
                                    <p class="text-[11px] text-slate-400 font-semibold">Margin Untung / {{ ucfirst($item['unit']) }}</p>
                                    <p class="text-base font-extrabold text-slate-900 mt-0.5 dark:text-slate-100">
                                        Rp {{ number_format($item['profit_per_unit'], 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>

                            {{-- Kotak Gelap Saran Aksi Warung --}}
                            <div class="mt-3 bg-[#0F172A] text-white rounded-xl p-3 flex items-start gap-3">
                                <span class="material-symbols-outlined text-amber-400 text-[18px] mt-0.5">lightbulb</span>
                                <p class="text-xs text-slate-200 leading-relaxed">
                                    <span class="font-extrabold text-white">SARAN AKSI WARUNG:</span> {{ $item['advice'] }}
                                </p>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                            <span>Harga Eceran: <strong class="text-slate-800 dark:text-slate-100">Rp {{ number_format($item['price_eceran'], 0, ',', '.') }}</strong></span>
                            <span>Akurasi Item: <strong class="text-slate-700 dark:text-slate-200">{{ $item['confidence_badge'] }}</strong></span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <script>
        window.bcgAnalysisManager = function() {
            return {
                isGeneratingBcg: false,
                bcgInsight: '',
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
                async generateBcgInsight() {
                    if (this.isGeneratingBcg) return;
                    this.isGeneratingBcg = true;
                    try {
                        const res = await fetch('{{ route('analysis.ai_bcg') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            }
                        });
                        const data = await res.json();
                        if (data && data.insight) {
                            this.bcgInsight = data.insight;
                        }
                    } catch (e) {
                        this.bcgInsight = 'Terjadi kesalahan saat memproses analisis portofolio BCG.';
                    } finally {
                        this.isGeneratingBcg = false;
                    }
                }
            };
        };

        document.addEventListener('alpine:init', () => {
            if (typeof Alpine !== 'undefined' && typeof window.bcgAnalysisManager === 'function') {
                Alpine.data('bcgAnalysisManager', window.bcgAnalysisManager);
            }
        });
    </script>
</x-app-layout>
