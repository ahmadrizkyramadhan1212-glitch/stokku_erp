<x-app-layout>
    @php
        $user = Auth::user();
        $isOwner = $user ? $user->isOwner() : false;
        $ingredients = collect($ingredients ?? []);
        $fifoItems = collect($fifoItems ?? []);
        $totalCount = (int) ($totalCount ?? $ingredients->count());
        $waCard = $waCard ?? [];

        // Susun JSON state awal untuk pembaruan stok cepat tanpa reload
        $defaultFallbackImg = asset('images/commodities/default-commodity.svg');
        $initialItemsState = [];
        foreach ($ingredients as $ing) {
            $status = strtolower((string) ($ing->stock_status ?? $ing->status ?? 'aman'));
            $label = strtoupper($status);
            $ingStock = (float) ($ing->current_stock ?? 0);
            $ingMin = (float) ($ing->minimum_stock ?? $ing->min_stock ?? 0);
            $ingUnit = $ing->unit ?? 'Kg';
            $qtyFormatted = $status === 'habis' ? '0' : number_format($ingStock, $ingStock == (int) $ingStock ? 0 : 1, ',', '.');
            $initialItemsState[$ing->id] = [
                'id'           => $ing->id,
                'name'         => $ing->name ?? $ing->nama_barang ?? '-',
                'kategori'     => $ing->kategori ?? $ing->category ?? 'Sayur & Buah Segar',
                'image_url'    => $ing->image_url ?? $defaultFallbackImg,
                'status'       => $status,
                'badge_text'   => "{$qtyFormatted} {$ingUnit} ({$label})",
                'stock'        => $ingStock,
                'min_stock'    => $ingMin,
                'prev_stock'   => $ingStock,
                'est_sold'     => 0.0,
                'unit'         => $ingUnit,
                'cost'         => (float) ($ing->cost_per_unit ?? 0),
                'price_eceran' => (float) ($ing->linked_menu->price_eceran ?? 0),
                'rec_qty'      => (int) ($ing->recommended_restock_qty ?? max(5, ceil($ingMin * 1.5))),
                'is_fifo'      => (bool) ($ing->is_fifo ?? false),
                'perish_score' => (int) ($ing->perishability_score ?? 1),
                'saw_rank'     => (int) ($ing->saw_rank ?? $ing->rank ?? 99),
            ];
        }

        // Bangun daftar pesan WA real-time untuk tombol Kirim ke WA Owner
        $criticalList = $ingredients->whereIn('stock_status', ['habis', 'menipis'])->values();
        $waShoppingLines = [
            "*LAPORAN CEK STOK MALAM & DAFTAR BELANJA BESOK*",
            "Warung Sembako & Sayur Adhi Jaya • " . now()->translatedFormat('d F Y (H:i)') . " WIB",
            "----------------------------------------",
            "*RINGKASAN RAK MALAM INI:*",
            "• Total Komoditas Dicek: " . $ingredients->count() . " Barang",
            "• Barang Habis / Menipis: " . $criticalList->count() . " Barang",
            "• Sisa Kemarin - Jual Duluan: " . $fifoItems->count() . " Barang",
            "",
            "*DAFTAR BELANJA PASAR BESOK SUBUH:*",
        ];
        foreach ($criticalList->take(15) as $idx => $cItem) {
            $statusUpper = strtoupper($cItem->stock_status);
            $recQty = $cItem->recommended_restock_qty ?? max(5, ceil($cItem->minimum_stock * 1.5));
            $waShoppingLines[] = ($idx + 1) . ". {$cItem->name} [{$statusUpper}] -> Beli +{$recQty} {$cItem->unit}";
        }
        if ($fifoItems->isNotEmpty()) {
            $waShoppingLines[] = "";
            $waShoppingLines[] = "*SISA KEMARIN - JUAL DULUAN (TARUH DI DEPAN RAK):*";
            $waShoppingLines[] = "• " . $fifoItems->pluck('name')->implode(', ');
        }
        $waQuickUrl = 'https://wa.me/' . ($waCard['phone'] ?? '6281234567890') . '?text=' . rawurlencode(implode("\n", $waShoppingLines));
    @endphp

    <div x-data="stokCepatHub(@js($initialItemsState))" class="flex flex-col gap-5 pb-10">

        <!-- ===================================================================
             FLASH / TOAST NOTIFICATION
             =================================================================== -->
        @if(session('success'))
            <div class="bg-teal-50 dark:bg-slate-900 border border-slate-200 dark:border-teal-500/40 text-[#0F172A] dark:text-slate-100 px-4 py-3 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-teal-600 dark:text-teal-300">check_circle</span>
                    <span class="text-xs font-bold">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-500/40 text-rose-900 dark:text-rose-200 px-4 py-3 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-rose-600 dark:text-rose-400">error</span>
                    <span class="text-xs font-bold">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- Floating Toast Real-time -->
        <div x-show="toastVisible"
             x-transition
             style="display: none;"
             class="fixed bottom-24 right-4 z-50 max-w-sm bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white px-4 py-3 rounded-2xl shadow-xl shadow-teal-950/50 border border-white/25 flex items-center gap-3">
            <span class="material-symbols-outlined text-white text-[20px]">bolt</span>
            <div class="text-xs">
                <p class="font-extrabold text-white" x-text="toastTitle"></p>
                <p class="text-white/90 text-[11px]" x-text="toastMessage"></p>
            </div>
        </div>

        <!-- ===================================================================
             1. HEADER HALAMAN CEK STOK & UPDATE STATUS RAK (HD GLOSSY)
             =================================================================== -->
        <section class="relative overflow-hidden rounded-3xl p-6 sm:p-6 bg-gradient-to-br from-[#0D9488] to-[#0F766E] text-white shadow-xl shadow-teal-500/30 border border-white/25">
            <!-- Dekorasi Cahaya Glossy -->
            <div class="pointer-events-none absolute -top-12 -right-12 w-44 h-44 rounded-full bg-white/15 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-14 -left-10 w-40 h-40 rounded-full bg-white/15 blur-2xl"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center text-white shrink-0 shadow-md">
                        <span class="material-symbols-outlined text-[26px]">inventory_2</span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded-full bg-white/20 backdrop-blur-md border border-white/30 text-white text-[10px] font-extrabold uppercase tracking-wider">
                                Cek Rak Harian Warung
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-400/25 border border-amber-300/40 text-white text-[10px] font-extrabold">
                                <span x-text="criticalTotal"></span> Barang Habis / Menipis
                            </span>
                        </div>
                        <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight">
                            Cek &amp; Update Stok Barang Warung ({{ $totalCount }} Komoditas)
                        </h1>
                        <p class="text-xs text-white/90 mt-0.5 leading-relaxed max-w-2xl">
                            Semua barang yang masih banyak otomatis berstatus <strong class="text-white">AMAN</strong>. Cukup ketuk <strong class="text-amber-200">MENIPIS</strong> atau <strong class="text-rose-200">HABIS</strong> hanya pada barang yang berkurang di rak.
                        </p>
                    </div>
                </div>

                <!-- Tombol Aksi Cepat di Kanan Atas: Set Semua Aman & Tambah Komoditas -->
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <button type="button"
                            @click="setAllCategorySafe()"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-2xl bg-white/20 hover:bg-white/30 backdrop-blur-md text-white border border-white/30 text-xs font-extrabold shadow-sm transition active:scale-95 cursor-pointer"
                            title="Kembalikan barang di tab ini menjadi AMAN">
                        <span class="material-symbols-outlined text-[18px]">done_all</span>
                        <span>Set Tab Ini Semua AMAN</span>
                    </button>

                    @if($isOwner)
                        <button type="button"
                                @click="showAddModal = true"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-white text-teal-700 hover:bg-teal-50 text-xs font-extrabold shadow-md transition active:scale-95 cursor-pointer">
                            <span class="material-symbols-outlined text-[18px] text-teal-600">add_circle</span>
                            <span>+ Barang Baru</span>
                        </button>
                    @endif
                </div>
            </div>
        </section>

        <!-- ===================================================================
             2. FITUR FILTER KATEGORI KAPSUL GLOSSY + SEARCH BAR CEPAT
             =================================================================== -->
        <div class="relative bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-4 shadow-lg shadow-teal-950/10 dark:shadow-teal-950/60 border border-slate-200 dark:border-slate-700 flex flex-col gap-3">
            <!-- Baris Search Cepat + Statistik Ringkas -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <div class="relative flex-1">
                    <span class="material-symbols-outlined text-teal-600 dark:text-teal-300 absolute left-3.5 top-1/2 -translate-y-1/2 text-[20px]">search</span>
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Ketik nama barang (contoh: Cabai, Bawang, Telur, Minyak, Beras)..."
                           class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-2xl pl-10 pr-9 py-2.5 text-xs sm:text-sm font-semibold text-[#0F172A] dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 transition" />
                    <button type="button"
                            x-show="searchQuery.length > 0"
                            @click="searchQuery = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <!-- 3 Indikator Status Real-Time -->
                <div class="flex items-center gap-2 shrink-0 overflow-x-auto no-scrollbar">
                    <button type="button"
                            @click="activeTab = 'KRITIS'"
                            class="px-3 py-2 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-extrabold flex items-center gap-1.5 cursor-pointer hover:bg-rose-100 transition">
                        <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                        <span>Habis: <strong x-text="habisTotal"></strong></span>
                    </button>
                    <button type="button"
                            @click="activeTab = 'KRITIS'"
                            class="px-3 py-2 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-500/30 text-amber-800 dark:text-amber-300 text-xs font-extrabold flex items-center gap-1.5 cursor-pointer hover:bg-amber-100 transition">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>Menipis: <strong x-text="menipisTotal"></strong></span>
                    </button>
                    <span class="px-3 py-2 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-xs font-extrabold flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Aman: <strong x-text="amanTotal"></strong></span>
                    </span>
                </div>
            </div>

            <!-- Baris Tab Kategori Horizontal (Tombol Kapsul Glossy Menyala Saat Aktif - 100% Vector SVG) -->
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-0.5">
                <!-- Tab 1 (Default): Prioritas Harian / Cepat Habis -->
                <button type="button"
                        @click="activeTab = 'PRIORITAS'"
                        :class="activeTab === 'PRIORITAS' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold shadow-md shadow-teal-500/30' : 'bg-teal-50 dark:bg-slate-950 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold hover:bg-teal-100'"
                        class="px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                    <span>Prioritas Harian / Cepat Habis</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/20" x-text="countByTab('PRIORITAS')"></span>
                </button>

                <!-- Tab 2: Sayur & Buah -->
                <button type="button"
                        @click="activeTab = 'Sayur & Buah Segar'"
                        :class="activeTab === 'Sayur & Buah Segar' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold shadow-md shadow-teal-500/30' : 'bg-teal-50 dark:bg-slate-950 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold hover:bg-teal-100'"
                        class="px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                    <span>Sayur &amp; Buah (<span x-text="countByTab('Sayur & Buah Segar')"></span>)</span>
                </button>

                <!-- Tab 3: Bumbu Dapur -->
                <button type="button"
                        @click="activeTab = 'Bumbu Dapur & Rempah'"
                        :class="activeTab === 'Bumbu Dapur & Rempah' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold shadow-md shadow-teal-500/30' : 'bg-teal-50 dark:bg-slate-950 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold hover:bg-teal-100'"
                        class="px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 21h10"/><path d="M12 21a9 9 0 0 0 9-9H3a9 9 0 0 0 9 9Z"/><path d="M11.38 12a2.4 2.4 0 0 1-.4-4.77 2.4 2.4 0 0 1 3.2-2.77 2.4 2.4 0 0 1 3.47-.63 2.4 2.4 0 0 1 3.37 3.37 2.4 2.4 0 0 1-1.1 3.7 2.51 2.51 0 0 1 .03 1.1"/></svg>
                    <span>Bumbu Dapur (<span x-text="countByTab('Bumbu Dapur & Rempah')"></span>)</span>
                </button>

                <!-- Tab 4: Minyak & Kecap -->
                <button type="button"
                        @click="activeTab = 'Minyak, Saus & Kecap'"
                        :class="activeTab === 'Minyak, Saus & Kecap' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold shadow-md shadow-teal-500/30' : 'bg-teal-50 dark:bg-slate-950 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold hover:bg-teal-100'"
                        class="px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>
                    <span>Minyak &amp; Kecap (<span x-text="countByTab('Minyak, Saus & Kecap')"></span>)</span>
                </button>

                <!-- Tab 5: Sembako & Mie -->
                <button type="button"
                        @click="activeTab = 'Sembako & Bahan Pokok'"
                        :class="activeTab === 'Sembako & Bahan Pokok' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold shadow-md shadow-teal-500/30' : 'bg-teal-50 dark:bg-slate-950 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold hover:bg-teal-100'"
                        class="px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 22 16 8"/><path d="M3.47 12.53 5 11l1.53 1.53a3.5 3.5 0 0 1 0 4.94L5 19l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M7.47 8.53 9 7l1.53 1.53a3.5 3.5 0 0 1 0 4.94L9 15l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M11.47 4.53 13 3l1.53 1.53a3.5 3.5 0 0 1 0 4.94L13 11l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M20 2h2v2a4 4 0 0 1-4 4h-2V6a4 4 0 0 1 4-4Z"/></svg>
                    <span>Sembako &amp; Mie (<span x-text="countByTab('Sembako & Bahan Pokok')"></span>)</span>
                </button>

                <!-- Tab 6: Lainnya -->
                <button type="button"
                        @click="activeTab = 'Lauk, Minuman & Rumah Tangga'"
                        :class="activeTab === 'Lauk, Minuman & Rumah Tangga' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold shadow-md shadow-teal-500/30' : 'bg-teal-50 dark:bg-slate-950 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold hover:bg-teal-100'"
                        class="px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    <span>Lainnya (<span x-text="countByTab('Lauk, Minuman & Rumah Tangga')"></span>)</span>
                </button>

                <!-- Tab 7: Semua (96) -->
                <button type="button"
                        @click="activeTab = 'ALL'"
                        :class="activeTab === 'ALL' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white font-extrabold shadow-md shadow-teal-500/30' : 'bg-teal-50 dark:bg-slate-950 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold hover:bg-teal-100'"
                        class="px-4 py-2 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                    Semua ({{ $totalCount }})
                </button>
            </div>
        </div>

        <!-- ===================================================================
             3. BANNER PENGINGAT SISA KEMARIN - JUAL DULUAN (BAHASA WARUNG)
             =================================================================== -->
        @if($fifoItems->isNotEmpty())
            <div class="bg-amber-50 dark:bg-amber-950/35 border border-amber-200 dark:border-amber-500/35 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start sm:items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[18px]">priority_high</span>
                    </span>
                    <div class="text-xs">
                        <span class="font-extrabold text-amber-950 dark:text-amber-200">Pengingat Sisa Kemarin - Jual Duluan ({{ $fifoItems->count() }} Barang Segar):</span>
                        <span class="text-amber-900 dark:text-amber-300 font-medium">
                            {{ $fifoItems->pluck('name')->implode(', ') }} — letakkan di rak paling depan agar terjual habis lebih dulu sebelum membuka stok baru.
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- ===================================================================
             4. GRID KOMODITAS WARUNG (TOMBOL STATUS CEPAT: AMAN | MENIPIS | HABIS)
             =================================================================== -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($ingredients as $item)
                @php
                    $isFifo = (bool) ($item->is_fifo ?? false);
                    $perishScore = (int) ($item->perishability_score ?? 1);
                    $itemName = $item->name ?? $item->nama_barang ?? '-';
                    $itemKategori = $item->kategori ?? $item->category ?? '-';
                    $itemUnit = $item->unit ?? 'Kg';
                    $itemCost = (float) ($item->cost_per_unit ?? 0);
                    $itemMinStock = (float) ($item->minimum_stock ?? $item->min_stock ?? 0);
                    $itemRecQty = (int) ($item->recommended_restock_qty ?? 5);
                @endphp

                <div x-show="isVisible({{ $item->id }})"
                     :class="{
                        'border-rose-300 dark:border-rose-500/50 bg-rose-50/40 dark:bg-rose-950/25': itemStatus({{ $item->id }}) === 'habis',
                        'border-amber-300 dark:border-amber-500/50 bg-amber-50/40 dark:bg-amber-950/25': itemStatus({{ $item->id }}) === 'menipis',
                        'border-slate-300 dark:border-slate-700 shadow-sm bg-white dark:bg-slate-900': itemStatus({{ $item->id }}) === 'aman'
                     }"
                     class="rounded-3xl p-4 shadow-md dark:shadow-lg dark:shadow-teal-950/40 border transition-all flex flex-col justify-between gap-3">

                    <!-- Baris 1 (Top Bar): Label Kategori (kiri) + Badge Status Stok (pojok kanan atas) -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400 min-w-0 truncate"
                              x-text="items[{{ $item->id }}] ? items[{{ $item->id }}].kategori : @js($itemKategori)">
                            {{ $itemKategori }}
                        </span>
                        <span :class="badgeClass({{ $item->id }})"
                              class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase shrink-0 whitespace-nowrap tabular-nums"
                              x-text="badgeText({{ $item->id }})">
                        </span>
                    </div>

                    <!-- Baris 2 (Info Utama): Thumbnail (kiri) + Nama, Batas Aman, Badge FIFO (kanan) -->
                    <div class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            <div class="w-14 h-14 rounded-xl overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 relative">
                                <img :src="itemImage({{ $item->id }})"
                                     src="{{ $item->image_url }}"
                                     alt="{{ $itemName }}"
                                     width="56" height="56"
                                     style="width:100%;height:100%;max-width:100%;object-fit:cover;"
                                     loading="lazy"
                                     onerror="this.onerror=null;this.src='{{ $defaultFallbackImg }}';"
                                     class="w-full h-full object-cover object-center block" />
                            </div>
                            <span :class="{
                                    'bg-rose-600': itemStatus({{ $item->id }}) === 'habis',
                                    'bg-amber-500': itemStatus({{ $item->id }}) === 'menipis',
                                    'bg-emerald-500': itemStatus({{ $item->id }}) === 'aman'
                                 }"
                                 class="absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white dark:border-slate-900"></span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 leading-snug break-words"
                                x-text="items[{{ $item->id }}] ? items[{{ $item->id }}].name : @js($itemName)">
                                {{ $itemName }}
                            </h3>

                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Batas Aman: <strong><span x-text="items[{{ $item->id }}] ? items[{{ $item->id }}].min_stock : {{ $itemMinStock }}">{{ number_format($itemMinStock, 0, ',', '.') }}</span> {{ $itemUnit }}</strong>
                                @if($isOwner)
                                    <span class="whitespace-nowrap">• Modal: <strong class="text-slate-800 dark:text-slate-100">Rp {{ number_format($itemCost, 0, ',', '.') }}/{{ $itemUnit }}</strong></span>
                                @endif
                            </p>

                            @if($isOwner && ($itemCost <= 0 || (float) ($item->linked_menu->price_eceran ?? 0) <= 0))
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 mt-1.5 rounded-full text-[11px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-500/40 whitespace-nowrap w-fit">
                                    <span class="material-symbols-outlined text-[13px]">sell</span>
                                    Harga Belum Diatur
                                </span>
                            @endif

                            <template x-if="items[{{ $item->id }}] && items[{{ $item->id }}].is_fifo && items[{{ $item->id }}].stock > 0">
                                <span class="inline-flex items-center px-2.5 py-0.5 mt-1.5 rounded-full text-[11px] font-semibold bg-amber-500 text-white whitespace-nowrap w-fit">
                                    Sisa Kemarin - Jual Duluan
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Notifikasi Otomatis Estimasi Barang Laku Saat Diklik Menipis / Habis -->
                    <div x-show="estSold({{ $item->id }}) > 0"
                         x-transition
                         style="display: none;"
                         class="px-3 py-2 rounded-xl bg-teal-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-[11px] text-teal-950 dark:text-teal-200 flex items-center justify-between">
                        <span class="flex items-center gap-1 font-bold">
                            <span class="material-symbols-outlined text-[15px] text-teal-600 dark:text-teal-300">trending_up</span>
                            <span>Otomatis tercatat laku hari ini:</span>
                        </span>
                        <strong class="font-extrabold text-teal-600 dark:text-teal-300 tabular-nums">
                            +<span x-text="estSold({{ $item->id }})"></span> {{ $itemUnit }}
                        </strong>
                    </div>

                    <!-- 3 TOMBOL STATUS CEPAT: [AMAN] [MENIPIS] [HABIS] -->
                    <div class="grid grid-cols-3 gap-1.5 pt-1">
                        <!-- Tombol 1: AMAN -->
                        <button type="button"
                                :disabled="loadingId === {{ $item->id }}"
                                @click.stop="tapStatus({{ $item->id }}, 'aman')"
                                :class="itemStatus({{ $item->id }}) === 'aman'
                                    ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-600/30 font-extrabold'
                                    : 'bg-slate-100 text-slate-700 border border-slate-300 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-600 dark:hover:bg-slate-700 font-bold'"
                                class="py-2.5 px-2 rounded-2xl text-[11px] flex flex-col items-center justify-center gap-0.5 transition active:scale-95 cursor-pointer">
                            <span class="inline-flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span>Aman</span>
                            </span>
                            <span class="text-[9px] opacity-85">Di Atas Batas</span>
                        </button>

                        <!-- Tombol 2: MENIPIS -->
                        <button type="button"
                                :disabled="loadingId === {{ $item->id }}"
                                @click.stop="tapStatus({{ $item->id }}, 'menipis')"
                                :class="itemStatus({{ $item->id }}) === 'menipis'
                                    ? 'bg-amber-500 text-white shadow-sm ring-2 ring-amber-500/30 font-extrabold'
                                    : 'bg-slate-100 text-slate-700 border border-slate-300 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-600 dark:hover:bg-slate-700 font-bold'"
                                class="py-2.5 px-2 rounded-2xl text-[11px] flex flex-col items-center justify-center gap-0.5 transition active:scale-95 cursor-pointer">
                            <span class="inline-flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-amber-300"></span>
                                <span>Menipis</span>
                            </span>
                            <span class="text-[9px] opacity-85">Sisa {{ number_format($itemMinStock, 0, ',', '.') }} {{ $itemUnit }}</span>
                        </button>

                        <!-- Tombol 3: HABIS -->
                        <button type="button"
                                :disabled="loadingId === {{ $item->id }}"
                                @click.stop="tapStatus({{ $item->id }}, 'habis')"
                                :class="itemStatus({{ $item->id }}) === 'habis'
                                    ? 'bg-rose-600 text-white shadow-sm ring-2 ring-rose-600/30 font-extrabold'
                                    : 'bg-slate-100 text-slate-700 border border-slate-300 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-600 dark:hover:bg-slate-700 font-bold'"
                                class="py-2.5 px-2 rounded-2xl text-[11px] flex flex-col items-center justify-center gap-0.5 transition active:scale-95 cursor-pointer">
                            <span class="inline-flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-rose-300"></span>
                                <span>Habis</span>
                            </span>
                            <span class="text-[9px] opacity-85">Stok 0 {{ $itemUnit }}</span>
                        </button>
                    </div>

                    <!-- Footer Aksi Tambahan: Koreksi Angka, Edit & Foto, dan + Kulakan Cepat -->
                    <div class="pt-2 border-t border-teal-50 dark:border-slate-800 flex items-center justify-between gap-1.5 text-[11px] flex-wrap">
                        <div class="flex items-center gap-3">
                            <button type="button"
                                    @click.stop="openOpname({{ $item->id }})"
                                    class="text-slate-600 dark:text-slate-400 hover:text-teal-600 dark:hover:text-teal-300 font-bold inline-flex items-center gap-1 cursor-pointer">
                                <span class="material-symbols-outlined text-[15px]">tune</span>
                                <span>Koreksi Angka</span>
                            </button>

                            <button type="button"
                                    @click.stop="openEditModal({{ $item->id }})"
                                    class="text-slate-500 dark:text-slate-400 hover:text-teal-600 dark:hover:text-teal-300 font-bold inline-flex items-center gap-1 cursor-pointer">
                                <span class="material-symbols-outlined text-[15px]">photo_camera</span>
                                <span>Edit / Foto</span>
                            </button>
                        </div>

                        @if($isOwner)
                            <button type="button"
                                    @click.stop="openPurchase({{ $item->id }})"
                                    class="text-teal-600 dark:text-teal-300 hover:underline font-extrabold inline-flex items-center gap-1 cursor-pointer">
                                <span class="material-symbols-outlined text-[15px]">add_shopping_cart</span>
                                <span>+ Kulakan Cepat</span>
                            </button>
                        @else
                            <span class="text-[10px] font-extrabold text-teal-600 dark:text-teal-300">
                                Saran Beli: +{{ $itemRecQty }} {{ $itemUnit }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- ===================================================================
             5. BAGIAN BAWAH HALAMAN: TOMBOL PENGIRIMAN LAPORAN WHATSAPP GLOSSY
             Diletakkan rapi di bagian paling bawah halaman (bukan popup mengambang)
             =================================================================== -->
        <section class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-6 shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-500/25 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-[24px]">fact_check</span>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-extrabold text-[#0F172A] dark:text-slate-100">
                        Selesai Cek Rak Malam Ini? Kirim Laporan ke WhatsApp Owner
                    </h2>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                        Saat ini tercatat <strong class="text-rose-600 dark:text-rose-400" x-text="habisTotal"></strong> barang habis dan <strong class="text-amber-600 dark:text-amber-400" x-text="menipisTotal"></strong> barang menipis yang siap masuk daftar belanja pasar besok subuh.
                    </p>
                </div>
            </div>

            <a :href="buildWaUrl()"
               target="_blank"
               rel="noopener"
               class="py-3.5 px-6 rounded-2xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white text-xs sm:text-sm font-extrabold shadow-lg shadow-teal-500/35 flex items-center justify-center gap-2 transition active:scale-95 shrink-0">
                <svg class="w-4 h-4 text-white shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m22 2-7 20-4-9-9-4Z"/>
                    <path d="M22 2 11 13"/>
                </svg>
                <span>Kirim Rincian Stok &amp; Daftar Belanja ke WA Owner</span>
            </a>
        </section>

        <!-- ===================================================================
             MODAL 1: TAMBAH KULAKAN CEPAT DARI KARTU BARANG
             =================================================================== -->
        <div x-cloak style="display:none" x-show="showPurchase"
             @click.self="showPurchase = false"
             style="display: none;"
             class="fixed inset-0 z-50 bg-black/65 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 sm:p-6 shadow-2xl border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-teal-600 dark:text-teal-300">Tambah Stok Masuk</span>
                        <h3 class="text-base font-extrabold text-[#0F172A] dark:text-slate-100" x-text="selectedItemName"></h3>
                    </div>
                    <button type="button" @click="showPurchase = false" class="w-8 h-8 rounded-full bg-teal-50 dark:bg-slate-950 flex items-center justify-center text-slate-500 cursor-pointer dark:text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <form action="{{ route('inventory.purchase') }}" method="POST" @submit.prevent="submitPurchase()" class="space-y-3.5 mt-4">
                    @csrf
                    <input type="hidden" name="ingredient_id" :value="selectedItemId">

                    <div>
                        <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">
                            Jumlah Belanja Masuk (<span x-text="selectedItemUnit"></span>)
                        </label>
                        <input type="number" step="0.01" min="0.1" name="quantity"
                               x-model="purchaseQty"
                               @input="purchaseTotal = Math.round((parseFloat(purchaseQty) || 0) * selectedItemCost)"
                               required
                               class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2.5 text-sm font-extrabold text-[#0F172A] dark:text-slate-100" />
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">
                            Total Nota Belanja (Rp)
                        </label>
                        <input type="number" step="100" min="0" name="total_price"
                               x-model="purchaseTotal"
                               required
                               class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2.5 text-sm font-extrabold text-[#0F172A] dark:text-slate-100" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showPurchase = false" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-950 text-xs font-bold text-slate-600 dark:text-slate-400 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white text-xs font-extrabold shadow-md shadow-teal-500/30 cursor-pointer">
                            Simpan Stok Masuk
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===================================================================
             MODAL 2: KOREKSI JUMLAH FISIK / BARANG BUSUK (OPNAME)
             =================================================================== -->
        <div x-cloak style="display:none" x-show="showOpname"
             @click.self="showOpname = false"
             style="display: none;"
             class="fixed inset-0 z-50 bg-black/65 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 sm:p-6 shadow-2xl border border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-600 dark:text-amber-400">Koreksi Stok Fisik / Susut</span>
                        <h3 class="text-base font-extrabold text-[#0F172A] dark:text-slate-100" x-text="selectedItemName"></h3>
                    </div>
                    <button type="button" @click="showOpname = false" class="w-8 h-8 rounded-full bg-teal-50 dark:bg-slate-950 flex items-center justify-center text-slate-500 cursor-pointer dark:text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <form action="{{ route('inventory.opname') }}" method="POST" @submit.prevent="submitOpname()" class="space-y-3.5 mt-4">
                    @csrf
                    <input type="hidden" name="ingredient_id" :value="selectedItemId">

                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-xs flex justify-between items-center">
                        <span class="text-slate-600 dark:text-slate-400">Stok Tercatat / Batas Aman:</span>
                        <strong class="text-[#0F172A] dark:text-slate-100">
                            <span x-text="currentStock"></span> <span x-text="selectedItemUnit"></span>
                            <span class="text-slate-500 font-medium dark:text-slate-400">(Batas: <span x-text="selectedItemMinStock"></span> <span x-text="selectedItemUnit"></span>)</span>
                        </strong>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">
                            Jumlah Fisik Sebenarnya di Rak (<span x-text="selectedItemUnit"></span>)
                        </label>
                        <input type="number" step="0.01" min="0" name="actual_stock"
                               x-model="opnameActual"
                               required
                               class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2.5 text-sm font-extrabold text-[#0F172A] dark:text-slate-100" />
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            Otomatis: 0 = <strong>HABIS</strong> • ≤ <span x-text="selectedItemMinStock"></span> = <strong>MENIPIS</strong> • &gt; <span x-text="selectedItemMinStock"></span> = <strong>AMAN</strong>
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">
                            Keterangan Koreksi / Alasan Susut
                        </label>
                        <input type="text" name="reason"
                               x-model="opnameReason"
                               required
                               class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2.5 text-xs font-semibold text-[#0F172A] dark:text-slate-100" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showOpname = false" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-950 text-xs font-bold text-slate-600 dark:text-slate-400 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white text-xs font-extrabold shadow-md shadow-teal-500/30 cursor-pointer">
                            Simpan Koreksi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===================================================================
             MODAL 3: TAMBAH BARANG BARU + UPLOAD FOTO & PREVIEW
             =================================================================== -->
        <div x-cloak style="display:none" x-show="showAddModal"
             @click.self="showAddModal = false"
             style="display: none;"
             class="fixed inset-0 z-50 bg-black/65 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 sm:p-6 shadow-2xl border border-slate-200 dark:border-slate-700 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-teal-600 dark:text-teal-300">Komoditas Baru Warung</span>
                        <h3 class="text-base font-extrabold text-[#0F172A] dark:text-slate-100">Tambah Barang Dagangan</h3>
                    </div>
                    <button type="button" @click="showAddModal = false" class="w-8 h-8 rounded-full bg-teal-50 dark:bg-slate-950 flex items-center justify-center text-slate-500 cursor-pointer dark:text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <form action="{{ route('inventory.materials.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3 mt-4">
                    @csrf
                    <!-- Upload Foto Komoditas + Preview -->
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 flex items-center gap-3">
                        <img :src="addImagePreview || @js($defaultFallbackImg)"
                             alt="Preview Foto Barang"
                             class="w-16 h-16 rounded-2xl object-cover border border-slate-200 dark:border-teal-500/40 bg-white shrink-0" />
                        <div class="flex-1 min-w-0">
                            <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">
                                Foto Riil Komoditas (JPG, PNG, WEBP maks 2MB)
                            </label>
                            <input type="file"
                                   name="image"
                                   accept="image/*"
                                   @change="handleAddImagePreview($event)"
                                   class="block w-full text-[11px] text-slate-600 dark:text-slate-400 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-extrabold file:bg-teal-600 file:text-white hover:file:bg-teal-700 cursor-pointer" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Nama Barang</label>
                        <input type="text" name="name" required placeholder="Contoh: Cabai Rawit Merah"
                               class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2 text-xs font-semibold text-[#0F172A] dark:text-slate-100" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Kategori Rak</label>
                            <select name="kategori" class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-semibold text-[#0F172A] dark:text-slate-100">
                                <option value="Sayur & Buah Segar">Sayur &amp; Buah Segar</option>
                                <option value="Bumbu Dapur & Rempah">Bumbu Dapur &amp; Rempah</option>
                                <option value="Minyak, Saus & Kecap">Minyak, Saus &amp; Kecap</option>
                                <option value="Sembako & Bahan Pokok">Sembako &amp; Bahan Pokok</option>
                                <option value="Lauk, Minuman & Rumah Tangga">Lauk, Minuman &amp; Rumah Tangga</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Satuan</label>
                            <input type="text" name="unit" value="kg" required
                                   class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2 text-xs font-semibold text-[#0F172A] dark:text-slate-100" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Stok Awal</label>
                            <input type="number" step="0.1" name="current_stock" value="10" required
                                   class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-[#0F172A] dark:text-slate-100" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Batas Aman (Menipis)</label>
                            <input type="number" step="0.1" name="min_stock" value="3" required
                                   class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-[#0F172A] dark:text-slate-100" />
                        </div>
                    </div>

                    @if($isOwner)
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Harga Modal / Satuan (Rp)</label>
                                <input type="number" step="100" name="cost_per_unit" value="15000"
                                       class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-[#0F172A] dark:text-slate-100" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Harga Jual Eceran (Rp)</label>
                                <input type="number" step="100" name="price_eceran" value="5000"
                                       class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-[#0F172A] dark:text-slate-100" />
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="cost_per_unit" value="0">
                        <input type="hidden" name="price_eceran" value="0">
                    @endif

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-950 text-xs font-bold text-slate-600 dark:text-slate-400 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white text-xs font-extrabold shadow-md shadow-teal-500/30 cursor-pointer">
                            Tambah Barang
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===================================================================
             MODAL 4: EDIT BARANG & UPLOAD FOTO RIIL KOMODITAS
             =================================================================== -->
        <div x-cloak style="display:none" x-show="showEditModal"
             @click.self="showEditModal = false"
             style="display: none;"
             class="fixed inset-0 z-50 bg-black/65 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 sm:p-6 shadow-2xl border border-slate-200 dark:border-slate-700 my-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-teal-600 dark:text-teal-300">Edit Komoditas &amp; Foto</span>
                        <h3 class="text-base font-extrabold text-[#0F172A] dark:text-slate-100" x-text="editForm.name"></h3>
                    </div>
                    <button type="button" @click="showEditModal = false" class="w-8 h-8 rounded-full bg-teal-50 dark:bg-slate-950 flex items-center justify-center text-slate-500 cursor-pointer dark:text-slate-400">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <form :action="`/inventory/materials/${editForm.id}/update`"
                      method="POST"
                      enctype="multipart/form-data"
                      @submit.prevent="submitEdit($event)"
                      class="space-y-3 mt-4">
                    @csrf
                    <!-- Upload Foto Komoditas + Preview -->
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 flex items-center gap-3">
                        <img :src="editImagePreview || editForm.image_url || @js($defaultFallbackImg)"
                             alt="Preview Foto Barang"
                             onerror="this.onerror=null;this.src='{{ $defaultFallbackImg }}';"
                             class="w-16 h-16 rounded-2xl object-cover border border-slate-200 dark:border-teal-500/40 bg-white shrink-0" />
                        <div class="flex-1 min-w-0">
                            <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">
                                Ganti Foto Komoditas (JPG, PNG, WEBP maks 2MB)
                            </label>
                            <input type="file"
                                   name="image"
                                   accept="image/*"
                                   @change="handleEditImagePreview($event)"
                                   class="block w-full text-[11px] text-slate-600 dark:text-slate-400 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-extrabold file:bg-teal-600 file:text-white hover:file:bg-teal-700 cursor-pointer" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Nama Barang</label>
                        <input type="text" name="name" x-model="editForm.name" required
                               class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2 text-xs font-semibold text-[#0F172A] dark:text-slate-100" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Kategori Rak</label>
                            <select name="kategori" x-model="editForm.kategori" class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-semibold text-[#0F172A] dark:text-slate-100">
                                <option value="Sayur & Buah Segar">Sayur &amp; Buah Segar</option>
                                <option value="Bumbu Dapur & Rempah">Bumbu Dapur &amp; Rempah</option>
                                <option value="Minyak, Saus & Kecap">Minyak, Saus &amp; Kecap</option>
                                <option value="Sembako & Bahan Pokok">Sembako &amp; Bahan Pokok</option>
                                <option value="Lauk, Minuman & Rumah Tangga">Lauk, Minuman &amp; Rumah Tangga</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Satuan</label>
                            <input type="text" name="unit" x-model="editForm.unit" required
                                   class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2 text-xs font-semibold text-[#0F172A] dark:text-slate-100" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Stok Saat Ini</label>
                            <input type="number" step="0.01" min="0" name="current_stock" x-model="editForm.current_stock" required
                                   class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-[#0F172A] dark:text-slate-100" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Batas Aman (Menipis)</label>
                            <input type="number" step="0.01" min="0" name="min_stock" x-model="editForm.min_stock" required
                                   class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-[#0F172A] dark:text-slate-100" />
                        </div>
                    </div>

                    @if($isOwner)
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Harga Modal / Satuan (Rp)</label>
                                <input type="number" step="100" min="0" name="cost_per_unit" x-model="editForm.cost_per_unit"
                                       class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-[#0F172A] dark:text-slate-100" />
                            </div>
                            <div>
                                <label class="block text-[11px] font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">Harga Jual Eceran (Rp)</label>
                                <input type="number" step="100" min="0" name="price_eceran" x-model="editForm.price_eceran"
                                       class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-[#0F172A] dark:text-slate-100" />
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-950 text-xs font-bold text-slate-600 dark:text-slate-400 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white text-xs font-extrabold shadow-md shadow-teal-500/30 cursor-pointer">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        window.stokCepatHub = function (initialItems) {
            return {
                items: initialItems || {},
                activeTab: 'PRIORITAS',
                searchQuery: @js($search ?? ''),
                loadingId: null,
                toastVisible: false,
                toastTitle: '',
                toastMessage: '',
                toastTimer: null,

                // Modal state
                showPurchase: false,
                showOpname: false,
                showAddModal: false,
                showEditModal: false,
                selectedItemId: null,
                selectedItemName: '',
                selectedItemUnit: 'Kg',
                selectedItemCost: 0,
                selectedItemMinStock: 3,
                currentStock: 0,
                purchaseQty: 5,
                purchaseTotal: 0,
                opnameActual: 0,
                opnameReason: 'Penyesuaian stok fisik di rak',

                // Preview foto upload
                addImagePreview: null,
                editImagePreview: null,
                editForm: {
                    id: null,
                    name: '',
                    kategori: 'Sayur & Buah Segar',
                    unit: 'kg',
                    current_stock: 0,
                    min_stock: 3,
                    cost_per_unit: 0,
                    price_eceran: 0,
                    image_url: ''
                },

                get habisTotal() {
                    return Object.values(this.items).filter(i => i.status === 'habis').length;
                },
                get menipisTotal() {
                    return Object.values(this.items).filter(i => i.status === 'menipis').length;
                },
                get amanTotal() {
                    return Object.values(this.items).filter(i => i.status === 'aman').length;
                },
                get criticalTotal() {
                    return this.habisTotal + this.menipisTotal;
                },

                itemStatus(id) {
                    return this.items[id] ? this.items[id].status : 'aman';
                },
                itemImage(id) {
                    return (this.items[id] && this.items[id].image_url) ? this.items[id].image_url : @js($defaultFallbackImg);
                },
                badgeText(id) {
                    return this.items[id] ? this.items[id].badge_text : '';
                },
                estSold(id) {
                    return this.items[id] ? (this.items[id].est_sold || 0) : 0;
                },
                badgeClass(id) {
                    const st = this.itemStatus(id);
                    if (st === 'habis') {
                        return 'bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-200 border border-rose-200 dark:border-rose-500/40';
                    }
                    if (st === 'menipis') {
                        return 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-200 border border-amber-200 dark:border-amber-500/40';
                    }
                    return 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/40';
                },

                isPriorityItem(item) {
                    if (!item) return false;
                    return item.status === 'habis'
                        || item.status === 'menipis'
                        || item.is_fifo
                        || item.perish_score >= 3
                        || item.saw_rank <= 25;
                },

                countByTab(tabName) {
                    const list = Object.values(this.items);
                    if (tabName === 'PRIORITAS') {
                        return list.filter(i => this.isPriorityItem(i)).length;
                    }
                    if (tabName === 'KRITIS') {
                        return list.filter(i => i.status === 'habis' || i.status === 'menipis').length;
                    }
                    if (tabName === 'ALL') {
                        return list.length;
                    }
                    return list.filter(i => i.kategori === tabName).length;
                },

                isVisible(id) {
                    const item = this.items[id];
                    if (!item) return false;

                    const q = (this.searchQuery || '').trim().toLowerCase();
                    if (q.length > 0) {
                        return String(item.name).toLowerCase().includes(q)
                            || String(item.kategori).toLowerCase().includes(q)
                            || String(item.unit).toLowerCase().includes(q);
                    }

                    if (this.activeTab === 'ALL') return true;
                    if (this.activeTab === 'KRITIS') {
                        return item.status === 'habis' || item.status === 'menipis';
                    }
                    if (this.activeTab === 'PRIORITAS') {
                        return this.isPriorityItem(item);
                    }
                    return item.kategori === this.activeTab;
                },

                showToast(title, message) {
                    this.toastTitle = title;
                    this.toastMessage = message;
                    this.toastVisible = true;
                    if (this.toastTimer) clearTimeout(this.toastTimer);
                    this.toastTimer = setTimeout(() => {
                        this.toastVisible = false;
                    }, 2800);
                },

                async tapStatus(id, status) {
                    if (!this.items[id]) return;
                    this.loadingId = id;

                    const prevStock = parseFloat(this.items[id].stock) || 0;

                    try {
                        const res = await fetch(`/inventory/materials/${id}/quick-status`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ stock_status: status, status: status })
                        });
                        const data = await res.json();
                        if (data && data.success) {
                            const newStock = parseFloat(data.current_stock) || 0;
                            const soldDiff = Math.max(0, +(prevStock - newStock).toFixed(2));

                            this.items[id].status = data.stock_status;
                            this.items[id].stock = newStock;
                            this.items[id].badge_text = data.badge_text;
                            if (soldDiff > 0) {
                                this.items[id].est_sold = soldDiff;
                            } else if (status === 'aman') {
                                this.items[id].est_sold = 0;
                            }

                            const statusLabel = String(data.stock_status || status).toUpperCase();
                            const soldInfo = soldDiff > 0 ? ` • Otomatis tercatat laku +${soldDiff} ${this.items[id].unit}` : '';
                            this.showToast(`${this.items[id].name} -> ${statusLabel}`, `${data.badge_text}${soldInfo}`);
                        } else {
                            this.showToast('Gagal Memperbarui', (data && data.message) ? data.message : 'Silakan coba lagi.');
                        }
                    } catch (e) {
                        this.showToast('Gagal Menyimpan', 'Periksa koneksi Anda dan coba lagi.');
                    } finally {
                        this.loadingId = null;
                    }
                },

                async setAllCategorySafe() {
                    if (this.activeTab === 'ALL') {
                        try {
                            const res = await fetch(@js(route('inventory.materials.reset_aman')), {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                }
                            });
                            const data = await res.json();
                            if (data && data.success) {
                                Object.values(this.items).forEach(i => {
                                    i.status = 'aman';
                                    i.badge_text = `${i.stock} ${i.unit} (AMAN)`;
                                    i.est_sold = 0;
                                });
                                this.showToast('Selesai Direset', data.message || 'Seluruh 96 komoditas diset menjadi AMAN.');
                                return;
                            }
                        } catch (e) {}
                    }

                    const visibleIds = Object.values(this.items)
                        .filter(i => this.isVisible(i.id) && i.status !== 'aman')
                        .map(i => i.id);

                    if (visibleIds.length === 0) {
                        this.showToast('Semua Sudah Aman', 'Seluruh barang pada tab ini sudah berstatus AMAN.');
                        return;
                    }

                    for (const id of visibleIds) {
                        await this.tapStatus(id, 'aman');
                    }
                    this.showToast('Selesai Direset', `${visibleIds.length} barang pada tab ini diset menjadi AMAN.`);
                },

                buildWaUrl() {
                    const phone = @js($waCard['phone'] ?? '6281234567890');
                    const critical = Object.values(this.items).filter(i => i.status === 'habis' || i.status === 'menipis');
                    const lines = [
                        '*LAPORAN CEK STOK MALAM & DAFTAR BELANJA BESOK*',
                        'Warung Sembako & Sayur Adhi Jaya',
                        '----------------------------------------',
                        `*Ringkasan Rak:* Habis (${this.habisTotal}) • Menipis (${this.menipisTotal}) • Aman (${this.amanTotal})`,
                        '',
                        '*DAFTAR BELANJA PASAR BESOK SUBUH:*'
                    ];

                    if (critical.length === 0) {
                        lines.push('• Semua stok barang masih AMAN.');
                    } else {
                        critical.slice(0, 20).forEach((item, idx) => {
                            lines.push(`${idx + 1}. ${item.name} [${item.status.toUpperCase()}] -> Beli +${item.rec_qty} ${item.unit}`);
                        });
                    }

                    return 'https://wa.me/' + phone + '?text=' + encodeURIComponent(lines.join('\n'));
                },

                openPurchase(id) {
                    const item = this.items[id];
                    if (!item) return;
                    this.selectedItemId = item.id;
                    this.selectedItemName = item.name;
                    this.selectedItemUnit = item.unit;
                    this.selectedItemCost = item.cost || 0;
                    this.purchaseQty = item.rec_qty || 5;
                    this.purchaseTotal = Math.round(this.purchaseQty * (item.cost || 0));
                    this.showPurchase = true;
                    this.showOpname = false;
                    this.showAddModal = false;
                    this.showEditModal = false;
                },

                async submitPurchase() {
                    const id = this.selectedItemId;
                    if (!id) return;
                    try {
                        const res = await fetch(@js(route('inventory.purchase')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                ingredient_id: id,
                                quantity: parseFloat(this.purchaseQty) || 1,
                                total_price: parseFloat(this.purchaseTotal) || 0
                            })
                        });
                        const data = await res.json();
                        if (data && data.success && this.items[id]) {
                            this.items[id].stock = parseFloat(data.current_stock) || 0;
                            this.items[id].status = data.stock_status;
                            this.items[id].badge_text = data.badge_text;
                            this.items[id].est_sold = 0;
                            this.showPurchase = false;
                            this.showToast('Stok Masuk Tersimpan', data.message);
                        } else {
                            this.showToast('Gagal Menyimpan', (data && data.message) || 'Pastikan jumlah kulakan benar.');
                        }
                    } catch (e) {
                        this.showToast('Gagal Menyimpan', 'Periksa koneksi Anda dan coba lagi.');
                    }
                },

                openOpname(id) {
                    const item = this.items[id];
                    if (!item) return;
                    this.selectedItemId = item.id;
                    this.selectedItemName = item.name;
                    this.currentStock = item.stock;
                    this.selectedItemUnit = item.unit;
                    this.selectedItemCost = item.cost || 0;
                    this.selectedItemMinStock = item.min_stock || 3;
                    this.opnameActual = item.stock;
                    this.opnameReason = 'Penyesuaian stok fisik di rak';
                    this.showOpname = true;
                    this.showPurchase = false;
                    this.showAddModal = false;
                    this.showEditModal = false;
                },

                async submitOpname() {
                    const id = this.selectedItemId;
                    if (!id) return;
                    try {
                        const res = await fetch(@js(route('inventory.opname')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                ingredient_id: id,
                                actual_stock: parseFloat(this.opnameActual) || 0,
                                reason: this.opnameReason || 'Penyesuaian stok fisik di rak'
                            })
                        });
                        const data = await res.json();
                        if (data && data.success && this.items[id]) {
                            this.items[id].stock = parseFloat(data.current_stock) || 0;
                            this.items[id].status = data.stock_status;
                            this.items[id].badge_text = data.badge_text;
                            this.showOpname = false;
                            this.showToast(`${this.items[id].name} -> ${String(data.stock_status).toUpperCase()}`, data.message);
                        } else {
                            this.showToast('Gagal Menyimpan', (data && data.message) || 'Pastikan angka stok valid.');
                        }
                    } catch (e) {
                        this.showToast('Gagal Menyimpan', 'Periksa koneksi Anda dan coba lagi.');
                    }
                },

                openEditModal(id) {
                    const item = this.items[id];
                    if (!item) return;
                    this.editForm = {
                        id: item.id,
                        name: item.name,
                        kategori: item.kategori,
                        unit: item.unit,
                        current_stock: item.stock,
                        min_stock: item.min_stock || 3,
                        cost_per_unit: item.cost || 0,
                        price_eceran: item.price_eceran || 0,
                        image_url: item.image_url || @js($defaultFallbackImg)
                    };
                    this.editImagePreview = null;
                    this.showEditModal = true;
                    this.showOpname = false;
                    this.showPurchase = false;
                    this.showAddModal = false;
                },

                handleAddImagePreview(event) {
                    const file = event.target.files && event.target.files[0];
                    if (file) {
                        this.addImagePreview = URL.createObjectURL(file);
                    }
                },

                handleEditImagePreview(event) {
                    const file = event.target.files && event.target.files[0];
                    if (file) {
                        this.editImagePreview = URL.createObjectURL(file);
                    }
                },

                async submitEdit(event) {
                    const id = this.editForm.id;
                    if (!id) return;
                    const formEl = event.target;
                    const formData = new FormData(formEl);

                    try {
                        const res = await fetch(`/inventory/materials/${id}/update`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: formData
                        });
                        const data = await res.json();
                        if (data && data.success && this.items[id]) {
                            this.items[id].name = data.name;
                            this.items[id].kategori = data.kategori;
                            this.items[id].unit = data.unit;
                            this.items[id].stock = parseFloat(data.current_stock) || 0;
                            this.items[id].min_stock = parseFloat(data.min_stock) || 0;
                            this.items[id].status = data.stock_status;
                            this.items[id].badge_text = data.badge_text;
                            if (data.image_url) {
                                this.items[id].image_url = data.image_url + '?t=' + Date.now();
                            }
                            this.showEditModal = false;
                            this.showToast('Berhasil Diperbarui', data.message);
                        } else {
                            this.showToast('Gagal Memperbarui', (data && data.message) || 'Pastikan foto maks 2MB (JPG/PNG/WEBP).');
                        }
                    } catch (e) {
                        this.showToast('Gagal Menyimpan', 'Periksa ukuran file foto (maks 2MB) dan coba lagi.');
                    }
                }
            };
        };

        document.addEventListener('alpine:init', () => {
            Alpine.data('stokCepatHub', window.stokCepatHub);
        });
    </script>
</x-app-layout>
