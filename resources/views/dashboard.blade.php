<x-app-layout>
    @php
        $user = Auth::user();
        $isOwner = $user ? $user->isOwner() : false;
        $userName = $user?->name ?? 'Pengguna Adhi Jaya';
        $sawItems = collect($sawItems ?? []);
        $criticalItems = collect($criticalItems ?? []);
        $fifoItems = collect($fifoItems ?? []);
        $estimatedModal = (float) ($estimatedModal ?? 0);
        $kpi = $kpi ?? [
            'period_label' => 'Hari Ini',
            'current_period' => 'today',
            'revenue' => 0,
            'pengeluaran' => 0,
            'profit' => 0,
            'margin_percent' => 0,
        ];
        $topSaw = $sawItems->first();
        $habisCount = $sawItems->where('stock_status', 'habis')->count();
        $menipisCount = $sawItems->where('stock_status', 'menipis')->count();

        $categoryGroupsMeta = [
            'Sayur & Buah Segar' => [
                'code'  => 'A',
                'tab'   => 'Sayur & Buah',
                'title' => 'Sayur & Buah Segar',
                'desc'  => 'Komoditas segar harian warung',
                'icon'  => 'eco',
            ],
            'Bumbu Dapur & Rempah' => [
                'code'  => 'B',
                'tab'   => 'Bumbu Dapur',
                'title' => 'Bumbu Dapur & Rempah',
                'desc'  => 'Bawang, cabai, rimpang, daun rempah & bumbu kemasan',
                'icon'  => 'nutrition',
            ],
            'Minyak, Saus & Kecap' => [
                'code'  => 'C',
                'tab'   => 'Minyak & Kecap',
                'title' => 'Minyak Goreng, Saus & Kecap',
                'desc'  => 'Minyak goreng pouch/botol, saus, kecap & cuka',
                'icon'  => 'water_drop',
            ],
            'Sembako & Bahan Pokok' => [
                'code'  => 'D',
                'tab'   => 'Sembako & Mie',
                'title' => 'Sembako, Tepung & Bahan Pokok',
                'desc'  => 'Beras, telur, mie, tepung, gula & penyedap',
                'icon'  => 'grain',
            ],
            'Lauk, Minuman & Rumah Tangga' => [
                'code'  => 'E',
                'tab'   => 'Lainnya',
                'title' => 'Lauk, Minuman, Es & Rumah Tangga',
                'desc'  => 'Ikan asin, frozen, air galon/dus, es batu, sabun & LPG',
                'icon'  => 'inventory_2',
            ],
        ];

        $waLines = [
            "*LAPORAN TUTUP WARUNG - ADHI JAYA*",
            "Warung Sembako & Sayur Adhi Jaya • " . now()->translatedFormat('d F Y (H:i)') . " WIB",
            "",
        ];
        if ($isOwner) {
            $waLines[] = "*Ringkasan Keuangan (" . ($kpi['period_label'] ?? 'Hari Ini') . "):*";
            $waLines[] = "• Total Omzet Warung: Rp " . number_format((float) ($kpi['revenue'] ?? 0), 0, ',', '.');
            $waLines[] = "• Kulakan Pasar: Rp " . number_format((float) ($kpi['pengeluaran'] ?? 0), 0, ',', '.');
            $waLines[] = "• Untung Bersih Warung: Rp " . number_format((float) ($kpi['profit'] ?? 0), 0, ',', '.');
            $waLines[] = "";
        }
        $waLines[] = "*Daftar Prioritas Belanja Besok:*";
        foreach ($sawItems->take(5) as $idx => $wItem) {
            $wName = $wItem->name ?? $wItem->nama_barang ?? '-';
            $wStatus = strtoupper((string) ($wItem->stock_status ?? $wItem->status ?? 'AMAN'));
            $wQty = $wItem->recommended_restock_qty ?? 0;
            $wUnit = $wItem->unit ?? 'Kg';
            $waLines[] = ($idx + 1) . ". {$wName} ({$wStatus}) -> Beli {$wQty} {$wUnit}";
        }
        $waUrl = 'https://wa.me/6281234567890?text=' . rawurlencode(implode("\n", $waLines));
    @endphp

    <div x-data="{
            hideBalance: false,
            isDark: document.documentElement.classList.contains('dark'),
            searchQuery: '',
            selectedCategory: 'ALL',
            openSections: {
                'Sayur & Buah Segar': true,
                'Bumbu Dapur & Rempah': false,
                'Minyak, Saus & Kecap': false,
                'Sembako & Bahan Pokok': false,
                'Lauk, Minuman & Rumah Tangga': false
            },
            toggleSection(cat) {
                this.openSections[cat] = !this.openSections[cat];
            },
            isSectionOpen(cat) {
                if (this.searchQuery.trim() !== '') return true;
                if (this.selectedCategory !== 'ALL' && this.selectedCategory !== 'KRITIS') {
                    return this.selectedCategory === cat;
                }
                if (this.selectedCategory === 'KRITIS') return true;
                return !!this.openSections[cat];
            },
            expandAll(state) {
                Object.keys(this.openSections).forEach(k => this.openSections[k] = state);
            },
            matchesItem(name, kategori, status, isFifo, unit) {
                if (this.selectedCategory === 'KRITIS') {
                    if (status !== 'habis' && status !== 'menipis' && !isFifo) return false;
                } else if (this.selectedCategory !== 'ALL') {
                    if (kategori !== this.selectedCategory) return false;
                }
                const q = this.searchQuery.trim().toLowerCase();
                if (!q) return true;
                return String(name).toLowerCase().includes(q)
                    || String(kategori).toLowerCase().includes(q)
                    || String(unit).toLowerCase().includes(q);
            }
        }"
        @stokku-theme-changed.window="isDark = $event.detail.dark"
        class="flex flex-col w-full gap-6 pb-8">

        {{-- ===================================================================
             1. BLOK HEADER ATAS HIJAU TEAL WARUNG (#0D9488 - #0F766E) + KARTU UTAMA
             Menyatu penuh dari atas membungkus baris STOKKU ERP + ROLE hingga belakang Kartu Utama
             Non-sticky (relative) agar ikut tergulung naik saat layar di-scroll ke bawah
             =================================================================== --}}
        <section class="relative -mx-4 sm:-mx-5 md:mx-0 -mt-3 md:mt-0 px-4 sm:px-5 md:p-6 pt-4 pb-6 rounded-b-[2.25rem] md:rounded-3xl bg-gradient-to-b from-[#0D9488] to-[#0F766E] shadow-lg shadow-teal-900/15">
            <!-- Baris Atas Header Hijau: Logo STOKKU ERP + Badge OWNER (Kiri) & Tombol Ikon Bulat (Kanan) -->
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-full bg-white/15 border border-white/20 flex items-center justify-center text-white shrink-0">
                        <span class="material-symbols-outlined text-[21px]">storefront</span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap min-w-0">
                        <span class="text-base sm:text-lg font-extrabold text-white tracking-tight truncate">
                            STOKKU ERP
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full bg-white/20 border border-white/25 text-white text-[10px] font-extrabold uppercase tracking-wider">
                            {{ $isOwner ? 'OWNER' : 'STAF' }}
                        </span>
                    </div>
                </div>

                <!-- Tombol Ikon Bulat Kanan Atas (Notifikasi, Mode Gelap/Terang, Logout) -->
                <div class="flex items-center gap-2 shrink-0">
                    <!-- Tombol Toggle Mode Gelap / Terang -->
                    <button type="button"
                            @click="isDark = window.toggleStokkuTheme()"
                            aria-label="Ubah Mode Gelap atau Terang"
                            title="Ubah Mode Gelap / Terang"
                            class="w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 border border-white/20 text-white flex items-center justify-center transition active:scale-95 cursor-pointer">
                        <span class="material-symbols-outlined text-[20px]" x-text="isDark ? 'light_mode' : 'dark_mode'">dark_mode</span>
                    </button>

                    <!-- Tombol Lonceng Notifikasi Stok Kritis -->
                    <a href="#daftar-belanja"
                       aria-label="Notifikasi Belanja"
                       title="Lihat Prioritas Belanja"
                       class="relative w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 border border-white/20 text-white flex items-center justify-center transition active:scale-95">
                        <span class="material-symbols-outlined text-[20px]">notifications</span>
                        @if(($habisCount + $menipisCount) > 0)
                            <span class="absolute top-2 right-2 w-2.5 h-2.5 rounded-full bg-rose-500 ring-2 ring-[#0D9488]"></span>
                        @endif
                    </a>

                    <!-- Tombol Keluar Sesi -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                aria-label="Keluar"
                                title="Keluar Aplikasi"
                                class="w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 border border-white/20 text-white flex items-center justify-center transition active:scale-95 cursor-pointer">
                            <span class="material-symbols-outlined text-[20px]">logout</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- KARTU UTAMA MENGAMBANG DI ATAS LATAR HIJAU -->
            @if($isOwner)
                {{-- KARTU UTAMA OWNER: TOTAL OMZET & LABA BERSIH --}}
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 shadow-lg border border-slate-200 dark:border-slate-700 transition-colors duration-200">
                    {{-- Baris Atas Kartu: Ikon Dompet Hijau Kecil + TOTAL OMZET WARUNG + Ikon Mata (Kiri) & Dropdown Hari Ini v (Kanan) --}}
                    <div class="flex items-center justify-between gap-1.5">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="w-6 h-6 rounded-lg bg-teal-50 dark:bg-teal-950/80 text-[#0D9488] dark:text-teal-300 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[15px]">account_balance_wallet</span>
                            </span>
                            <span class="text-slate-600 dark:text-slate-400 font-bold text-[11px] sm:text-xs tracking-tight uppercase whitespace-nowrap">
                                TOTAL OMZET WARUNG
                            </span>
                            <button type="button"
                                    @click="hideBalance = !hideBalance"
                                    class="text-slate-400 hover:text-[#0D9488] dark:hover:text-teal-300 flex items-center justify-center transition cursor-pointer shrink-0"
                                    title="Sembunyikan atau Tampilkan Nominal">
                                <span class="material-symbols-outlined text-[16px]" x-text="hideBalance ? 'visibility_off' : 'visibility'">visibility</span>
                            </button>
                        </div>

                        <!-- Dropdown Kapsul Periode (Hari Ini v) -->
                        <div x-data="{ openPeriod: false }" class="relative shrink-0">
                            <button @click="openPeriod = !openPeriod"
                                    type="button"
                                    class="bg-teal-50 dark:bg-teal-950/80 hover:bg-teal-100 text-teal-800 dark:text-teal-200 border border-slate-200 dark:border-slate-700 rounded-full px-2.5 py-1 text-[11px] font-semibold inline-flex items-center gap-1 transition cursor-pointer whitespace-nowrap">
                                <span class="material-symbols-outlined text-[13px] text-[#0D9488] dark:text-teal-300">calendar_today</span>
                                <span>{{ $kpi['period_label'] ?? 'Hari Ini' }}</span>
                                <span class="material-symbols-outlined text-[14px]">expand_more</span>
                            </button>
                            <div x-show="openPeriod"
                                 @click.away="openPeriod = false"
                                 style="display: none;"
                                 class="absolute right-0 mt-1.5 w-36 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 z-50 overflow-hidden py-1">
                                <a href="?period=today" class="block px-3.5 py-2 text-xs font-extrabold {{ ($kpi['current_period'] ?? 'today') === 'today' ? 'bg-[#0D9488] text-white' : 'hover:bg-teal-50 dark:hover:bg-teal-950/60' }}">Hari Ini</a>
                                <a href="?period=yesterday" class="block px-3.5 py-2 text-xs font-extrabold {{ ($kpi['current_period'] ?? 'today') === 'yesterday' ? 'bg-[#0D9488] text-white' : 'hover:bg-teal-50 dark:hover:bg-teal-950/60' }}">Kemarin</a>
                                <a href="?period=this_week" class="block px-3.5 py-2 text-xs font-extrabold {{ ($kpi['current_period'] ?? 'today') === 'this_week' ? 'bg-[#0D9488] text-white' : 'hover:bg-teal-50 dark:hover:bg-teal-950/60' }}">Minggu Ini</a>
                                <a href="?period=this_month" class="block px-3.5 py-2 text-xs font-extrabold {{ ($kpi['current_period'] ?? 'today') === 'this_month' ? 'bg-[#0D9488] text-white' : 'hover:bg-teal-50 dark:hover:bg-teal-950/60' }}">Bulan Ini</a>
                            </div>
                        </div>
                    </div>

                    <!-- Baris Tengah Kartu: Angka Rp Besar + Badge Kapsul Hijau Muda Margin ...% -->
                    <div class="mt-3 flex items-center gap-3 flex-wrap">
                        <span x-show="!hideBalance" class="text-slate-900 dark:text-slate-100 text-3xl sm:text-4xl font-extrabold tracking-tight tabular-nums">
                            Rp {{ number_format((float) ($kpi['revenue'] ?? 0), 0, ',', '.') }}
                        </span>
                        <span x-show="hideBalance" style="display: none;" class="text-slate-900 dark:text-slate-100 text-3xl sm:text-4xl font-extrabold tracking-widest">
                            Rp ••••••••
                        </span>

                        <span class="bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-500/30 text-xs font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/>
                                <polyline points="16 7 22 7 22 13"/>
                            </svg>
                            <span>Margin {{ $kpi['margin_percent'] ?? 0 }}%</span>
                        </span>
                    </div>

                    <!-- Baris Sub-info: ● Untung Bersih: Rp ... (Hijau) & ● Kulakan Pasar: Rp ... (Merah/Amber) -->
                    <div class="mt-2.5 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs">
                        <span class="inline-flex items-center gap-1.5 text-slate-600 dark:text-slate-400 font-medium">
                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                            <span>Untung Bersih:</span>
                            <strong class="text-emerald-700 dark:text-emerald-400 font-extrabold tabular-nums" x-text="hideBalance ? 'Rp ••••••' : 'Rp {{ number_format((float) ($kpi['profit'] ?? 0), 0, ',', '.') }}'">
                                Rp {{ number_format((float) ($kpi['profit'] ?? 0), 0, ',', '.') }}
                            </strong>
                        </span>

                        <span class="inline-flex items-center gap-1.5 text-slate-600 dark:text-slate-400 font-medium">
                            <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                            <span>Kulakan Pasar:</span>
                            <strong class="text-rose-700 dark:text-rose-400 font-extrabold tabular-nums" x-text="hideBalance ? 'Rp ••••••' : 'Rp {{ number_format((float) ($kpi['pengeluaran'] ?? 0), 0, ',', '.') }}'">
                                Rp {{ number_format((float) ($kpi['pengeluaran'] ?? 0), 0, ',', '.') }}
                            </strong>
                        </span>
                    </div>

                    <!-- Baris Bawah di Dalam Kartu: 2 Tombol Besar Sejajar Tanpa Terpotong (...) -->
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <!-- 1) Tombol Kiri (Solid Teal): Top Up Stok / Catat Kulakan -->
                        <a href="{{ route('purchases.index') }}"
                           class="bg-[#0D9488] hover:bg-[#0F766E] text-white rounded-2xl px-3 py-2.5 flex items-center gap-3 shadow-sm transition active:scale-95">
                            <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-white shrink-0">
                                <span class="material-symbols-outlined text-[18px]">add_card</span>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[11px] font-medium text-teal-100 leading-tight block whitespace-nowrap">Top Up Stok</span>
                                <span class="text-xs font-bold text-white leading-tight block whitespace-nowrap">Catat Kulakan</span>
                            </div>
                        </a>

                        <!-- 2) Tombol Kanan (Soft Tint): Rekap & PDF / Tarik Laporan -->
                        <a href="{{ route('reports.index') }}"
                           class="bg-sky-50 hover:bg-sky-100 dark:bg-sky-950/40 dark:hover:bg-sky-950/70 text-slate-800 dark:text-slate-100 border border-sky-200/60 dark:border-sky-500/30 rounded-2xl px-3 py-2.5 flex items-center gap-3 transition active:scale-95">
                            <div class="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                <span class="material-symbols-outlined text-[18px]">monitoring</span>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[11px] font-medium text-blue-600 dark:text-sky-300 leading-tight block whitespace-nowrap">Rekap &amp; PDF</span>
                                <span class="text-xs font-bold text-blue-950 dark:text-slate-100 leading-tight block whitespace-nowrap">Tarik Laporan</span>
                            </div>
                        </a>
                    </div>
                </div>
            @else
                <!-- KARTU UTAMA STAF GUDANG: RINGKASAN TUGAS & STOK HARIAN (Tanpa Angka Finansial) -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-5 shadow-lg border border-slate-200 dark:border-slate-700 transition-colors duration-200">
                    <div class="flex items-center justify-between gap-1.5 pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-950/80 text-[#0D9488] dark:text-teal-300 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[17px]">inventory_2</span>
                            </span>
                            <span class="text-slate-700 dark:text-slate-300 font-extrabold text-xs sm:text-sm tracking-tight uppercase whitespace-nowrap">
                                RINGKASAN TUGAS &amp; STOK HARIAN
                            </span>
                        </div>
                        <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 tabular-nums">
                            {{ now()->translatedFormat('d M Y') }}
                        </span>
                    </div>

                    <!-- 3 Indikator Kritis: HABIS (Merah), MENIPIS (Kuning), Sisa Kemarin FIFO (Oranye) -->
                    <div class="mt-4 grid grid-cols-3 gap-2 sm:gap-3">
                        <a href="{{ route('inventory.materials') }}" class="p-3 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 flex flex-col items-center text-center transition hover:bg-rose-100/70">
                            <span class="text-2xl sm:text-3xl font-extrabold text-rose-600 dark:text-rose-400 tabular-nums">
                                {{ $habisCount }}
                            </span>
                            <span class="text-[10px] sm:text-[11px] font-extrabold text-rose-700 dark:text-rose-300 uppercase tracking-wider mt-1">
                                Stok Habis
                            </span>
                            <span class="text-[9px] text-rose-500/80 hidden sm:block">Perlu kulakan</span>
                        </a>

                        <a href="{{ route('inventory.materials') }}" class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 flex flex-col items-center text-center transition hover:bg-amber-100/70">
                            <span class="text-2xl sm:text-3xl font-extrabold text-amber-600 dark:text-amber-400 tabular-nums">
                                {{ $menipisCount }}
                            </span>
                            <span class="text-[10px] sm:text-[11px] font-extrabold text-amber-700 dark:text-amber-300 uppercase tracking-wider mt-1">
                                Stok Menipis
                            </span>
                            <span class="text-[9px] text-amber-500/80 hidden sm:block">Batas aman</span>
                        </a>

                        <a href="{{ route('inventory.materials') }}" class="p-3 rounded-2xl bg-orange-50 dark:bg-orange-950/40 border border-orange-200 dark:border-orange-800/60 flex flex-col items-center text-center transition hover:bg-orange-100/70">
                            <span class="text-2xl sm:text-3xl font-extrabold text-orange-600 dark:text-orange-400 tabular-nums">
                                {{ $fifoItems->count() }}
                            </span>
                            <span class="text-[10px] sm:text-[11px] font-extrabold text-orange-700 dark:text-orange-300 uppercase tracking-wider mt-1">
                                Sisa Kemarin (FIFO)
                            </span>
                            <span class="text-[9px] text-orange-500/80 hidden sm:block">Jual duluan</span>
                        </a>
                    </div>

                    <!-- Baris Bawah: 2 Tombol Aksi Operasional Staf -->
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <a href="{{ route('inventory.materials') }}"
                           class="bg-[#0D9488] hover:bg-[#0F766E] text-white rounded-2xl px-3 py-2.5 flex items-center gap-3 shadow-sm transition active:scale-95">
                            <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-white shrink-0">
                                <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[11px] font-medium text-teal-100 leading-tight block whitespace-nowrap">Cek Rak Warung</span>
                                <span class="text-xs font-bold text-white leading-tight block whitespace-nowrap">Cek Stok Rak</span>
                            </div>
                        </a>

                        <a href="{{ route('purchases.index') }}"
                           class="bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-950/70 text-emerald-900 dark:text-emerald-100 border border-emerald-200 dark:border-emerald-700 rounded-2xl px-3 py-2.5 flex items-center gap-3 transition active:scale-95">
                            <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[11px] font-medium text-emerald-700 dark:text-emerald-300 leading-tight block whitespace-nowrap">Kulakan Pasar</span>
                                <span class="text-xs font-bold text-emerald-950 dark:text-slate-100 leading-tight block whitespace-nowrap">Catat Barang Masuk / Struk</span>
                            </div>
                        </a>
                    </div>
                </div>
            @endif
        </section>

        <!-- ===================================================================
             2. MENU CEPAT / QUICK ACTIONS (4 TOMBOL GAYA MINIMALIST SQUIRCLE)
             Ikon Seragam 100% dengan Navigasi:
             1. Cek Stok (Kotak Stok)
             2. Belanja (Tas Belanja)
             3. Prioritas (Checklist Ranking Prioritas)
             4. Owner: Laporan (Grafik Batang) / Staf: Barang Keluar POS (Kasir/Keranjang)
             =================================================================== -->
        <section aria-label="Menu Cepat" class="py-1">
            <div class="grid grid-cols-4 gap-3 sm:gap-4">
                <!-- 1. Cek Stok (Ikon Kotak Inventori/Stok Seragam Navigasi) -->
                <a href="{{ route('inventory.materials') }}"
                   class="flex flex-col items-center gap-2 group">
                    <div class="w-16 h-16 rounded-[20px] bg-[#F1F5F4] dark:bg-teal-950/50 group-hover:bg-teal-50 dark:group-hover:bg-teal-900/40 transition-all flex items-center justify-center shadow-[0_2px_8px_rgba(0,0,0,0.03)] group-active:scale-95">
                        <svg class="w-6 h-6 text-slate-800 dark:text-teal-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                            <path d="m3.3 7 8.7 5 8.7-5"/>
                            <path d="M12 22V12"/>
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-400 tracking-tight">Cek Stok</span>
                </a>

                <!-- 2. Belanja (Ikon Tas Belanja Seragam Navigasi) -->
                <a href="{{ route('purchases.index') }}"
                   class="flex flex-col items-center gap-2 group">
                    <div class="w-16 h-16 rounded-[20px] bg-[#F1F5F4] dark:bg-teal-950/50 group-hover:bg-teal-50 dark:group-hover:bg-teal-900/40 transition-all flex items-center justify-center shadow-[0_2px_8px_rgba(0,0,0,0.03)] group-active:scale-95">
                        <svg class="w-6 h-6 text-slate-800 dark:text-teal-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>
                            <path d="M3 6h18"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-400 tracking-tight">Belanja</span>
                </a>

                <!-- 3. Prioritas (Ikon Checklist / Ranking Prioritas Seragam Navigasi) -->
                <a href="{{ route('spk.index') }}"
                   class="flex flex-col items-center gap-2 group">
                    <div class="w-16 h-16 rounded-[20px] bg-[#F1F5F4] dark:bg-teal-950/50 group-hover:bg-teal-50 dark:group-hover:bg-teal-900/40 transition-all flex items-center justify-center shadow-[0_2px_8px_rgba(0,0,0,0.03)] group-active:scale-95">
                        <svg class="w-6 h-6 text-slate-800 dark:text-teal-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 6h11"/>
                            <path d="M9 12h11"/>
                            <path d="M9 18h11"/>
                            <path d="m3 6 1 1 2-2"/>
                            <path d="m3 12 1 1 2-2"/>
                            <path d="m3 18 1 1 2-2"/>
                        </svg>
                    </div>
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-400 tracking-tight">Prioritas</span>
                </a>

                <!-- 4. Tombol ke-4: Owner = Laporan (Grafik Batang) / Staf = Barang Keluar POS (Kasir/Keranjang) -->
                @if($isOwner)
                    <a href="{{ route('reports.index') }}"
                       class="flex flex-col items-center gap-2 group">
                        <div class="w-16 h-16 rounded-[20px] bg-[#F1F5F4] dark:bg-teal-950/50 group-hover:bg-teal-50 dark:group-hover:bg-teal-900/40 transition-all flex items-center justify-center shadow-[0_2px_8px_rgba(0,0,0,0.03)] group-active:scale-95">
                            <svg class="w-6 h-6 text-slate-800 dark:text-teal-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 3v18h18"/>
                                <path d="M7 16v-5"/>
                                <path d="M12 16V8"/>
                                <path d="M17 16v-3"/>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-400 tracking-tight">Laporan</span>
                    </a>
                @else
                    <a href="{{ route('pos.index') }}"
                       class="flex flex-col items-center gap-2 group">
                        <div class="w-16 h-16 rounded-[20px] bg-[#F1F5F4] dark:bg-teal-950/50 group-hover:bg-teal-50 dark:group-hover:bg-teal-900/40 transition-all flex items-center justify-center shadow-[0_2px_8px_rgba(0,0,0,0.03)] group-active:scale-95">
                            <svg class="w-6 h-6 text-slate-800 dark:text-teal-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="8" cy="21" r="1"/>
                                <circle cx="19" cy="21" r="1"/>
                                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-400 tracking-tight">Barang Keluar</span>
                    </a>
                @endif
            </div>
        </section>

        <!-- ===================================================================
             3. DAFTAR PRIORITAS BELANJA BESOK & STOK SISA KEMARIN (JUAL DULUAN)
             Bebas 100% dari istilah akademis
             =================================================================== -->
        <section id="daftar-belanja" class="flex flex-col gap-3">
            <div class="flex items-center justify-between px-0.5">
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-slate-100 flex items-center gap-2 flex-wrap">
                        <span>Daftar Prioritas Belanja Besok</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white text-[10px] font-extrabold shadow-xs">
                            Prioritas Utama
                        </span>
                    </h2>
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Saran kulakan pasar esok pagi &amp; pengingat Stok Sisa Kemarin (Jual Duluan)
                    </p>
                </div>
                <a href="{{ route('spk.index') }}" class="text-xs font-extrabold text-[#0D9488] dark:text-teal-300 hover:underline flex items-center gap-1 shrink-0">
                    <span>Lihat Semua</span>
                    <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- KARTU 1: DAFTAR PRIORITAS BELANJA BESOK (#1) -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between gap-4">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-200 border border-rose-200 dark:border-rose-500/30 text-[11px] font-extrabold">
                                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                Barang #1 Wajib Dibeli Besok
                            </span>
                            <span class="px-2.5 py-1 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white text-xs font-extrabold shadow-xs">
                                Prioritas #1
                            </span>
                        </div>

                        @if($topSaw)
                            @php
                                $topName = $topSaw->name ?? $topSaw->nama_barang ?? '-';
                                $topKat = $topSaw->kategori ?? $topSaw->category ?? '-';
                                $topStatus = strtolower((string) ($topSaw->stock_status ?? $topSaw->status ?? 'aman'));
                                $topStock = (float) ($topSaw->current_stock ?? 0);
                                $topUnit = $topSaw->unit ?? 'Kg';
                                $topRestock = (float) ($topSaw->recommended_restock_qty ?? 0);
                                $topEstCost = (float) ($topSaw->estimated_restock_cost ?? $topSaw->estimated_cost ?? 0);
                                $topImg = $topSaw->image_url ?? asset('images/commodities/default-commodity.svg');
                            @endphp
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                                <div class="flex items-center gap-3 min-w-0">
                                    <img src="{{ $topImg }}"
                                         alt="{{ $topName }}"
                                         loading="lazy"
                                         onerror="this.onerror=null;this.src='{{ asset('images/commodities/default-commodity.svg') }}';"
                                         class="w-14 h-14 sm:w-16 sm:h-16 shrink-0 rounded-xl overflow-hidden object-cover object-center border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 block" />
                                    <div class="min-w-0 flex-1">
                                        <span class="text-[11px] font-extrabold text-[#0D9488] dark:text-teal-300 uppercase tracking-wider block truncate">{{ $topKat }}</span>
                                        <h3 class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-slate-100 mt-0.5 leading-snug break-words">{{ $topName }}</h3>
                                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                                            Kondisi Rak:
                                            <strong class="{{ $topStatus === 'habis' ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400' }}">
                                                {{ strtoupper($topStatus) }} ({{ number_format($topStock, $topStock == (int) $topStock ? 0 : 1, ',', '.') }} {{ $topUnit }})
                                            </strong>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex sm:flex-col items-center sm:items-end justify-between bg-teal-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-2xl px-3.5 py-2 shrink-0">
                                    <span class="text-[10px] font-extrabold uppercase text-[#0D9488] dark:text-teal-300 block">Saran Kulakan</span>
                                    <div class="text-right">
                                        <span class="text-base font-extrabold text-slate-900 dark:text-slate-100 tabular-nums">
                                            {{ number_format($topRestock, 0, ',', '.') }} {{ $topUnit }}
                                        </span>
                                        @if($isOwner)
                                            <span class="block text-[11px] font-extrabold text-[#0D9488] dark:text-teal-300 tabular-nums">
                                                ± Rp {{ number_format($topEstCost, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 flex items-start gap-3">
                                <span class="material-symbols-outlined text-[19px] text-[#0D9488] dark:text-teal-300 shrink-0 mt-0.5">lightbulb</span>
                                <p class="text-xs text-slate-800 dark:text-slate-300 leading-relaxed">
                                    <strong class="text-[#0D9488] dark:text-teal-300">Saran Warung:</strong>
                                    Segera masukkan {{ $topName }} ke catatan belanja esok pagi sebanyak {{ number_format($topRestock, 0, ',', '.') }} {{ $topUnit }} agar pelanggan tidak kecewa.
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                        <div class="text-xs text-slate-600 dark:text-slate-400">
                            Estimasi Modal ({{ $criticalItems->count() }} Barang):
                            @if($isOwner)
                                <strong class="text-slate-900 dark:text-slate-100">Rp {{ number_format($estimatedModal, 0, ',', '.') }}</strong>
                            @else
                                <strong class="text-slate-900 dark:text-slate-100">{{ $criticalItems->count() }} Barang</strong>
                            @endif
                        </div>
                        <a href="{{ route('spk.index') }}"
                           class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white text-xs font-extrabold inline-flex items-center gap-1 transition active:scale-95 shrink-0 shadow-md shadow-teal-600/25">
                            <span>Daftar Prioritas Belanja Besok</span>
                            <span class="material-symbols-outlined text-[15px]">chevron_right</span>
                        </a>
                    </div>
                </div>

                <!-- KARTU 2: STOK SISA KEMARIN (JUAL DULUAN) & REKAP WA -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between gap-4">
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-950/70 text-amber-900 dark:text-amber-300 border border-amber-200 dark:border-amber-500/30 text-[11px] font-extrabold">
                                <span class="material-symbols-outlined text-[15px] text-amber-600 dark:text-amber-400">priority_high</span>
                                Stok Sisa Kemarin (Jual Duluan)
                            </span>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 text-[11px] font-extrabold">
                                WA 23:00 Siap
                            </span>
                        </div>

                        <!-- 3 Mini Counters: Habis, Menipis, Stok Sisa Kemarin -->
                        <div class="grid grid-cols-3 gap-3">
                            <button type="button" @click="selectedCategory = 'KRITIS'"
                                     class="p-3 rounded-2xl bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 border border-rose-200 dark:border-rose-500/30 text-left transition cursor-pointer">
                                <span class="text-[10px] font-extrabold uppercase text-rose-800 dark:text-rose-300 block">Stok Habis</span>
                                <span class="text-xl font-extrabold text-rose-600 dark:text-rose-400 tabular-nums">{{ $habisCount }}</span>
                                <span class="text-[10px] text-rose-700 dark:text-rose-300 block">Barang</span>
                            </button>
                            <button type="button" @click="selectedCategory = 'KRITIS'"
                                     class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/40 hover:bg-amber-100 border border-amber-200 dark:border-amber-500/30 text-left transition cursor-pointer">
                                <span class="text-[10px] font-extrabold uppercase text-amber-900 dark:text-amber-300 block">Stok Menipis</span>
                                <span class="text-xl font-extrabold text-amber-600 dark:text-amber-400 tabular-nums">{{ $menipisCount }}</span>
                                <span class="text-[10px] text-amber-800 dark:text-amber-300 block">Barang</span>
                            </button>
                            <button type="button" @click="selectedCategory = 'KRITIS'"
                                     class="p-3 rounded-2xl bg-teal-50 dark:bg-teal-950/50 hover:bg-teal-100 border border-slate-200 dark:border-slate-700 text-left transition cursor-pointer">
                                <span class="text-[10px] font-extrabold uppercase text-teal-800 dark:text-teal-300 block">Jual Duluan</span>
                                <span class="text-xl font-extrabold text-[#0D9488] dark:text-teal-300 tabular-nums">{{ $fifoItems->count() }}</span>
                                <span class="text-[10px] text-teal-700 dark:text-teal-300 block">Sisa Kemarin</span>
                            </button>
                        </div>

                        <!-- Preview Daftar Stok Sisa Kemarin -->
                        <div class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-500/30 text-xs text-amber-950 dark:text-amber-200">
                            <div class="font-extrabold flex items-center gap-1.5 mb-1">
                                <span class="material-symbols-outlined text-[16px] text-amber-600 dark:text-amber-400">warning</span>
                                <span>Sisa Kemarin - Jual Duluan:</span>
                            </div>
                            <p class="text-[11px] text-amber-900 dark:text-amber-300/90 leading-relaxed">
                                {{ $fifoItems->pluck('name')->implode(', ') ?: 'Semua stok segar baru' }} — taruh di rak paling depan agar laku lebih dulu sebelum membuka stok baru.
                            </p>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center gap-3">
                        <a href="{{ $waUrl }}" target="_blank" rel="noopener"
                           class="flex-1 py-2.5 px-3.5 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white text-xs font-extrabold flex items-center justify-center gap-1.5 shadow-md shadow-teal-600/25 transition active:scale-95">
                            <span class="material-symbols-outlined text-[17px]">chat</span>
                            <span>Kirim Laporan WA</span>
                        </a>
                        <a href="{{ route('inventory.materials') }}"
                           class="py-2.5 px-3.5 rounded-xl bg-teal-50 dark:bg-slate-950 hover:bg-teal-100 text-[#0F766E] dark:text-teal-300 border border-slate-200 dark:border-slate-700 text-xs font-extrabold flex items-center justify-center gap-1.5 transition active:scale-95">
                            <span class="material-symbols-outlined text-[17px]">inventory_2</span>
                            <span>Cek Stok</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             4. DAFTAR STOK KOMODITAS WARUNG PER KATEGORI
             =================================================================== -->
        <section class="flex flex-col gap-3">
            <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-3xl shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 flex flex-col gap-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-600/20 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                        </span>
                        <div>
                            <h2 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-slate-100">
                                Daftar Stok Barang Warung Adhi Jaya
                            </h2>
                            <p class="text-xs text-slate-600 dark:text-slate-400">
                                Pilih kategori atau ketik nama barang untuk mencari stok dengan cepat
                            </p>
                        </div>
                    </div>

                    <div x-show="selectedCategory === 'ALL' && searchQuery.trim() === ''" class="flex items-center gap-1.5 self-start sm:self-center shrink-0">
                        <button type="button"
                                @click="expandAll(true)"
                                class="px-3 py-1.5 rounded-xl bg-teal-50 dark:bg-slate-950 hover:bg-teal-100 text-[#0F766E] dark:text-teal-300 border border-slate-200 dark:border-slate-700 text-[11px] font-extrabold cursor-pointer">
                            Buka Semua
                        </button>
                        <button type="button"
                                @click="expandAll(false)"
                                class="px-3 py-1.5 rounded-xl bg-teal-50 dark:bg-slate-950 hover:bg-teal-100 text-[#0F766E] dark:text-teal-300 border border-slate-200 dark:border-slate-700 text-[11px] font-extrabold cursor-pointer">
                            Ringkas
                        </button>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="relative">
                    <span class="material-symbols-outlined text-[#0D9488] dark:text-teal-300 absolute left-3.5 top-1/2 -translate-y-1/2 text-[20px]">search</span>
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Cari cepat barang (Cabai, Jagung, Bawang, Minyak, Beras, Telur)..."
                           class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-2xl pl-10 pr-9 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0D9488] transition" />
                    <button type="button"
                           x-show="searchQuery.length > 0"
                           @click="searchQuery = ''"
                           class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <!-- Filter Tab Kategori Kapsul -->
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-0.5">
                    <button type="button"
                            @click="selectedCategory = 'ALL'"
                            :class="selectedCategory === 'ALL' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold shadow-md shadow-teal-600/25' : 'bg-teal-50 dark:bg-slate-950 text-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold hover:bg-teal-100'"
                            class="px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                        Semua ({{ $sawItems->count() }})
                    </button>
                    <button type="button"
                            @click="selectedCategory = 'KRITIS'"
                            :class="selectedCategory === 'KRITIS' ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-extrabold shadow-md shadow-rose-500/30' : 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-500/30 font-bold hover:bg-rose-100'"
                            class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                        <span class="material-symbols-outlined text-[14px]">warning</span>
                        <span>Perlu Dibeli &amp; Sisa Kemarin ({{ $criticalItems->count() }})</span>
                    </button>
                    @foreach($categoryGroupsMeta as $catKey => $meta)
                        @php
                            $catCount = $sawItems->where('kategori', $catKey)->count();
                        @endphp
                        <button type="button"
                                @click="selectedCategory = @js($catKey)"
                                :class="selectedCategory === @js($catKey) ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold shadow-md shadow-teal-600/25' : 'bg-teal-50 dark:bg-slate-950 text-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold hover:bg-teal-100'"
                                class="px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                            {{ $meta['tab'] }} ({{ $catCount }})
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Accordion Kelompok Kategori -->
            <div class="flex flex-col gap-3">
                @foreach($categoryGroupsMeta as $catKey => $meta)
                    @php
                        $groupItems = $sawItems->where('kategori', $catKey)->values();
                        $groupCriticalCount = $groupItems->whereIn('stock_status', ['habis', 'menipis'])->count();
                    @endphp

                    @if($groupItems->isNotEmpty())
                        <div x-show="selectedCategory === 'ALL' || selectedCategory === 'KRITIS' || selectedCategory === @js($catKey)"
                             class="rounded-3xl bg-white dark:bg-slate-900 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 overflow-hidden">

                            <!-- Accordion Category Header -->
                            <button type="button"
                                    @click="toggleSection(@js($catKey))"
                                    class="w-full p-4 sm:px-5 bg-slate-50 dark:bg-slate-800 hover:bg-teal-50 dark:hover:bg-teal-950/50 flex items-center justify-between gap-3 text-left transition cursor-pointer border-b border-slate-200 dark:border-slate-800">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-600/20 flex items-center justify-center shrink-0 font-extrabold text-xs">
                                        {{ $meta['code'] }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-slate-100 truncate">{{ $meta['title'] }}</h3>
                                            <span class="px-2.5 py-0.5 rounded-full bg-teal-100 dark:bg-teal-500/25 text-teal-800 dark:text-teal-200 text-[10px] font-extrabold">
                                                {{ $groupItems->count() }} Barang
                                            </span>
                                            @if($groupCriticalCount > 0)
                                                <span class="px-2.5 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 text-[10px] font-extrabold">
                                                    {{ $groupCriticalCount }} Perlu Beli
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-600 dark:text-slate-400 truncate mt-0.5">{{ $meta['desc'] }}</p>
                                    </div>
                                </div>

                                <span class="material-symbols-outlined text-[22px] text-slate-400 transition-transform duration-200 shrink-0"
                                      :class="isSectionOpen(@js($catKey)) ? 'rotate-180 text-[#0D9488] dark:text-teal-300' : ''">
                                    expand_more
                                </span>
                            </button>

                            <!-- Accordion Body: Grid 2 Kolom -->
                            <div x-show="isSectionOpen(@js($catKey))" x-transition class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3 bg-white dark:bg-slate-900">
                                @foreach($groupItems as $item)
                                    @php
                                        $itemName = $item->name ?? $item->nama_barang ?? '-';
                                        $itemKategori = $item->kategori ?? $item->category ?? '-';
                                        $itemStatus = strtolower((string) ($item->stock_status ?? $item->status ?? 'aman'));
                                        $itemUnit = $item->unit ?? 'Kg';
                                        $itemStock = (float) ($item->current_stock ?? 0);
                                        $itemCost = (float) ($item->cost_per_unit ?? 0);
                                        $itemIsFifo = (bool) ($item->is_fifo ?? false);
                                        $itemImgUrl = $item->image_url ?? asset('images/commodities/default-commodity.svg');
                                        $itemRank = $item->saw_rank ?? $item->rank ?? $item->peringkat ?? $loop->iteration;
                                        $isHabis   = $itemStatus === 'habis';
                                        $isMenipis = $itemStatus === 'menipis';

                                        if ($isHabis) {
                                            $badgeClass = 'bg-rose-100 dark:bg-rose-950/70 text-rose-800 dark:text-rose-200 border border-rose-200 dark:border-rose-500/30';
                                            $badgeLabel = 'HABIS';
                                            $subTextClass = 'text-rose-600 dark:text-rose-400 font-bold';
                                        } elseif ($isMenipis) {
                                            $badgeClass = 'bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-200 border border-amber-200 dark:border-amber-500/30';
                                            $badgeLabel = 'MENIPIS';
                                            $subTextClass = 'text-amber-700 dark:text-amber-400 font-bold';
                                        } else {
                                            $badgeClass = 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30';
                                            $badgeLabel = 'AMAN';
                                            $subTextClass = 'text-emerald-600 dark:text-emerald-400 font-bold';
                                        }
                                    @endphp

                                    <div x-show="matchesItem(@js($itemName), @js($itemKategori), @js($itemStatus), {{ $itemIsFifo ? 'true' : 'false' }}, @js($itemUnit))"
                                         class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 flex flex-col justify-between gap-3 hover:border-[#0D9488] transition">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <img src="{{ $itemImgUrl }}"
                                                     alt="{{ $itemName }}"
                                                     loading="lazy"
                                                     onerror="this.onerror=null;this.src='{{ asset('images/commodities/default-commodity.svg') }}';"
                                                     class="w-14 h-14 sm:w-16 sm:h-16 shrink-0 rounded-xl overflow-hidden object-cover object-center border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 block" />
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <h4 class="text-sm font-extrabold text-slate-900 dark:text-slate-100 truncate">{{ $itemName }}</h4>
                                                        @if($itemIsFifo)
                                                            <span class="px-2 py-0.5 rounded-full bg-amber-500 text-white text-[9px] font-extrabold">
                                                                Sisa Kemarin - Jual Duluan
                                                            </span>
                                                        @endif
                                                        @if($isOwner && !empty($item->price_unset))
                                                            <span class="px-2 py-0.5 rounded-full bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-500/40 text-[9px] font-extrabold">
                                                                Harga Belum Diatur
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5 truncate">
                                                        @if($isOwner)
                                                            Beli: Rp {{ number_format($itemCost, 0, ',', '.') }}/{{ $itemUnit }}
                                                            @if($item->linked_menu ?? null)
                                                                • Jual: Rp {{ number_format((float) ($item->linked_menu->price_eceran ?? 0), 0, ',', '.') }}/{{ $item->linked_menu->sale_unit ?? $itemUnit }}
                                                            @endif
                                                        @else
                                                            Satuan: {{ $itemUnit }}
                                                        @endif
                                                    </p>
                                                    <span class="text-[11px] {{ $subTextClass }} mt-0.5 flex items-center gap-1">
                                                        Stok: {{ number_format($itemStock, $itemStock == (int) $itemStock ? 0 : 1, ',', '.') }} {{ $itemUnit }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="text-right shrink-0 flex flex-col items-end gap-1">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $badgeClass }}">
                                                    {{ $badgeLabel }}
                                                </span>
                                                <span class="text-[11px] font-extrabold text-[#0D9488] dark:text-teal-300 tabular-nums">
                                                    Prioritas #{{ $itemRank }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>

    </div>
</x-app-layout>
