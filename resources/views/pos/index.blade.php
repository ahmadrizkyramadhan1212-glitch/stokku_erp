<x-app-layout>
    @php
        $user = Auth::user();
        $isOwner = ($user->role ?? 'admin') === 'admin';
        $staffName = $user->name ?? 'Karyawan Gudang';
        $posItemsJson = $menus->map(function ($m) use ($isOwner) {
            return [
                'id'               => $m->id,
                'name'             => $m->name,
                'kategori'         => $m->kategori ?? ($m->category ?: 'Sayur Segar'),
                'sale_unit'        => $m->sale_unit ?: 'pcs',
                'stock'            => (int) ($m->effective_stock ?? $m->current_stock),
                'stock_status'     => $m->stock_status_code ?? 'aman',
                'is_fifo'          => (bool) $m->is_fifo,
                'fifo_note'        => $m->fifo_note,
                'price_eceran'     => $isOwner ? (float) $m->price_eceran : 0,
                'price_reseller'   => $isOwner ? (float) ($m->price_reseller ?: $m->price_eceran) : 0,
                'price_agen'       => $isOwner ? (float) ($m->price_agen ?: $m->price_eceran) : 0,
                'ingredient_stock' => (float) ($m->ingredient_stock ?? 0),
                'ingredient_unit'  => $m->ingredient_unit ?? ($m->sale_unit ?: 'pcs'),
            ];
        })->values();
    @endphp

    <div x-data="stokkuBarangKeluarApp(@js($posItemsJson), @js($isOwner))" class="flex flex-col gap-4 pb-32">

        <!-- ===================================================================
             FLASH ALERTS & SUMMARY BANNER
             =================================================================== -->
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-[#DCFCE7] border border-[#10B981]/30 text-[#166534] flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-[22px] text-[#15803D] shrink-0 mt-0.5">check_circle</span>
                    <div>
                        <p class="text-xs font-extrabold">{{ session('success') }}</p>
                        @if(session('last_receipt'))
                            <p class="text-[11px] font-medium text-[#15803D] mt-0.5">
                                Rincian Fisik: {{ session('last_receipt')['items_summary'] }} • Tercatat pukul {{ session('last_receipt')['time'] }} WIB
                            </p>
                        @endif
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-lg bg-white/80 text-[#166534] text-[10px] font-extrabold uppercase tracking-wider shrink-0 self-start sm:self-center">
                    Stok Gudang Otomatis Berkurang
                </span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-[#FEE2E2] border border-[#EF4444]/30 text-[#991B1B] flex items-center gap-3 text-xs font-bold shadow-xs">
                <span class="material-symbols-outlined text-[20px] shrink-0">error</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- ===================================================================
             CLEAN WHITE HEADER STRIP: PENCATATAN BARANG KELUAR & KONTROL FIFO
             =================================================================== -->
        <section class="relative overflow-hidden rounded-2xl bg-white p-4 sm:p-5 text-[#0F172A] shadow-xs border border-slate-200/80 dark:text-slate-100">
            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#0D9488]/10 text-[#0D9488] flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[22px]">outbox</span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h1 class="text-lg sm:text-xl font-extrabold tracking-tight text-[#0F172A] dark:text-slate-100">Pencatatan Barang Keluar &amp; FIFO</h1>
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-extrabold uppercase tracking-wider">
                                Rekap Pasca Jam Sibuk &amp; Prioritas FIFO
                            </span>
                        </div>
                        <p class="text-xs text-[#64748B] max-w-2xl">
                            Catat jumlah fisik komoditas yang keluar/laku pasca jam sibuk warung (<strong>07:00–11:00</strong> &amp; <strong>14:00–17:30 WIB</strong>) tanpa repot hitung uang. Utamakan komoditas bertanda <strong class="text-amber-700">Wajib FIFO</strong>.
                        </p>
                    </div>
                </div>

                <!-- Today Physical Outflow Counter Pills -->
                <div class="grid grid-cols-2 sm:flex items-center gap-3 shrink-0">
                    <div class="bg-[#F8FAFC] border border-slate-200/80 rounded-xl px-3.5 py-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#64748B] block">Rekap Hari Ini</span>
                        <span class="text-sm sm:text-base font-extrabold text-[#0F172A] tabular-nums dark:text-slate-100">{{ $todaySalesCount }} Catatan</span>
                    </div>
                    <div class="bg-[#F0FDFA] border border-slate-200 rounded-xl px-3.5 py-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#0F766E] block">Total Fisik Keluar</span>
                        <span class="text-sm sm:text-base font-extrabold tabular-nums text-[#0D9488]">{{ $todayOutQty ?? 0 }} Satuan</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             FIFO PRIORITY REMINDER BANNER FOR STAFF
             =================================================================== -->
        @if($fifoMenus->isNotEmpty())
            <section class="rounded-2xl bg-[#FFFBEB] border-2 border-[#F59E0B]/50 p-4 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#FEF3C7] text-[#B45309] flex items-center justify-center shrink-0 font-bold">
                        <span class="material-symbols-outlined text-[22px]">priority_high</span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-extrabold text-[#92400E] uppercase tracking-wider">Instruksi Operasional • Prioritas Keluar FIFO</span>
                            <span class="px-2 py-0.5 rounded-full bg-[#F59E0B] text-white text-[10px] font-extrabold">{{ $fifoMenus->count() }} Komoditas Sisa Kemarin</span>
                        </div>
                        <p class="text-xs text-[#78350F] mt-0.5 font-medium">
                            Wajib dikupas/dikemas dan dikeluarkan lebih dulu sebelum stok baru:
                            @foreach($fifoMenus as $fm)
                                <strong class="underline decoration-amber-500">{{ $fm->name }}</strong> (Sisa: {{ $fm->effective_stock }} {{ $fm->sale_unit }})@if(!$loop->last), @endif
                            @endforeach
                            agar tidak membusuk di gudang.
                        </p>
                    </div>
                </div>
                <button type="button"
                        @click="selectedCategory = 'FIFO'"
                        class="px-3.5 py-2 rounded-xl bg-[#D97706] hover:bg-[#B45309] text-white text-xs font-extrabold shrink-0 transition active:scale-95 cursor-pointer self-start sm:self-center">
                    Tampilkan Barang FIFO
                </button>
            </section>
        @endif

        <!-- ===================================================================
             MAIN DUAL-COLUMN LAYOUT (Left: Catalog, Right: Outgoing Stock Recap)
             =================================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

            <!-- LEFT COLUMN: OUTGOING CHANNEL SELECTOR & COMMODITY CATALOG -->
            <div class="lg:col-span-8 flex flex-col gap-4">

                <!-- KATEGORI SALURAN BARANG KELUAR (TANPA PEMBAYARAN TUNAI/QRIS) -->
                <section class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-2.5">
                        <div>
                            <span class="text-[11px] font-extrabold uppercase tracking-wider text-[#64748B]">Kategori Saluran Barang Keluar</span>
                            <p class="text-[11px] text-[#64748B]">Pilih jenis pengeluaran barang untuk sinkronisasi otomatis volume kelarisan SPK SAW (C3) &amp; Matriks BCG</p>
                        </div>
                        <span class="text-xs font-extrabold text-[#0D9488]" x-text="buyerLabel()"></span>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <!-- Pembeli Eceran / Harian -->
                        <button type="button"
                                @click="setBuyerType('Eceran')"
                                :class="buyerType === 'Eceran'
                                    ? 'border-2 border-[#0D9488] bg-[#0D9488]/10 text-[#0D9488] shadow-xs'
                                    : 'border border-slate-200 bg-[#F8FAFC] text-[#475569] hover:border-slate-300'"
                                class="flex flex-col items-center justify-center py-2.5 px-2 rounded-xl transition active:scale-95 cursor-pointer">
                            <span class="material-symbols-outlined text-[20px]">shopping_bag</span>
                            <span class="text-xs font-extrabold mt-0.5">Eceran Harian</span>
                            <span class="text-[10px] font-semibold opacity-80">Kantong Kecil / Ons</span>
                        </button>

                        <!-- Langganan Warung / Reseller -->
                        <button type="button"
                                @click="setBuyerType('Reseller')"
                                :class="buyerType === 'Reseller'
                                    ? 'border-2 border-[#0D9488] bg-[#0D9488]/10 text-[#0D9488] shadow-xs'
                                    : 'border border-slate-200 bg-[#F8FAFC] text-[#475569] hover:border-slate-300'"
                                class="flex flex-col items-center justify-center py-2.5 px-2 rounded-xl transition active:scale-95 cursor-pointer">
                            <span class="material-symbols-outlined text-[20px]">store</span>
                            <span class="text-xs font-extrabold mt-0.5">Langganan / Reseller</span>
                            <span class="text-[10px] font-semibold opacity-80">Warung Sayur / Bakso</span>
                        </button>

                        <!-- Partai Besar / Agen -->
                        <button type="button"
                                @click="setBuyerType('Agen')"
                                :class="buyerType === 'Agen'
                                    ? 'border-2 border-[#0D9488] bg-[#0D9488]/10 text-[#0D9488] shadow-xs'
                                    : 'border border-slate-200 bg-[#F8FAFC] text-[#475569] hover:border-slate-300'"
                                class="flex flex-col items-center justify-center py-2.5 px-2 rounded-xl transition active:scale-95 cursor-pointer">
                            <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                            <span class="text-xs font-extrabold mt-0.5">Partai Grosir</span>
                            <span class="text-[10px] font-semibold opacity-80">Karungan / Ikat Besar</span>
                        </button>
                    </div>
                </section>

                <!-- SEARCH BAR & CATEGORY PILLS -->
                <section class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80 flex flex-col gap-3">
                    <div class="relative">
                        <span class="material-symbols-outlined text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 text-[20px]">search</span>
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="Cari komoditas warung (Cabai, Jagung Manis, Daun Jeruk, Bawang, Beras, Minyak)..."
                               class="w-full bg-[#F8FAFC] border border-slate-200/90 rounded-xl pl-10 pr-9 py-2.5 text-xs font-medium text-[#0F172A] placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0D9488] focus:bg-white transition dark:text-slate-100">
                        <button type="button"
                                x-show="searchQuery.length > 0"
                                @click="searchQuery = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-outlined text-[18px]">close</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-0.5">
                        <button type="button"
                                @click="selectedCategory = 'ALL'"
                                :class="selectedCategory === 'ALL' ? 'bg-[#0D9488] text-white font-bold shadow-xs' : 'bg-slate-100 text-[#475569] font-semibold hover:bg-slate-200'"
                                class="px-3 py-1.5 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                            Semua (<span x-text="items.length"></span>)
                        </button>
                        <button type="button"
                                @click="selectedCategory = 'FIFO'"
                                :class="selectedCategory === 'FIFO' ? 'bg-[#D97706] text-white font-bold shadow-xs' : 'bg-[#FEF3C7] text-[#92400E] font-bold hover:bg-amber-200'"
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                            <span class="material-symbols-outlined text-[14px]">priority_high</span>
                            <span>Wajib FIFO</span>
                        </button>
                        <button type="button"
                                @click="selectedCategory = 'Sayur & Buah Segar'"
                                :class="selectedCategory === 'Sayur & Buah Segar' ? 'bg-[#0D9488] text-white font-bold shadow-xs' : 'bg-slate-100 text-[#475569] font-semibold hover:bg-slate-200'"
                                class="px-3 py-1.5 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                            A. Sayur &amp; Buah
                        </button>
                        <button type="button"
                                @click="selectedCategory = 'Bumbu Dapur & Rempah'"
                                :class="selectedCategory === 'Bumbu Dapur & Rempah' ? 'bg-[#0D9488] text-white font-bold shadow-xs' : 'bg-slate-100 text-[#475569] font-semibold hover:bg-slate-200'"
                                class="px-3 py-1.5 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                            B. Bumbu Dapur
                        </button>
                        <button type="button"
                                @click="selectedCategory = 'Minyak, Saus & Kecap'"
                                :class="selectedCategory === 'Minyak, Saus & Kecap' ? 'bg-[#0D9488] text-white font-bold shadow-xs' : 'bg-slate-100 text-[#475569] font-semibold hover:bg-slate-200'"
                                class="px-3 py-1.5 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                            C. Minyak &amp; Kecap
                        </button>
                        <button type="button"
                                @click="selectedCategory = 'Sembako & Bahan Pokok'"
                                :class="selectedCategory === 'Sembako & Bahan Pokok' ? 'bg-[#0D9488] text-white font-bold shadow-xs' : 'bg-slate-100 text-[#475569] font-semibold hover:bg-slate-200'"
                                class="px-3 py-1.5 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                            D-E. Sembako &amp; Mie
                        </button>
                        <button type="button"
                                @click="selectedCategory = 'Lauk, Minuman & Rumah Tangga'"
                                :class="selectedCategory === 'Lauk, Minuman & Rumah Tangga' ? 'bg-[#0D9488] text-white font-bold shadow-xs' : 'bg-slate-100 text-[#475569] font-semibold hover:bg-slate-200'"
                                class="px-3 py-1.5 rounded-full text-xs whitespace-nowrap transition cursor-pointer">
                            F-I. Lainnya
                        </button>
                    </div>
                </section>

                <!-- DAFTAR KOMODITAS WARUNG (FIFO SORTED AT TOP) -->
                <section class="flex flex-col gap-3">
                    <div class="flex items-center justify-between px-1">
                        <h2 class="text-sm font-bold text-[#0F172A] dark:text-slate-100">Daftar Komoditas Siap Keluar</h2>
                        <span class="text-[11px] font-semibold text-[#64748B]">
                            Menampilkan <strong class="text-[#0F172A] dark:text-slate-100" x-text="filteredItems().length"></strong> komoditas
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="item in filteredItems()" :key="item.id">
                            <article
                                :class="[
                                    item.stock <= 0 ? 'opacity-65 bg-slate-50 border-slate-200' : 'bg-white hover:border-[#0D9488]/50',
                                    item.is_fifo && item.stock > 0 ? 'border-2 border-[#F59E0B] ring-2 ring-[#FEF3C7]' : 'border border-slate-200/80',
                                    getQty(item.id) > 0 ? 'ring-2 ring-[#0D9488] border-[#0D9488]' : ''
                                ]"
                                class="rounded-2xl p-4 shadow-xs transition-all flex flex-col justify-between gap-3">

                                <!-- Top Info -->
                                <div>
                                    <!-- FIFO Priority Banner Inside Card -->
                                    <template x-if="item.is_fifo">
                                        <div class="mb-2.5 px-2.5 py-1.5 rounded-xl bg-[#FEF3C7] border border-[#F59E0B]/40 flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-1.5 text-[#92400E]">
                                                <span class="material-symbols-outlined text-[16px] text-[#D97706]">priority_high</span>
                                                <span class="text-[10px] font-extrabold uppercase tracking-wider">Prioritas FIFO • Sisa Kemarin</span>
                                            </div>
                                            <span class="text-[10px] font-bold text-[#B45309]">Keluarkan Dulu!</span>
                                        </div>
                                    </template>

                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                                <!-- Stock Indicator Badge -->
                                                <span :class="{
                                                        'bg-[#DCFCE7] text-[#166534]': item.stock > 0 && item.stock_status === 'aman',
                                                        'bg-[#FEF3C7] text-[#92400E]': item.stock > 0 && item.stock_status === 'menipis',
                                                        'bg-[#FEE2E2] text-[#991B1B]': item.stock <= 0 || item.stock_status === 'habis'
                                                      }"
                                                      class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase">
                                                    <span x-text="item.stock > 0 ? ('Tersedia: ' + item.stock + ' ' + item.sale_unit) : 'Stok Habis (0)'"></span>
                                                </span>

                                                <span class="text-[10px] font-semibold text-[#64748B]" x-text="'• ' + item.kategori"></span>
                                            </div>

                                            <h3 class="text-sm font-extrabold text-[#0F172A] truncate dark:text-slate-100" x-text="item.name"></h3>

                                            <!-- Sub-note for FIFO -->
                                            <template x-if="item.is_fifo && item.fifo_note">
                                                <p class="text-[11px] text-[#B45309] font-medium mt-0.5 line-clamp-1" x-text="item.fifo_note"></p>
                                            </template>
                                        </div>

                                        <!-- Selected Qty Badge -->
                                        <template x-if="getQty(item.id) > 0">
                                            <span class="px-2.5 py-1 rounded-full bg-[#0D9488] text-white text-[10px] font-extrabold shrink-0">
                                                <span x-text="'Keluar: ' + getQty(item.id) + ' ' + item.sale_unit"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Bottom Unit Info & Stepper Controls -->
                                <div class="pt-2.5 border-t border-slate-100 flex items-end justify-between gap-2">
                                    <div>
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#64748B] block" x-text="'Satuan Keluar (' + buyerType + ')'"></span>
                                        <div class="flex items-baseline gap-1 mt-0.5">
                                            <template x-if="isOwner">
                                                <span class="text-sm font-extrabold text-[#0D9488] tabular-nums" x-text="'Per ' + item.sale_unit + ' • ' + formatRupiah(getPrice(item))"></span>
                                            </template>
                                            <template x-if="!isOwner">
                                                <span class="text-sm font-extrabold text-[#0D9488]" x-text="'Per ' + item.sale_unit + ' (Gudang: ' + item.ingredient_stock + ' ' + item.ingredient_unit + ')'"></span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex items-center gap-1.5">
                                        <template x-if="item.stock <= 0">
                                            <a href="{{ route('inventory.materials') }}"
                                               class="inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-[#FEE2E2] text-[#991B1B] text-[11px] font-extrabold hover:bg-red-200 transition">
                                                <span class="material-symbols-outlined text-[15px]">inventory_2</span>
                                                <span>Stok Kosong</span>
                                            </a>
                                        </template>

                                        <template x-if="item.stock > 0">
                                            <div class="flex items-center gap-1.5">
                                                <!-- Stepper [- qty +] -->
                                                <div class="flex items-center bg-[#F8FAFC] border border-slate-200 rounded-xl p-1">
                                                    <button type="button"
                                                            @click="decrementItem(item)"
                                                            class="w-7 h-7 rounded-lg bg-white border border-slate-200/80 flex items-center justify-center text-slate-600 hover:bg-slate-100 active:scale-90 transition cursor-pointer dark:text-slate-400">
                                                        <span class="material-symbols-outlined text-[16px]">remove</span>
                                                    </button>
                                                    <span class="w-8 text-center text-xs font-extrabold text-[#0F172A] tabular-nums dark:text-slate-100" x-text="getQty(item.id)"></span>
                                                    <button type="button"
                                                            @click="incrementItem(item)"
                                                            class="w-7 h-7 rounded-lg bg-[#0D9488]/10 text-[#0D9488] flex items-center justify-center hover:bg-[#0D9488] hover:text-white active:scale-90 transition cursor-pointer">
                                                        <span class="material-symbols-outlined text-[16px]">add</span>
                                                    </button>
                                                </div>

                                                <!-- + Catat Quick Button -->
                                                <button type="button"
                                                        @click="incrementItem(item)"
                                                        class="bg-[#0D9488] hover:bg-[#0F766E] text-white font-bold text-xs px-3 py-2 rounded-xl shadow-xs transition active:scale-95 flex items-center gap-1 cursor-pointer">
                                                    <span class="material-symbols-outlined text-[15px]">outbox</span>
                                                    <span>Catat</span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>
                </section>
            </div>

            <!-- RIGHT COLUMN: DAFTAR REKAP BARANG KELUAR (Sticky on Desktop) -->
            <div class="lg:col-span-4 lg:sticky lg:top-20">
                <section class="bg-white rounded-3xl p-6 shadow-md border border-slate-200/90 flex flex-col gap-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-[#0D9488]/10 text-[#0D9488] flex items-center justify-center">
                                <span class="material-symbols-outlined text-[22px]">fact_check</span>
                            </div>
                            <div>
                                <h2 class="text-sm font-extrabold text-[#0F172A] dark:text-slate-100">Daftar Rekap Barang Keluar</h2>
                                <p class="text-[11px] text-[#64748B]">
                                    Saluran: <span class="font-bold text-[#0D9488]" x-text="buyerLabel()"></span>
                                </p>
                            </div>
                        </div>

                        <button type="button"
                                x-show="cart.length > 0"
                                @click="clearCart()"
                                class="text-[11px] font-bold text-[#EF4444] hover:underline cursor-pointer">
                            Reset
                        </button>
                    </div>

                    <!-- Empty State -->
                    <template x-if="cart.length === 0">
                        <div class="py-10 px-4 text-center rounded-2xl bg-[#F8FAFC] border border-dashed border-slate-200">
                            <span class="material-symbols-outlined text-[40px] text-slate-300">inventory</span>
                            <p class="text-xs font-bold text-[#475569] mt-2">Belum Ada Barang Keluar Dipilih</p>
                            <p class="text-[11px] text-[#64748B] mt-1">
                                Klik tombol <strong>+ Catat</strong> pada kartu komoditas di samping untuk mencatat jumlah fisik barang yang keluar/habis terjual.
                            </p>
                        </div>
                    </template>

                    <!-- Selected Outgoing Items List -->
                    <div x-show="cart.length > 0" class="flex flex-col gap-3 max-h-80 overflow-y-auto pr-1">
                        <template x-for="c in cart" :key="c.id">
                            <div class="p-3 rounded-2xl bg-[#F8FAFC] border border-slate-200/70 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs font-extrabold text-[#0F172A] truncate dark:text-slate-100" x-text="c.name"></span>
                                        <template x-if="c.is_fifo">
                                            <span class="px-1.5 py-0.5 rounded bg-[#FEF3C7] text-[#92400E] text-[9px] font-extrabold uppercase">FIFO</span>
                                        </template>
                                    </div>
                                    <p class="text-[11px] text-[#64748B] tabular-nums mt-0.5">
                                        Jumlah fisik keluar: <strong class="text-[#0F172A] dark:text-slate-100" x-text="c.qty + ' ' + c.sale_unit"></strong>
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="px-2.5 py-1 rounded-lg bg-[#0D9488]/10 text-[#0D9488] text-xs font-extrabold tabular-nums" x-text="c.qty + ' ' + c.sale_unit"></span>
                                    <button type="button"
                                            @click="removeFromCart(c.id)"
                                            class="w-7 h-7 rounded-lg bg-[#FEE2E2] text-[#991B1B] flex items-center justify-center hover:bg-[#EF4444] hover:text-white transition cursor-pointer">
                                        <span class="material-symbols-outlined text-[15px]">delete</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Submit Form (No Payment Method Needed) -->
                    <form method="POST" action="{{ route('pos.store') }}" @submit="prepareSubmit($event)" class="pt-3 border-t border-slate-200/80 flex flex-col gap-3">
                        @csrf
                        <input type="hidden" name="buyer_type" :value="buyerType">
                        <input type="hidden" name="cart" :value="JSON.stringify(cart)">

                        <div class="rounded-2xl bg-[#0F172A] text-white p-4 flex flex-col gap-2">
                            <div class="flex items-center justify-between text-xs text-slate-300">
                                <span>Jenis Komoditas Tercatat</span>
                                <span class="font-bold text-[#5EEAD4]" x-text="cart.length + ' Komoditas (' + buyerType + ')'"></span>
                            </div>
                            <div class="flex items-baseline justify-between pt-1.5 border-t border-white/10">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Total Fisik Keluar</span>
                                <span class="text-xl font-extrabold text-white tabular-nums" x-text="totalItems() + ' Satuan'"></span>
                            </div>
                            <template x-if="isOwner">
                                <div class="flex items-center justify-between pt-1 border-t border-white/10 text-[11px] text-teal-200">
                                    <span>Estimasi Omzet (Khusus Owner)</span>
                                    <span class="font-extrabold tabular-nums" x-text="formatRupiah(totalPrice())"></span>
                                </div>
                            </template>
                        </div>

                        <button type="submit"
                                :disabled="cart.length === 0 || isSubmitting"
                                :class="cart.length === 0 ? 'bg-slate-200 text-slate-400 cursor-not-allowed' : 'bg-[#0D9488] hover:bg-[#0F766E] text-white shadow-md shadow-teal-600/20 cursor-pointer active:scale-[0.99]'"
                                class="w-full py-3.5 px-5 rounded-2xl font-extrabold text-sm flex items-center justify-center gap-2 transition">
                            <span class="material-symbols-outlined text-[20px]">check_circle</span>
                            <span x-text="isSubmitting ? 'Menyimpan Catatan...' : 'Simpan Catatan Barang Keluar'"></span>
                        </button>
                    </form>
                </section>
            </div>
        </div>

        <!-- ===================================================================
             FLOATING MOBILE BAR (< lg)
             =================================================================== -->
        <div x-show="cart.length > 0"
             x-transition
             class="lg:hidden fixed bottom-[72px] inset-x-0 z-40 px-4 pointer-events-none">
            <div class="max-w-lg mx-auto bg-[#0F172A] text-white rounded-2xl p-4 shadow-2xl border border-slate-700/80 flex items-center justify-between gap-3 pointer-events-auto">
                <div class="flex flex-col min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-[#10B981]"></span>
                        <span class="text-[11px] font-semibold text-slate-300 truncate">
                            <span x-text="cart.length"></span> Komoditas • Saluran <span x-text="buyerType"></span>
                        </span>
                    </div>
                    <div class="text-base font-extrabold text-white tracking-tight mt-0.5 tabular-nums" x-text="'Total Keluar: ' + totalItems() + ' Satuan Fisik'"></div>
                </div>

                <form method="POST" action="{{ route('pos.store') }}" @submit="prepareSubmit($event)">
                    @csrf
                    <input type="hidden" name="buyer_type" :value="buyerType">
                    <input type="hidden" name="cart" :value="JSON.stringify(cart)">

                    <button type="submit"
                            :disabled="isSubmitting"
                            class="bg-[#10B981] hover:bg-[#059669] text-white font-extrabold text-xs px-4 py-3 rounded-xl shadow-md flex items-center gap-1.5 transition active:scale-95 shrink-0 cursor-pointer">
                        <span>Simpan Stok Keluar</span>
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    </button>
                </form>
            </div>
        </div>

    </div>

    <script>
        function stokkuBarangKeluarApp(initialItems, isOwnerFlag) {
            return {
                items: initialItems || [],
                isOwner: Boolean(isOwnerFlag),
                buyerType: 'Eceran',
                searchQuery: '',
                selectedCategory: 'ALL',
                cart: [],
                isSubmitting: false,

                buyerLabel() {
                    if (this.buyerType === 'Reseller') return 'Langganan Warung / Reseller';
                    if (this.buyerType === 'Agen') return 'Partai Grosir / Agen';
                    return 'Eceran Harian';
                },

                setBuyerType(type) {
                    this.buyerType = type;
                    this.cart = this.cart.map(c => {
                        const found = this.items.find(i => i.id === c.id);
                        return {
                            ...c,
                            price: found ? this.getPrice(found) : c.price
                        };
                    });
                },

                getPrice(item) {
                    if (this.buyerType === 'Reseller') return Number(item.price_reseller || item.price_eceran);
                    if (this.buyerType === 'Agen') return Number(item.price_agen || item.price_eceran);
                    return Number(item.price_eceran || 0);
                },

                filteredItems() {
                    return this.items.filter(item => {
                        const matchCategory =
                            this.selectedCategory === 'ALL' ||
                            (this.selectedCategory === 'FIFO' && item.is_fifo) ||
                            item.kategori === this.selectedCategory;

                        const q = this.searchQuery.trim().toLowerCase();
                        const matchQuery = !q ||
                            item.name.toLowerCase().includes(q) ||
                            (item.kategori && item.kategori.toLowerCase().includes(q));

                        return matchCategory && matchQuery;
                    });
                },

                getQty(itemId) {
                    const found = this.cart.find(c => c.id === itemId);
                    return found ? found.qty : 0;
                },

                incrementItem(item) {
                    if (item.stock <= 0) return;

                    const existing = this.cart.find(c => c.id === item.id);
                    if (existing) {
                        if (existing.qty < item.stock) {
                            existing.qty += 1;
                            existing.price = this.getPrice(item);
                        }
                    } else {
                        this.cart.push({
                            id: item.id,
                            name: item.name,
                            qty: 1,
                            price: this.getPrice(item),
                            sale_unit: item.sale_unit,
                            is_fifo: item.is_fifo
                        });
                    }
                },

                decrementItem(item) {
                    const idx = this.cart.findIndex(c => c.id === item.id);
                    if (idx === -1) return;

                    if (this.cart[idx].qty > 1) {
                        this.cart[idx].qty -= 1;
                    } else {
                        this.cart.splice(idx, 1);
                    }
                },

                removeFromCart(itemId) {
                    this.cart = this.cart.filter(c => c.id !== itemId);
                },

                clearCart() {
                    this.cart = [];
                },

                totalItems() {
                    return this.cart.reduce((acc, c) => acc + Number(c.qty || 0), 0);
                },

                totalPrice() {
                    return this.cart.reduce((acc, c) => acc + (Number(c.qty || 0) * Number(c.price || 0)), 0);
                },

                formatRupiah(num) {
                    return 'Rp ' + Math.round(Number(num || 0)).toLocaleString('id-ID');
                },

                prepareSubmit(e) {
                    if (this.cart.length === 0) {
                        e.preventDefault();
                        return;
                    }
                    this.isSubmitting = true;
                }
            };
        }
    </script>
</x-app-layout>
