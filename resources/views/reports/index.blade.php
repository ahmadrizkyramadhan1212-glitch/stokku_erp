<x-app-layout>
    @php
        $period = $period ?? 'today';
        $todayRevenue = (float) ($todayRevenue ?? 0);
        $todayPurchases = (float) ($todayPurchases ?? 0);
        $todayOperationalExpenses = (float) ($todayOperationalExpenses ?? 0);
        $todaySpoilage = (float) ($todaySpoilage ?? 0);
        $todayNetProfit = (float) ($todayNetProfit ?? 0);
        $monthRevenue = (float) ($monthRevenue ?? 0);
        $monthPurchases = (float) ($monthPurchases ?? 0);
        $monthHpp = (float) ($monthHpp ?? 0);
        $monthExpenses = (float) ($monthExpenses ?? (($monthOperationalExpenses ?? 0) + ($monthSpoilage ?? 0)));
        $monthNetProfit = (float) ($monthNetProfit ?? 0);
        $totalAssetValue = (float) ($totalAssetValue ?? 0);
        $avgMarginPct = (float) ($avgMarginPct ?? 20);
        $topSawItems = collect($topSawItems ?? []);
        $recentExpenses = collect($recentExpenses ?? []);
        $waCard = $waCard ?? [];

        // Perhitungan bar chart Pendapatan vs Pengeluaran untuk Hari Ini & Bulan Ini
        $todayOutflow = (float) ($todayPurchases + $todayOperationalExpenses + $todaySpoilage);
        $todayMaxBar = max(1, $todayRevenue, $todayOutflow);
        $todayRevPct = min(100, max(12, round(($todayRevenue / $todayMaxBar) * 100)));
        $todayOutPct = min(100, max(12, round(($todayOutflow / $todayMaxBar) * 100)));

        $monthOutflow = (float) ($monthPurchases + $monthExpenses);
        $monthMaxBar = max(1, $monthRevenue, $monthOutflow);
        $monthRevPct = min(100, max(12, round(($monthRevenue / $monthMaxBar) * 100)));
        $monthOutPct = min(100, max(12, round(($monthOutflow / $monthMaxBar) * 100)));
    @endphp

    <div x-data="{
            activeTab: @js($period === 'this_month' ? 'this_month' : 'today'),
            showExpenseModal: false
        }"
        class="flex flex-col gap-6 pb-10">

        <!-- ===================================================================
             FLASH MESSAGE
             =================================================================== -->
        @if(session('success'))
            <div class="bg-teal-50 dark:bg-slate-900 border border-slate-200 dark:border-teal-500/40 text-[#0F172A] dark:text-slate-100 px-4 py-3 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-teal-600 dark:text-teal-300">check_circle</span>
                    <span class="text-xs font-bold">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <!-- ===================================================================
             1. HEADER GRADASI GLOSSY TAJAM: LAPORAN USAHA & KEUANGAN WARUNG
             =================================================================== -->
        <section class="relative overflow-hidden rounded-3xl p-6 sm:p-6 bg-gradient-to-br from-[#0D9488] to-[#0F766E] text-white shadow-xl shadow-teal-500/30 border border-white/25">
            <!-- Dekorasi Lingkaran Cahaya Glossy -->
            <div class="pointer-events-none absolute -top-12 -right-12 w-44 h-44 rounded-full bg-white/15 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-14 -left-10 w-40 h-40 rounded-full bg-white/15 blur-2xl"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center text-white shrink-0 shadow-md">
                        <span class="material-symbols-outlined text-[26px]">bar_chart</span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded-full bg-white/20 backdrop-blur-md border border-white/30 text-white text-[10px] font-extrabold uppercase tracking-wider">
                                Laporan Usaha Warung Adhi Jaya
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-400/25 border border-emerald-300/40 text-white text-[10px] font-extrabold">
                                Margin Rata-rata {{ $avgMarginPct }}%
                            </span>
                        </div>
                        <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight">
                            Ringkasan Keuangan &amp; Urutan Prioritas Belanja Pasar
                        </h1>
                        <p class="text-xs text-white/90 mt-0.5 leading-relaxed max-w-2xl">
                            Pantau pemasukan kotor, modal belanja pasar, keuntungan bersih warung, serta daftar prioritas barang yang wajib dibeli esok pagi.
                        </p>
                    </div>
                </div>

                <!-- Pilihan Periode (Hari Ini / Bulan Ini) dengan Gaya Kaca Glossy -->
                <div class="flex items-center gap-2 bg-white/15 backdrop-blur-md p-1.5 rounded-full border border-white/30 self-start lg:self-center">
                    <button type="button"
                            @click="activeTab = 'today'"
                            :class="activeTab === 'today' ? 'bg-white text-teal-700 font-extrabold shadow-sm' : 'text-white hover:bg-white/10 font-semibold'"
                            class="px-4 py-1.5 rounded-full text-xs transition cursor-pointer">
                        Hari Ini
                    </button>
                    <button type="button"
                            @click="activeTab = 'this_month'"
                            :class="activeTab === 'this_month' ? 'bg-white text-teal-700 font-extrabold shadow-sm' : 'text-white hover:bg-white/10 font-semibold'"
                            class="px-4 py-1.5 rounded-full text-xs transition cursor-pointer">
                        Bulan Ini ({{ now()->translatedFormat('M Y') }})
                    </button>
                </div>
            </div>

            <!-- 3 Kartu Ringkasan Utama (Hari Ini) -->
            <div x-show="activeTab === 'today'" class="relative z-10 grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5">
                <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90">Omset Kotor (Hari Ini)</span>
                        <span class="material-symbols-outlined text-[18px] text-white">payments</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1">
                        Rp {{ number_format($todayRevenue, 0, ',', '.') }}
                    </p>
                    <span class="text-[11px] text-white/85 block mt-0.5">Total penjualan kotor dari barang terjual</span>
                </div>

                <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90">Modal Belanja Pasar (Hari Ini)</span>
                        <span class="material-symbols-outlined text-[18px] text-amber-200">shopping_bag</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1">
                        Rp {{ number_format($todayPurchases, 0, ',', '.') }}
                    </p>
                    <span class="text-[11px] text-white/85 block mt-0.5">Beban Ops &amp; Susut: Rp {{ number_format($todayOperationalExpenses + $todaySpoilage, 0, ',', '.') }}</span>
                </div>

                <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90">Omset Bersih / Laba Bersih (Hari Ini)</span>
                        <span class="material-symbols-outlined text-[18px] text-emerald-300">trending_up</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1">
                        Rp {{ number_format($todayNetProfit, 0, ',', '.') }}
                    </p>
                    <span class="text-[11px] text-white/85 block mt-0.5">Omset Kotor dikurangi Belanja Pasar &amp; Beban</span>
                </div>
            </div>

            <!-- 3 Kartu Ringkasan Utama (Bulan Ini) -->
            <div x-show="activeTab === 'this_month'" style="display: none;" class="relative z-10 grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5">
                <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90">Omset Kotor (Bulan Ini)</span>
                        <span class="material-symbols-outlined text-[18px] text-white">payments</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1">
                        Rp {{ number_format($monthRevenue, 0, ',', '.') }}
                    </p>
                    <span class="text-[11px] text-white/85 block mt-0.5">Akumulasi omzet kotor bulan {{ now()->translatedFormat('F Y') }}</span>
                </div>

                <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90">Modal Belanja Pasar (Bulan Ini)</span>
                        <span class="material-symbols-outlined text-[18px] text-amber-200">shopping_bag</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1">
                        Rp {{ number_format($monthPurchases, 0, ',', '.') }}
                    </p>
                    <span class="text-[11px] text-white/85 block mt-0.5">Beban Ops &amp; Susut: Rp {{ number_format($monthExpenses, 0, ',', '.') }}</span>
                </div>

                <div class="p-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-white/90">Omset Bersih / Laba Bersih (Bulan Ini)</span>
                        <span class="material-symbols-outlined text-[18px] text-emerald-300">trending_up</span>
                    </div>
                    <p class="text-xl sm:text-2xl font-extrabold text-white tabular-nums mt-1">
                        Rp {{ number_format($monthNetProfit, 0, ',', '.') }}
                    </p>
                    <span class="text-[11px] text-white/85 block mt-0.5">Nilai Stok Rak: Rp {{ number_format($totalAssetValue, 0, ',', '.') }}</span>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             2. GRAFIK BATANG MODERN (PENDAPATAN VS PENGELUARAN) & UNDUH PDF
             =================================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <!-- Grafik Batang Modern Biru-Indigo Menyala -->
            <section class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between gap-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-500/20 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[22px]">analytics</span>
                        </div>
                        <div>
                            <h2 class="text-sm sm:text-base font-extrabold text-[#0F172A] dark:text-slate-100">
                                Grafik Pendapatan vs Pengeluaran Warung
                            </h2>
                            <p class="text-xs text-slate-600 dark:text-slate-400" x-text="activeTab === 'today' ? 'Perbandingan uang masuk & belanja hari ini' : 'Perbandingan akumulasi bulan berjalan'"></p>
                        </div>
                    </div>
                </div>

                <!-- Bar Chart untuk Hari Ini -->
                <div x-show="activeTab === 'today'" class="space-y-4 py-2">
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-extrabold text-[#0F172A] dark:text-slate-100 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-teal-600"></span>
                                Pemasukan Kotor (Hari Ini)
                            </span>
                            <strong class="font-extrabold text-teal-600 dark:text-teal-300 tabular-nums">
                                Rp {{ number_format($todayRevenue, 0, ',', '.') }}
                            </strong>
                        </div>
                        <div class="w-full h-4 rounded-full bg-teal-50 dark:bg-slate-950 p-0.5 border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-[#0D9488] to-[#0F766E] transition-all duration-500"
                                 style="width: {{ $todayRevPct }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-extrabold text-[#0F172A] dark:text-slate-100 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                Total Pengeluaran &amp; Modal Belanja (Hari Ini)
                            </span>
                            <strong class="font-extrabold text-teal-600 dark:text-amber-400 tabular-nums">
                                Rp {{ number_format($todayOutflow, 0, ',', '.') }}
                            </strong>
                        </div>
                        <div class="w-full h-4 rounded-full bg-teal-50 dark:bg-slate-950 p-0.5 border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-500 transition-all duration-500"
                                 style="width: {{ $todayOutPct }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Bar Chart untuk Bulan Ini -->
                <div x-show="activeTab === 'this_month'" style="display: none;" class="space-y-4 py-2">
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-extrabold text-[#0F172A] dark:text-slate-100 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-teal-600"></span>
                                Pemasukan Kotor (Bulan Ini)
                            </span>
                            <strong class="font-extrabold text-teal-600 dark:text-teal-300 tabular-nums">
                                Rp {{ number_format($monthRevenue, 0, ',', '.') }}
                            </strong>
                        </div>
                        <div class="w-full h-4 rounded-full bg-teal-50 dark:bg-slate-950 p-0.5 border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-[#0D9488] to-[#0F766E] transition-all duration-500"
                                 style="width: {{ $monthRevPct }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-extrabold text-[#0F172A] dark:text-slate-100 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                Total Pengeluaran &amp; Modal Belanja (Bulan Ini)
                            </span>
                            <strong class="font-extrabold text-teal-600 dark:text-amber-400 tabular-nums">
                                Rp {{ number_format($monthOutflow, 0, ',', '.') }}
                            </strong>
                        </div>
                        <div class="w-full h-4 rounded-full bg-teal-50 dark:bg-slate-950 p-0.5 border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-500 transition-all duration-500"
                                 style="width: {{ $monthOutPct }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Cetak PDF Laporan Usaha & Daftar Stok -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-200 dark:border-slate-800">
                    <a :href="'{{ route('reports.finance') }}?period=' + activeTab"
                       class="py-3 px-4 rounded-2xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white font-extrabold text-xs shadow-md shadow-teal-500/30 flex items-center justify-center gap-2 transition active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">download</span>
                        <span>Unduh PDF Laporan Keuangan</span>
                    </a>
                    <a href="{{ route('reports.inventory') }}"
                       class="py-3 px-4 rounded-2xl bg-teal-50 dark:bg-slate-950 hover:bg-teal-100 text-teal-700 dark:text-teal-300 border border-slate-200 dark:border-slate-700 font-extrabold text-xs flex items-center justify-center gap-2 transition active:scale-95">
                        <span class="material-symbols-outlined text-[18px]">inventory</span>
                        <span>Unduh PDF Nilai Stok Rak</span>
                    </a>
                </div>
            </section>

            <!-- Rincian Kas Bersih & Catat Pengeluaran Operasional -->
            <section class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between gap-4">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <h3 class="text-sm sm:text-base font-extrabold text-[#0F172A] dark:text-slate-100">
                            Rincian Perhitungan Laba Warung
                        </h3>
                        <span class="px-2.5 py-0.5 rounded-full bg-teal-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-teal-600 dark:text-teal-300 text-[11px] font-extrabold"
                              x-text="activeTab === 'today' ? 'Hari Ini' : 'Bulan Ini'"></span>
                    </div>

                    <!-- Rincian Hari Ini -->
                    <div x-show="activeTab === 'today'" class="bg-slate-50 dark:bg-slate-950 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 space-y-2.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">Pemasukan Kotor Penjualan</span>
                            <strong class="text-[#0F172A] dark:text-slate-100 tabular-nums">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">Modal Belanja Pasar</span>
                            <strong class="text-rose-600 dark:text-rose-400 tabular-nums">- Rp {{ number_format($todayPurchases, 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">Biaya Operasional (Plastik, Es, Listrik)</span>
                            <strong class="text-rose-600 dark:text-rose-400 tabular-nums">- Rp {{ number_format($todayOperationalExpenses, 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">Susut Barang Layu / Rusak</span>
                            <strong class="text-rose-600 dark:text-rose-400 tabular-nums">- Rp {{ number_format($todaySpoilage, 0, ',', '.') }}</strong>
                        </div>
                        <div class="pt-2.5 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="font-extrabold text-[#0F172A] dark:text-slate-100">Keuntungan Bersih Hari Ini</span>
                            <strong class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                Rp {{ number_format($todayNetProfit, 0, ',', '.') }}
                            </strong>
                        </div>
                    </div>

                    <!-- Rincian Bulan Ini -->
                    <div x-show="activeTab === 'this_month'" style="display: none;" class="bg-slate-50 dark:bg-slate-950 rounded-2xl p-4 border border-slate-200 dark:border-slate-700 space-y-2.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">Pemasukan Kotor Bulan Ini</span>
                            <strong class="text-[#0F172A] dark:text-slate-100 tabular-nums">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">Modal Pokok Barang Terjual</span>
                            <strong class="text-rose-600 dark:text-rose-400 tabular-nums">- Rp {{ number_format($monthHpp, 0, ',', '.') }}</strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 dark:text-slate-400">Total Biaya Operasional &amp; Susut</span>
                            <strong class="text-rose-600 dark:text-rose-400 tabular-nums">- Rp {{ number_format($monthExpenses, 0, ',', '.') }}</strong>
                        </div>
                        <div class="pt-2.5 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <span class="font-extrabold text-[#0F172A] dark:text-slate-100">Keuntungan Bersih Bulan Ini</span>
                            <strong class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                Rp {{ number_format($monthNetProfit, 0, ',', '.') }}
                            </strong>
                        </div>
                    </div>
                </div>

                <button type="button"
                        @click="showExpenseModal = true"
                        class="w-full py-3 px-4 rounded-2xl bg-teal-50 dark:bg-slate-950 hover:bg-teal-100 text-teal-700 dark:text-teal-300 border border-slate-200 dark:border-slate-700 text-xs font-extrabold flex items-center justify-center gap-2 transition active:scale-95 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">add_circle</span>
                    <span>+ Catat Pengeluaran Operasional Warung</span>
                </button>
            </section>
        </div>

        <!-- ===================================================================
             3. DAFTAR URUTAN PRIORITAS BELANJA PASAR (BAHASA WARUNG BERSIH)
             =================================================================== -->
        <section class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-500/20 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">checklist</span>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-extrabold text-[#0F172A] dark:text-slate-100">
                            Urutan Prioritas Belanja Pasar Besok Pagi
                        </h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            Daftar barang prioritas tertinggi berdasarkan kondisi stok, kecepatan laku, dan saran jumlah beli
                        </p>
                    </div>
                </div>

                <a href="{{ route('spk.index') }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white text-xs font-extrabold shadow-md shadow-teal-500/30 self-start sm:self-center">
                    <span>Lihat Semua 96 Barang</span>
                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
            </div>

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
                        @foreach($topSawItems as $item)
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
                                $itemRank = $item->saw_rank ?? $item->rank ?? $item->peringkat ?? $loop->iteration;
                                $dailySpeed = (float) ($item->avg_daily_demand ?? (($item->sold_qty ?? 0) > 0 ? round(($item->sold_qty ?? 0) / 30, 1) : ($item->popularity_score ?? 3)));
                            @endphp
                            <tr class="hover:bg-teal-50/50 dark:hover:bg-teal-950/30 transition">
                                <td class="px-4 py-3 align-middle whitespace-nowrap">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-gradient-to-tr from-[#0D9488] to-[#0F766E] text-white font-extrabold text-xs tabular-nums shadow-xs">
                                        #{{ $itemRank }}
                                    </span>
                                </td>
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
                                        </div>
                                    </div>
                                </td>
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
                                <td class="px-4 py-3 align-middle whitespace-normal leading-relaxed font-bold text-teal-600 dark:text-teal-300">
                                    ± {{ number_format(max(1, $dailySpeed), 0, ',', '.') }} {{ $itemUnit }} / hari
                                </td>
                                <td class="px-4 py-3 text-right align-middle whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-xl bg-teal-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-teal-700 dark:text-teal-300 font-extrabold text-xs tabular-nums">
                                        Beli +{{ number_format($itemRestockQty, 0, ',', '.') }} {{ $itemUnit }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ===================================================================
             4. RIWAYAT PENGELUARAN OPERASIONAL WARUNG
             =================================================================== -->
        <section class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-teal-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-teal-600 dark:text-teal-300 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-extrabold text-[#0F172A] dark:text-slate-100">
                            Catatan Biaya Operasional &amp; Susut Barang
                        </h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            Pengeluaran plastik kresek, es batu, listrik, bensin pasar, dan susut barang
                        </p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 text-[11px] font-extrabold uppercase tracking-wider text-teal-600 dark:text-teal-300">
                            <th class="py-2.5 px-3">Tanggal</th>
                            <th class="py-2.5 px-3">Kategori</th>
                            <th class="py-2.5 px-3">Keterangan</th>
                            <th class="py-2.5 px-3 text-right">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-teal-100/60 dark:divide-teal-500/20 text-xs">
                        @forelse($recentExpenses as $exp)
                            <tr class="hover:bg-teal-50/40 dark:hover:bg-teal-950/30 transition">
                                <td class="py-2.5 px-3 font-semibold text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    {{ Carbon\Carbon::parse($exp->expense_date ?? $exp->created_at ?? now())->translatedFormat('d M Y') }}
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-teal-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-teal-700 dark:text-teal-300 text-[10px] font-extrabold">
                                        {{ $exp->category ?? 'Operasional' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-[#0F172A] dark:text-slate-100 font-medium">{{ $exp->description ?? '-' }}</td>
                                <td class="py-2.5 px-3 text-right font-extrabold text-rose-600 dark:text-rose-400 tabular-nums whitespace-nowrap">
                                    Rp {{ number_format((float) ($exp->amount ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-xs text-slate-400">
                                    Belum ada catatan biaya operasional.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ===================================================================
             5. KARTU LAPORAN TUTUP WARUNG KE WHATSAPP OWNER
             =================================================================== -->
        @include('partials.wa-report-card', ['waCard' => $waCard, 'variant' => 'reports'])

        <!-- ===================================================================
             MODAL CATAT PENGELUARAN OPERASIONAL
             =================================================================== -->
        <div x-show="showExpenseModal"
             style="display: none;"
             class="fixed inset-0 z-50 bg-black/65 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showExpenseModal = false"
                 class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 sm:p-6 shadow-2xl border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-teal-600 dark:text-teal-300">Biaya Operasional Warung</span>
                        <h3 class="text-base font-extrabold text-[#0F172A] dark:text-slate-100">Catat Pengeluaran Baru</h3>
                    </div>
                    <button type="button" @click="showExpenseModal = false" class="w-8 h-8 rounded-full bg-teal-50 dark:bg-slate-950 flex items-center justify-center text-slate-500 dark:text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <form action="{{ route('reports.store_expense') }}" method="POST" class="space-y-3.5 mt-4">
                    @csrf
                    <input type="hidden" name="expense_date" value="{{ now()->toDateString() }}">
                    <div>
                        <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Kategori Pengeluaran</label>
                        <select name="category" required
                                class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2.5 text-xs font-semibold text-[#0F172A] dark:text-slate-100">
                            <option value="Plastik & Kemasan">Plastik Kresek &amp; Kemasan</option>
                            <option value="Es Batu & Pendingin">Es Batu &amp; Pendingin</option>
                            <option value="Transportasi Pasar">Bensin / Ongkos Angkut Pasar</option>
                            <option value="Listrik & Air">Listrik, Air &amp; Kebersihan</option>
                            <option value="Operasional Lainnya">Operasional Warung Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Nominal Biaya (Rp)</label>
                        <input type="number" name="amount" min="500" step="500" placeholder="Contoh: 25000" required
                               class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2.5 text-sm font-extrabold text-[#0F172A] dark:text-slate-100" />
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Keterangan Singkat</label>
                        <input type="text" name="description" placeholder="Contoh: Beli 3 pak plastik kresek & es batu" required
                               class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2.5 text-xs font-semibold text-[#0F172A] dark:text-slate-100" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showExpenseModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-950 text-xs font-bold text-slate-600 dark:text-slate-400">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white text-xs font-extrabold shadow-md shadow-teal-500/30 cursor-pointer">
                            Simpan Pengeluaran
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
