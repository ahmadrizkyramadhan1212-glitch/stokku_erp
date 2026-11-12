<x-app-layout>
    @php
        $user = Auth::user();
        $isOwner = $user ? $user->isOwner() : false;
        $sawItems = collect($sawItems ?? []);
        $waCard = $waCard ?? [];
        $sawSummary = $sawSummary ?? [];
        $habisCount = $sawItems->where('stock_status', 'habis')->count();
        $menipisCount = $sawItems->where('stock_status', 'menipis')->count();
        $amanCount = $sawItems->where('stock_status', 'aman')->count();
        $fifoList = $sawItems->where('is_fifo', true)->values();

        // Hitung ringkasan keuangan harian/bulanan dari data waCard & sawSummary untuk kartu atas
        $grossIncome = (float) ($waCard['today_revenue'] ?? 0);
        $shoppingModal = (float) ($sawSummary['total_estimated_cost'] ?? $sawSummary['total_modal'] ?? ($waCard['today_purchases'] ?? 0));
        $netProfit = (float) ($waCard['today_net_profit'] ?? max(0, $grossIncome - $shoppingModal));
        $totalOutflow = (float) (($waCard['today_purchases'] ?? 0) + ($waCard['today_expenses'] ?? 0));
        $maxChartVal = max(1, $grossIncome, $totalOutflow, $shoppingModal);
        $incomeBarPct = min(100, max(12, round(($grossIncome / $maxChartVal) * 100)));
        $outflowBarPct = min(100, max(12, round((($totalOutflow > 0 ? $totalOutflow : $shoppingModal) / $maxChartVal) * 100)));
    @endphp

    <div x-data="spkManager()"
        class="flex flex-col gap-6 pb-10">

        <!-- ===================================================================
             1. HEADER GRADASI GLOSSY TAJAM: LAPORAN USAHA & PRIORITAS BELANJA PASAR
             =================================================================== -->
        <section class="relative overflow-hidden rounded-3xl p-6 sm:p-6 bg-gradient-to-br from-[#0D9488] to-[#0F766E] text-white shadow-xl shadow-teal-500/30 border border-white/25">
            <!-- Dekorasi Cahaya Glossy -->
            <div class="pointer-events-none absolute -top-12 -right-12 w-44 h-44 rounded-full bg-white/15 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-14 -left-10 w-40 h-40 rounded-full bg-white/15 blur-2xl"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center text-white shrink-0 shadow-md">
                        <span class="material-symbols-outlined text-[26px]">checklist</span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded-full bg-white/20 backdrop-blur-md border border-white/30 text-white text-[10px] font-extrabold uppercase tracking-wider">
                                Panduan Belanja Subuh
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-400/25 border border-amber-300/40 text-white text-[10px] font-extrabold">
                                {{ $habisCount + $menipisCount }} Barang Perlu Dibeli
                            </span>
                        </div>
                        <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight">
                            Urutan Prioritas Belanja Pasar &amp; Ringkasan Usaha
                        </h1>
                        <p class="text-xs text-white/90 mt-0.5 leading-relaxed max-w-2xl">
                            Daftar barang yang paling mendesak untuk dibeli esok pagi berdasarkan kondisi stok di rak, kecepatan laku harian, dan daya tahan kesegaran barang.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <a href="{{ route('inventory.materials') }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-white/20 hover:bg-white/30 backdrop-blur-md text-white border border-white/30 text-xs font-extrabold shadow-sm transition active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                        <span>Update Stok Rak</span>
                    </a>
                    @if($isOwner)
                        <a href="{{ route('reports.index') }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-white text-teal-700 hover:bg-teal-50 text-xs font-extrabold shadow-md transition active:scale-95">
                            <span class="material-symbols-outlined text-[18px] text-teal-600">bar_chart</span>
                            <span>Laporan Keuangan</span>
                        </a>
                    @endif
                </div>
            </div>

            <!-- 3 Kartu Ringkasan Utama di Dalam Header Glossy: Finansial (Owner) atau Operasional (Staf) -->
            @if($isOwner)
                <div class="relative z-10 grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5">
                    <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90">Pemasukan Kotor</span>
                            <span class="material-symbols-outlined text-[18px] text-white">payments</span>
                        </div>
                        <p class="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1">
                            Rp {{ number_format($grossIncome, 0, ',', '.') }}
                        </p>
                        <span class="text-[11px] text-white/85 block mt-0.5">Omzet penjualan warung hari ini</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90">Modal Belanja</span>
                            <span class="material-symbols-outlined text-[18px] text-amber-200">shopping_bag</span>
                        </div>
                        <p class="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1">
                            Rp {{ number_format($shoppingModal, 0, ',', '.') }}
                        </p>
                        <span class="text-[11px] text-white/85 block mt-0.5">Estimasi modal {{ $habisCount + $menipisCount }} barang habis/menipis</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90">Keuntungan Bersih</span>
                            <span class="material-symbols-outlined text-[18px] text-emerald-300">trending_up</span>
                        </div>
                        <p class="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1">
                            Rp {{ number_format($netProfit, 0, ',', '.') }}
                        </p>
                        <span class="text-[11px] text-white/85 block mt-0.5">Laba bersih operasional warung</span>
                    </div>
                </div>
            @else
                <div class="relative z-10 grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5">
                    <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90">Stok Habis Total</span>
                            <span class="material-symbols-outlined text-[18px] text-rose-300">cancel</span>
                        </div>
                        <p class="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1">
                            {{ $habisCount }} <span class="text-sm font-semibold text-white/80">Barang</span>
                        </p>
                        <span class="text-[11px] text-white/85 block mt-0.5">Mendesak dibeli besok subuh</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90">Stok Menipis</span>
                            <span class="material-symbols-outlined text-[18px] text-amber-200">warning</span>
                        </div>
                        <p class="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1">
                            {{ $menipisCount }} <span class="text-sm font-semibold text-white/80">Barang</span>
                        </p>
                        <span class="text-[11px] text-white/85 block mt-0.5">Di bawah batas aman rak</span>
                    </div>

                    <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90">Wajib FIFO</span>
                            <span class="material-symbols-outlined text-[18px] text-amber-300">priority_high</span>
                        </div>
                        <p class="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1">
                            {{ $fifoList->count() }} <span class="text-sm font-semibold text-white/80">Komoditas</span>
                        </p>
                        <span class="text-[11px] text-white/85 block mt-0.5">Sisa kemarin - pajang paling depan</span>
                    </div>
                </div>
            @endif
        </section>

        <!-- ===================================================================
             2. GRAFIK BATANG MODERN (PENDAPATAN VS PENGELUARAN) & SISA KEMARIN
             =================================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <!-- Grafik Batang Pendapatan vs Pengeluaran (Owner) / Status Kesiapan Operasional Rak (Staf) -->
            <section class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between gap-4">
                @if($isOwner)
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-500/20 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[20px]">bar_chart</span>
                            </div>
                            <div>
                                <h2 class="text-sm sm:text-base font-extrabold text-[#0F172A] dark:text-slate-100">
                                    Perbandingan Pendapatan vs Pengeluaran
                                </h2>
                                <p class="text-xs text-slate-600 dark:text-slate-400">
                                    Ringkasan arus uang masuk dan modal belanja pasar
                                </p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-teal-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-teal-600 dark:text-teal-300 text-[11px] font-extrabold">
                            {{ now()->translatedFormat('d M Y') }}
                        </span>
                    </div>

                    <!-- Visual Bar Chart Biru-Indigo Menyala -->
                    <div class="space-y-4 py-1">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-[#0F172A] dark:text-slate-100 flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-teal-600"></span>
                                    Pendapatan Masuk (Omzet)
                                </span>
                                <strong class="font-extrabold text-teal-600 dark:text-teal-300 tabular-nums">
                                    Rp {{ number_format($grossIncome, 0, ',', '.') }}
                                </strong>
                            </div>
                            <div class="w-full h-4 rounded-full bg-teal-50 dark:bg-slate-950 overflow-hidden p-0.5 border border-slate-200 dark:border-slate-700">
                                <div class="h-full rounded-full bg-gradient-to-r from-[#0D9488] to-[#0F766E] shadow-sm transition-all duration-500"
                                     style="width: {{ $incomeBarPct }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-[#0F172A] dark:text-slate-100 flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                    Pengeluaran &amp; Estimasi Modal Belanja
                                </span>
                                <strong class="font-extrabold text-teal-600 dark:text-amber-400 tabular-nums">
                                    Rp {{ number_format($totalOutflow > 0 ? $totalOutflow : $shoppingModal, 0, ',', '.') }}
                                </strong>
                            </div>
                            <div class="w-full h-4 rounded-full bg-teal-50 dark:bg-slate-950 overflow-hidden p-0.5 border border-slate-200 dark:border-slate-700">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-500 shadow-sm transition-all duration-500"
                                     style="width: {{ $outflowBarPct }}%"></div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-500/20 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[20px]">inventory</span>
                            </div>
                            <div>
                                <h2 class="text-sm sm:text-base font-extrabold text-[#0F172A] dark:text-slate-100">
                                    Status Kesiapan Operasional Rak
                                </h2>
                                <p class="text-xs text-slate-600 dark:text-slate-400">
                                    Kondisi persediaan barang dagangan di rak warung
                                </p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full bg-teal-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-teal-600 dark:text-teal-300 text-[11px] font-extrabold">
                            {{ now()->translatedFormat('d M Y') }}
                        </span>
                    </div>

                    @php
                        $totalKomoditas = max(1, $sawItems->count());
                        $amanPct = min(100, round(($amanCount / $totalKomoditas) * 100));
                        $kritisPct = min(100, round((($habisCount + $menipisCount) / $totalKomoditas) * 100));
                    @endphp

                    <div class="space-y-4 py-1">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-[#0F172A] dark:text-slate-100 flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    Kesiapan Stok Aman
                                </span>
                                <strong class="font-extrabold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                    {{ $amanCount }} dari {{ $totalKomoditas }} Barang ({{ $amanPct }}%)
                                </strong>
                            </div>
                            <div class="w-full h-4 rounded-full bg-slate-100 dark:bg-slate-950 overflow-hidden p-0.5 border border-slate-200 dark:border-slate-700">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 shadow-sm transition-all duration-500"
                                     style="width: {{ $amanPct }}%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-[#0F172A] dark:text-slate-100 flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                                    Stok Kritis (Habis &amp; Menipis)
                                </span>
                                <strong class="font-extrabold text-rose-600 dark:text-rose-400 tabular-nums">
                                    {{ $habisCount + $menipisCount }} Barang ({{ $kritisPct }}%)
                                </strong>
                            </div>
                            <div class="w-full h-4 rounded-full bg-slate-100 dark:bg-slate-950 overflow-hidden p-0.5 border border-slate-200 dark:border-slate-700">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-rose-500 shadow-sm transition-all duration-500"
                                     style="width: {{ $kritisPct }}%"></div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <span class="text-slate-600 dark:text-slate-400">
                        Status Stok: <strong class="text-rose-600 dark:text-rose-400">{{ $habisCount }} Habis</strong> • <strong class="text-amber-600 dark:text-amber-400">{{ $menipisCount }} Menipis</strong> • <strong class="text-emerald-600 dark:text-emerald-400">{{ $amanCount }} Aman</strong>
                    </span>
                    <span class="font-extrabold text-teal-600 dark:text-teal-300">
                        Total {{ $sawItems->count() }} Komoditas
                    </span>
                </div>
            </section>

                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <span class="text-slate-600 dark:text-slate-400">
                        Status Stok: <strong class="text-rose-600 dark:text-rose-400">{{ $habisCount }} Habis</strong> • <strong class="text-amber-600 dark:text-amber-400">{{ $menipisCount }} Menipis</strong> • <strong class="text-emerald-600 dark:text-emerald-400">{{ $amanCount }} Aman</strong>
                    </span>
                    <span class="font-extrabold text-teal-600 dark:text-teal-300">
                        Total {{ $sawItems->count() }} Komoditas
                    </span>
                </div>
            </section>

            <!-- Kartu Pengingat Sisa Kemarin - Jual Duluan -->
            <section class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between gap-4">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-950/70 text-amber-900 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 text-xs font-extrabold">
                            <span class="material-symbols-outlined text-[16px] text-amber-600 dark:text-amber-400">priority_high</span>
                            Sisa Kemarin - Jual Duluan
                        </span>
                        <span class="text-xs font-extrabold text-teal-600 dark:text-teal-300">
                            {{ $fifoList->count() }} Barang
                        </span>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Barang segar di bawah ini masih memiliki stok sisa kemarin. Letakkan di bagian paling depan rak agar terjual lebih dulu sebelum membuka belanjaan baru:
                    </p>

                    <div class="flex flex-wrap gap-1.5">
                        @forelse($fifoList as $fItem)
                            <span class="px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-500/30 text-amber-950 dark:text-amber-200 text-xs font-extrabold">
                                {{ $fItem->name ?? $fItem->nama_barang ?? '-' }} ({{ number_format((float) ($fItem->current_stock ?? 0), 0, ',', '.') }} {{ $fItem->unit ?? 'Kg' }})
                            </span>
                        @empty
                            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold">
                                Semua komoditas segar di rak merupakan stok baru.
                            </span>
                        @endforelse
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">Cegah sayur &amp; bumbu layu di gudang</span>
                    <button type="button"
                            @click="filterStatus = 'SISA_KEMARIN'"
                            class="px-3.5 py-2 rounded-xl bg-teal-50 dark:bg-slate-950 hover:bg-teal-100 text-teal-600 dark:text-teal-300 border border-slate-200 dark:border-slate-700 text-xs font-extrabold cursor-pointer transition">
                        Filter Sisa Kemarin
                    </button>
                </div>
            </section>
        </div>

        <!-- ===================================================================
             AI SMART RESTOCK & ASISTEN ALOKASI MODAL BELANJA (GEMINI 2.5 FLASH)
             =================================================================== -->
        <section class="bg-gradient-to-br from-white via-teal-50/20 to-white dark:from-slate-900 dark:via-teal-950/20 dark:to-slate-900 rounded-3xl p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/40 border border-teal-200/80 dark:border-teal-500/30 flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-teal-100 dark:border-teal-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-500/20 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">smart_toy</span>
                    </div>
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-teal-100/80 dark:bg-teal-950/80 text-[#0D9488] dark:text-teal-300 text-[10px] font-black uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse"></span>
                            AI Smart Restock &bull; Google Gemini 2.5 Flash
                        </div>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F172A] dark:text-slate-100 mt-0.5">
                            Asisten Alokasi Modal Belanja Pasar
                        </h2>
                    </div>
                </div>

                <div class="text-xs text-slate-500 dark:text-slate-400">
                    Memadukan skor SAW &amp; proteksi sayur cepat busuk
                </div>
            </div>

            <!-- Kontrol Input Modal & Tombol Racik AI -->
            <div class="flex flex-col md:flex-row md:items-end gap-3">
                <div class="flex-1 space-y-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Simulasi Modal Belanja Hari Ini (Rp):
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Rp</span>
                        <input type="number"
                               x-model.number="aiBudgetModal"
                               step="50000"
                               min="50000"
                               placeholder="750000"
                               class="w-full bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-2xl pl-10 pr-4 py-2.5 text-sm font-extrabold text-[#0F172A] dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-teal-600 transition" />
                    </div>
                    <!-- Quick Preset Pills -->
                    <div class="flex items-center gap-1.5 flex-wrap pt-1">
                        <button type="button" @click="aiBudgetModal = 500000" class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition cursor-pointer" :class="aiBudgetModal === 500000 ? 'bg-teal-600 text-white border-teal-600' : 'bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100'">Rp 500.000</button>
                        <button type="button" @click="aiBudgetModal = 750000" class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition cursor-pointer" :class="aiBudgetModal === 750000 ? 'bg-teal-600 text-white border-teal-600' : 'bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100'">Rp 750.000</button>
                        <button type="button" @click="aiBudgetModal = 1000000" class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition cursor-pointer" :class="aiBudgetModal === 1000000 ? 'bg-teal-600 text-white border-teal-600' : 'bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100'">Rp 1.000.000</button>
                        <button type="button" @click="aiBudgetModal = 1500000" class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition cursor-pointer" :class="aiBudgetModal === 1500000 ? 'bg-teal-600 text-white border-teal-600' : 'bg-white dark:bg-slate-950 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100'">Rp 1.500.000</button>
                    </div>
                </div>

                <button type="button"
                        @click="generateAiRestock()"
                        :disabled="isGeneratingRestock"
                        class="py-2.5 px-5 rounded-2xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white text-xs sm:text-sm font-extrabold shadow-md shadow-teal-500/30 flex items-center justify-center gap-2 transition active:scale-95 cursor-pointer disabled:opacity-50 shrink-0">
                    <template x-if="isGeneratingRestock">
                        <span class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>AI Sedang Meracik...</span>
                        </span>
                    </template>
                    <template x-if="!isGeneratingRestock">
                        <span class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[19px]">auto_awesome</span>
                            <span>Racik Daftar Belanja AI</span>
                        </span>
                    </template>
                </button>
            </div>

            <!-- Card Hasil Rekomendasi Alokasi Belanja AI -->
            <div x-show="aiRestockResult" x-cloak class="mt-2 p-5 rounded-2xl bg-white dark:bg-slate-950 border border-teal-200 dark:border-teal-900/60 shadow-inner">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-teal-600">verified</span>
                        <span class="text-xs font-extrabold text-slate-900 dark:text-slate-100">Hasil Analisis Asisten Kulakan Pasar</span>
                    </div>
                    <button type="button"
                            @click="navigator.clipboard.writeText(aiRestockResult); alert('Daftar belanja AI berhasil disalin!')"
                            class="text-[11px] font-bold text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1 cursor-pointer">
                        <span class="material-symbols-outlined text-[15px]">content_copy</span>
                        <span>Salin Teks</span>
                    </button>
                </div>
                <div x-html="formatAiMarkdown(aiRestockResult)" class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed space-y-2"></div>
            </div>
        </section>

        <!-- ===================================================================
             3. DAFTAR URUTAN PRIORITAS BELANJA PASAR (BAHASA WARUNG BERSIH)
             Kolom: Urutan, Nama Barang, Status Stok, Kecepatan Laku, dan Saran Beli
             =================================================================== -->
        <section class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 flex flex-col gap-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-500/20 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">format_list_numbered</span>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F172A] dark:text-slate-100">
                            Urutan Prioritas Belanja Pasar Besok Pagi
                        </h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            Diurutkan mulai dari barang yang paling wajib dibeli besok subuh
                        </p>
                    </div>
                </div>

                <!-- Pencarian Cepat -->
                <div class="relative w-full md:w-72">
                    <span class="material-symbols-outlined text-teal-600 dark:text-teal-300 absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px]">search</span>
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Cari nama barang belanjaan..."
                           class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-2xl pl-9 pr-8 py-2 text-xs font-semibold text-[#0F172A] dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600" />
                </div>
            </div>

            <!-- Tombol Kapsul Filter Status -->
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                <button type="button"
                        @click="filterStatus = 'KRITIS'"
                        :class="filterStatus === 'KRITIS' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold shadow-md shadow-teal-500/30' : 'bg-teal-50 dark:bg-slate-950 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold'"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                    <span>Wajib Beli Besok ({{ $habisCount + $menipisCount }})</span>
                </button>
                <button type="button"
                        @click="filterStatus = 'HABIS'"
                        :class="filterStatus === 'HABIS' ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-500/30' : 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-500/30 font-bold'"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-current"></span>
                    <span>Stok Habis ({{ $habisCount }})</span>
                </button>
                <button type="button"
                        @click="filterStatus = 'MENIPIS'"
                        :class="filterStatus === 'MENIPIS' ? 'bg-amber-500 text-white font-extrabold shadow-md shadow-amber-500/30' : 'bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 font-bold'"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                    <span class="w-2 h-2 rounded-full bg-current"></span>
                    <span>Stok Menipis ({{ $menipisCount }})</span>
                </button>
                <button type="button"
                        @click="filterStatus = 'SISA_KEMARIN'"
                        :class="filterStatus === 'SISA_KEMARIN' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold shadow-md shadow-teal-500/30' : 'bg-teal-50 dark:bg-slate-950 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold'"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    <span>Sisa Kemarin - Jual Duluan ({{ $fifoList->count() }})</span>
                </button>
                <button type="button"
                        @click="filterStatus = 'ALL'"
                        :class="filterStatus === 'ALL' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold shadow-md shadow-teal-500/30' : 'bg-teal-50 dark:bg-slate-950 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold'"
                        class="px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                    Semua Barang ({{ $sawItems->count() }})
                </button>
            </div>

            <!-- Tabel Rapi & Kartu Responsif Urutan Prioritas Belanja Pasar Besok Pagi -->
            <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700">
                <table class="w-full min-w-[680px] text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 text-[11px] font-extrabold uppercase tracking-wider text-teal-700 dark:text-teal-300 bg-slate-50 dark:bg-slate-950">
                            <th class="px-4 py-3 w-20 align-middle">Urutan</th>
                            <th class="px-4 py-3 min-w-[220px] align-middle">Nama Barang</th>
                            <th class="px-4 py-3 min-w-[165px] align-middle">Status Stok</th>
                            <th class="px-4 py-3 min-w-[155px] align-middle">Kecepatan Laku</th>
                            <th class="px-4 py-3 min-w-[140px] text-right align-middle">Saran Beli</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-teal-100/70 dark:divide-teal-500/20 text-xs">
                        @foreach($sawItems as $item)
                            @php
                                $itemName = $item->name ?? $item->nama_barang ?? '-';
                                $itemKategori = $item->kategori ?? $item->category ?? '-';
                                $itemStatus = strtolower((string) ($item->stock_status ?? $item->status ?? 'aman'));
                                $itemUnit = $item->unit ?? 'Kg';
                                $itemCurrentStock = (float) ($item->current_stock ?? 0);
                                $itemRestockQty = (float) ($item->recommended_restock_qty ?? 0);
                                $itemIsFifo = (bool) ($item->is_fifo ?? false);
                                $itemImgUrl = $item->image_url ?? asset('images/commodities/default-commodity.svg');
                                $isHabis = $itemStatus === 'habis';
                                $isMenipis = $itemStatus === 'menipis';
                                $itemRank = (int) ($item->saw_rank ?? $item->rank ?? $item->peringkat ?? $loop->iteration);
                                $estCost = (float) ($item->estimated_cost ?? $item->estimated_restock_cost ?? 0);
                                $dailySpeed = (float) ($item->avg_daily_demand ?? (($item->sold_qty ?? 0) > 0 ? round(($item->sold_qty ?? 0) / max(1, $days ?? 30), 1) : ($item->popularity_score ?? 3)));
                                if ($dailySpeed >= 12) {
                                    $speedLabel = 'Sangat Laris (' . number_format($dailySpeed, 0, ',', '.') . ' ' . $itemUnit . '/hari)';
                                    $speedColor = 'text-teal-600 dark:text-teal-300 font-extrabold';
                                } elseif ($dailySpeed >= 5) {
                                    $speedLabel = 'Laris Stabil (' . number_format($dailySpeed, 0, ',', '.') . ' ' . $itemUnit . '/hari)';
                                    $speedColor = 'text-emerald-600 dark:text-emerald-400 font-bold';
                                } else {
                                    $speedLabel = 'Laku Harian (' . number_format(max(1, $dailySpeed), 0, ',', '.') . ' ' . $itemUnit . '/hari)';
                                    $speedColor = 'text-slate-600 dark:text-slate-400 font-semibold';
                                }
                            @endphp
                            <tr x-show="matchesRow(@js($itemName), @js($itemKategori), @js($itemStatus), {{ $itemIsFifo ? 'true' : 'false' }})"
                                class="hover:bg-teal-50/50 dark:hover:bg-teal-950/30 transition">
                                <!-- Urutan -->
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl {{ $itemRank <= 10 ? 'bg-gradient-to-tr from-[#0D9488] to-[#0F766E] text-white shadow-xs' : 'bg-teal-50 dark:bg-slate-950 text-teal-700 dark:text-teal-300 border border-slate-200 dark:border-slate-700' }} font-extrabold text-xs tabular-nums">
                                        #{{ $itemRank }}
                                    </span>
                                </td>

                                <!-- Nama Barang -->
                                <td class="px-4 py-3 align-middle whitespace-normal leading-relaxed">
                                    <div class="flex items-center gap-3 min-w-[200px]">
                                        <img src="{{ $itemImgUrl }}"
                                             alt="{{ $itemName }}"
                                             loading="lazy"
                                             onerror="this.onerror=null;this.src='{{ asset('images/commodities/default-commodity.svg') }}';"
                                             class="w-14 h-14 sm:w-16 sm:h-16 shrink-0 rounded-xl overflow-hidden object-cover object-center border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 block" />
                                        <div class="flex flex-col gap-0.5 min-w-0">
                                            <span class="font-extrabold text-sm text-[#0F172A] dark:text-slate-100 leading-snug break-words">{{ $itemName }}</span>
                                            <span class="text-[11px] text-slate-500 dark:text-slate-400 leading-normal">{{ $itemKategori }}</span>
                                            @if($isOwner && !empty($item->price_unset))
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-500/40 w-fit whitespace-nowrap">Harga Belum Diatur</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Status Stok (Badge Seragam + Sub-label Sisa Kemarin) -->
                                <td class="px-4 py-3 align-middle whitespace-normal leading-relaxed">
                                    <div class="flex flex-col items-start gap-1.5">
                                        @if($isHabis)
                                            <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-200 border border-rose-200 dark:border-rose-500/40 text-[10px] font-extrabold uppercase tracking-wide whitespace-nowrap">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 shrink-0"></span>
                                                <span>HABIS (0 {{ $itemUnit }})</span>
                                            </span>
                                        @elseif($isMenipis)
                                            <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-200 border border-amber-200 dark:border-amber-500/40 text-[10px] font-extrabold uppercase tracking-wide whitespace-nowrap">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>
                                                <span>MENIPIS ({{ number_format($itemCurrentStock, $itemCurrentStock == (int) $itemCurrentStock ? 0 : 1, ',', '.') }} {{ $itemUnit }})</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/40 text-[10px] font-extrabold uppercase tracking-wide whitespace-nowrap">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                <span>AMAN ({{ number_format($itemCurrentStock, $itemCurrentStock == (int) $itemCurrentStock ? 0 : 1, ',', '.') }} {{ $itemUnit }})</span>
                                            </span>
                                        @endif

                                        @if($itemIsFifo)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-500 text-white text-[10px] font-extrabold tracking-tight whitespace-nowrap shadow-2xs">
                                                <span>Sisa Kemarin - Jual Duluan</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Kecepatan Laku -->
                                <td class="px-4 py-3 align-middle whitespace-normal leading-relaxed">
                                    <span class="{{ $speedColor }} block">{{ $speedLabel }}</span>
                                </td>

                                <!-- Saran Beli -->
                                <td class="px-4 py-3 text-right align-middle whitespace-nowrap">
                                    @if($itemRestockQty > 0 && ($isHabis || $isMenipis))
                                        <div class="inline-flex flex-col items-end gap-0.5">
                                            <span class="px-3 py-1 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold text-xs tabular-nums shadow-xs">
                                                Beli +{{ number_format($itemRestockQty, 0, ',', '.') }} {{ $itemUnit }}
                                            </span>
                                            @if($isOwner)
                                                <span class="text-[11px] font-bold text-teal-600 dark:text-teal-300 tabular-nums">
                                                    ± Rp {{ number_format($estCost, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200/70 dark:border-emerald-500/30 text-[11px] font-extrabold">
                                            Stok Cukup
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ===================================================================
             4. KARTU PENGIRIMAN LAPORAN KE WHATSAPP OWNER
             =================================================================== -->
        @include('partials.wa-report-card', ['waCard' => $waCard, 'variant' => 'prioritas'])

    </div>

    <script>
        window.spkManager = function() {
            return {
                filterStatus: 'KRITIS',
                searchQuery: '',
                aiBudgetModal: 750000,
                isGeneratingRestock: false,
                aiRestockResult: '',
                formatAiMarkdown(text) {
                    if (!text) return '';
                    let escaped = text
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                    escaped = escaped.replace(/^### (.*$)/gim, '<h4 class="text-sm font-black text-teal-800 dark:text-teal-300 mt-2 mb-1">$1</h4>');
                    escaped = escaped.replace(/^## (.*$)/gim, '<h3 class="text-base font-black text-slate-900 dark:text-slate-100 mt-2 mb-1">$1</h3>');
                    escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900 dark:text-slate-100">$1</strong>');
                    escaped = escaped.replace(/\*(.*?)\*/g, '<em class="italic text-slate-600 dark:text-slate-400">$1</em>');
                    escaped = escaped.replace(/^\s*-\s+(.*$)/gim, '<li class="ml-4 list-disc text-xs leading-relaxed text-slate-700 dark:text-slate-300">$1</li>');
                    escaped = escaped.replace(/\n/g, '<br>');
                    return escaped;
                },
                async generateAiRestock() {
                    if (this.isGeneratingRestock) return;
                    this.isGeneratingRestock = true;
                    try {
                        const res = await fetch('{{ route('spk.ai_recommendation') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({ budget_modal: this.aiBudgetModal, days: {{ $days ?? 30 }} })
                        });
                        const data = await res.json();
                        if (data && data.recommendation) {
                            this.aiRestockResult = data.recommendation;
                        }
                    } catch (e) {
                        this.aiRestockResult = 'Terjadi kendala saat meracik rekomendasi belanja AI.';
                    } finally {
                        this.isGeneratingRestock = false;
                    }
                },
                matchesRow(name, kategori, status, isFifo) {
                    const q = (this.searchQuery || '').trim().toLowerCase();
                    if (q.length > 0) {
                        if (!String(name).toLowerCase().includes(q) && !String(kategori).toLowerCase().includes(q)) {
                            return false;
                        }
                    }
                    if (this.filterStatus === 'ALL') return true;
                    if (this.filterStatus === 'KRITIS') return status === 'habis' || status === 'menipis';
                    if (this.filterStatus === 'HABIS') return status === 'habis';
                    if (this.filterStatus === 'MENIPIS') return status === 'menipis';
                    if (this.filterStatus === 'SISA_KEMARIN') return !!isFifo;
                    return true;
                }
            };
        };

        document.addEventListener('alpine:init', () => {
            if (typeof Alpine !== 'undefined' && typeof window.spkManager === 'function') {
                Alpine.data('spkManager', window.spkManager);
            }
        });
    </script>
</x-app-layout>
