<x-app-layout>
    <div x-data="productionManager()" class="flex flex-col gap-4 pb-24">

        {{-- Header --}}
        <section class="bg-white p-4 sm:p-5 rounded-2xl shadow-xs border border-slate-200/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('inventory.index') }}"
                   aria-label="Kembali"
                   class="w-10 h-10 rounded-full bg-[#F1F5F9] hover:bg-slate-200/80 active:scale-95 transition flex items-center justify-center text-[#0F172A] shrink-0 dark:text-slate-100">
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                </a>
                <div class="flex flex-col min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="font-headline-sm sm:font-headline-md text-lg sm:text-xl text-[#0F172A] font-bold truncate dark:text-slate-100">
                            Kemas Ulang Eceran &amp; Stok Etalase Siap Jual
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full bg-[#0D9488]/10 text-[#0D9488] text-[10px] font-bold uppercase tracking-wider">
                            Warung Sayur Adijaya
                        </span>
                    </div>
                    <p class="font-body-sm text-xs text-[#64748B] truncate">
                        Kemas stok curah gudang ke satuan jual eceran warung &amp; potong stok gudang otomatis
                    </p>
                </div>
            </div>

            <div class="relative w-full sm:w-72">
                <span class="material-symbols-outlined text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 text-[18px]">search</span>
                <input type="text"
                       x-model="searchQuery"
                       placeholder="Cari barang etalase..."
                       class="w-full bg-[#F8FAFC] border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-xs font-semibold text-[#0F172A] placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0D9488] focus:bg-white dark:text-slate-100">
            </div>
        </section>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="p-4 bg-[#ECFDF5] border border-emerald-200 text-[#065F46] rounded-2xl text-xs sm:text-sm font-semibold flex items-center gap-3 shadow-xs">
                <span class="material-symbols-outlined text-[20px] text-[#10B981]">check_circle</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 bg-[#FEE2E2] border border-red-200 text-[#991B1B] rounded-2xl text-xs sm:text-sm font-semibold flex items-center gap-3 shadow-xs">
                <span class="material-symbols-outlined text-[20px] text-[#EF4444]">error</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Info Banner & Filter Tab Kategori --}}
        @php
            $categoryGroupsMeta = [
                'Sayur & Buah Segar' => [
                    'code'  => 'A',
                    'tab'   => 'A. Sayur & Buah',
                    'title' => 'Kategori A — Sayur & Buah Segar',
                    'desc'  => 'Komoditas segar cepat busuk (Perishability = 3)',
                ],
                'Bumbu Dapur & Rempah' => [
                    'code'  => 'B',
                    'tab'   => 'B. Bumbu Dapur',
                    'title' => 'Kategori B — Bumbu Dapur & Rempah',
                    'desc'  => 'Bawang, cabai, rimpang, daun rempah & bumbu kemasan',
                ],
                'Minyak, Saus & Kecap' => [
                    'code'  => 'C',
                    'tab'   => 'C. Minyak & Kecap',
                    'title' => 'Kategori C — Minyak Goreng, Saus & Kecap',
                    'desc'  => 'Minyak goreng pouch/botol, saus, kecap & cuka',
                ],
                'Sembako & Bahan Pokok' => [
                    'code'  => 'D-E',
                    'tab'   => 'D-E. Sembako & Mie',
                    'title' => 'Kategori D-E — Sembako, Tepung & Bahan Pokok',
                    'desc'  => 'Beras, telur, mie, tepung, gula & penyedap',
                ],
                'Lauk, Minuman & Rumah Tangga' => [
                    'code'  => 'F-I',
                    'tab'   => 'F-I. Lainnya',
                    'title' => 'Kategori F-I — Lauk, Minuman, Es & Rumah Tangga',
                    'desc'  => 'Ikan asin, frozen, air galon/dus, es batu, sabun & LPG',
                ],
            ];
        @endphp

        <div class="bg-[#EFF6FF] border border-blue-200/80 rounded-2xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-[#2563EB] text-[20px] shrink-0 mt-0.5">package_2</span>
            <p class="text-xs text-[#1E3A8A] font-medium leading-relaxed">
                Setiap satuan barang yang dikemas ulang ke etalase akan <strong>langsung memotong stok curah di gudang</strong> sesuai rasio konversi satuan warung (misal: kupas jagung sisa kemarin ke kantong plastik eceran atau bongkar dus minyak ke pouch).
            </p>
        </div>

        {{-- Filter Tab Kategori Bar --}}
        <div class="bg-white rounded-2xl p-2.5 shadow-xs border border-slate-200/70 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-0.5">
                <button type="button"
                        @click="selectedCategory = 'ALL'"
                        :class="selectedCategory === 'ALL' ? 'bg-[#0D9488] text-white shadow-xs' : 'bg-[#F1F5F9] text-[#475569] hover:text-[#0F172A]'"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer">
                    Semua ({{ $menus->count() }})
                </button>
                <button type="button"
                        @click="selectedCategory = 'FIFO'"
                        :class="selectedCategory === 'FIFO' ? 'bg-amber-500 text-white shadow-xs' : 'bg-amber-50 text-amber-800 hover:bg-amber-100'"
                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer">
                    <span class="material-symbols-outlined text-[14px]">priority_high</span>
                    <span>Wajib FIFO</span>
                </button>
                @foreach($categoryGroupsMeta as $catKey => $meta)
                    @php
                        $catCount = $menus->filter(fn($m) => ($m->category ?: $m->recipe?->items?->first()?->ingredient?->kategori) === $catKey)->count();
                    @endphp
                    <button type="button"
                            @click="selectedCategory = @js($catKey)"
                            :class="selectedCategory === @js($catKey) ? 'bg-[#0D9488] text-white shadow-xs' : 'bg-[#F1F5F9] text-[#475569] hover:text-[#0F172A]'"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer">
                        {{ $meta['tab'] }} ({{ $catCount }})
                    </button>
                @endforeach
            </div>

            <div x-show="selectedCategory === 'ALL' && searchQuery.trim() === ''" class="flex items-center gap-1.5 shrink-0 self-end sm:self-center">
                <button type="button"
                        @click="expandAll(true)"
                        class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-[#475569] text-[11px] font-bold transition cursor-pointer">
                    Buka Semua
                </button>
                <button type="button"
                        @click="expandAll(false)"
                        class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-[#475569] text-[11px] font-bold transition cursor-pointer">
                    Ringkas
                </button>
            </div>
        </div>

        {{-- Commodity Etalase Cards Grouped by Category Accordion --}}
        <div class="flex flex-col gap-3">
            @foreach($categoryGroupsMeta as $catKey => $meta)
                @php
                    $groupMenus = $menus->filter(function ($m) use ($catKey) {
                        $cat = $m->category ?: ($m->recipe?->items?->first()?->ingredient?->kategori ?? '');
                        return $cat === $catKey;
                    })->values();
                @endphp

                @if($groupMenus->isNotEmpty())
                    <div x-show="selectedCategory === 'ALL' || selectedCategory === 'FIFO' || selectedCategory === @js($catKey)"
                         class="bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">

                        <button type="button"
                                @click="toggleSection(@js($catKey))"
                                class="w-full p-4 sm:px-5 bg-[#F8FAFC] hover:bg-slate-100/80 flex items-center justify-between gap-3 text-left transition cursor-pointer border-b border-slate-200/70">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-[#0D9488]/10 text-[#0D9488] flex items-center justify-center shrink-0 font-extrabold text-xs">
                                    {{ $meta['code'] }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h2 class="text-sm sm:text-base font-extrabold text-[#0F172A] truncate dark:text-slate-100">{{ $meta['title'] }}</h2>
                                        <span class="px-2.5 py-0.5 rounded-full bg-slate-200/80 text-[#334155] text-[11px] font-bold">
                                            {{ $groupMenus->count() }} Item Etalase
                                        </span>
                                    </div>
                                    <p class="text-xs text-[#64748B] truncate mt-0.5">{{ $meta['desc'] }}</p>
                                </div>
                            </div>

                            <span class="material-symbols-outlined text-[22px] text-[#64748B] transition-transform duration-200 shrink-0"
                                  :class="isSectionOpen(@js($catKey)) ? 'rotate-180 text-[#0D9488]' : ''">
                                expand_more
                            </span>
                        </button>

                        <div x-show="isSectionOpen(@js($catKey))" x-transition class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3 bg-slate-50/40">
                            @foreach($groupMenus as $menu)
                                @php
                                    $capacity = $menu->production_capacity;
                                    $capColor = $capacity > 0 ? 'text-[#059669]' : 'text-[#DC2626]';
                                    $firstIng = $menu->recipe?->items?->first()?->ingredient;
                                    $saleUnit = $menu->sale_unit ?: ($firstIng?->unit ?? 'satuan');
                                    $gudangUnit = $firstIng?->unit ?? $saleUnit;
                                    $gudangStock = $firstIng ? (float) $firstIng->current_stock : 0;
                                    $isFifo = (bool) ($firstIng?->is_fifo ?? false);
                                    $catLabel = $menu->category ?: ($firstIng?->kategori ?? 'Komoditas Warung');
                                @endphp
                                <div x-show="matchesSearch(@js($menu->name), @js($catLabel), {{ $isFifo ? 'true' : 'false' }}, @js($saleUnit))"
                                     class="bg-white rounded-2xl p-4 shadow-xs border {{ $isFifo ? 'border-amber-400 ring-1 ring-amber-200' : 'border-slate-200/80' }} flex flex-col justify-between gap-3">
                                    <div>
                                        <div class="flex items-start justify-between gap-2 mb-2">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <h4 class="font-bold text-[#0F172A] text-sm sm:text-base truncate dark:text-slate-100">{{ $menu->name }}</h4>
                                                    @if($isFifo)
                                                        <span class="px-2 py-0.5 rounded-full bg-amber-500 text-white text-[10px] font-bold uppercase tracking-wider">
                                                            Wajib FIFO
                                                        </span>
                                                    @endif
                                                </div>
                                                <span class="inline-block mt-0.5 px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-semibold dark:text-slate-400">
                                                    {{ $catLabel }} • Satuan Jual: {{ $saleUnit }}
                                                </span>
                                            </div>

                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold shrink-0 {{ $menu->current_stock > 0 ? 'bg-[#ECFDF5] text-[#059669] border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $menu->current_stock }} {{ $saleUnit }}
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 p-2.5 rounded-xl bg-[#F8FAFC] border border-slate-100 text-xs">
                                            <div>
                                                <span class="text-[10px] font-semibold text-[#64748B] block">Stok Etalase Siap Jual:</span>
                                                <span class="font-extrabold {{ $menu->current_stock > 0 ? 'text-[#0D9488]' : 'text-[#0F172A]' }} text-sm tabular-nums">
                                                    {{ $menu->current_stock }} {{ $saleUnit }}
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-[10px] font-semibold text-[#64748B] block">Stok Gudang Tersedia:</span>
                                                <span class="font-extrabold {{ $capColor }} text-sm tabular-nums">
                                                    {{ $capacity }} {{ $saleUnit }}
                                                </span>
                                                @if($firstIng && $gudangUnit !== $saleUnit)
                                                    <span class="text-[10px] text-slate-500 block dark:text-slate-400">({{ $gudangStock }} {{ $gudangUnit }} di gudang)</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                                        <a href="{{ route('recipe.index') }}"
                                           class="flex-1 bg-slate-100 hover:bg-slate-200 text-[#475569] py-2 px-2.5 rounded-xl text-[11px] font-bold flex items-center justify-center gap-1 transition-colors">
                                            <span class="material-symbols-outlined text-[15px]">inventory_2</span>
                                            <span>Detail Kemasan</span>
                                        </a>
                                        <button type="button"
                                            @click="openProduction({{ $menu->id }}, @js($menu->name), {{ $capacity }}, @js($saleUnit))"
                                            {{ $capacity == 0 ? 'disabled' : '' }}
                                            class="flex-1 {{ $capacity > 0 ? 'bg-[#0D9488] hover:bg-[#0F766E] text-white shadow-xs cursor-pointer' : 'bg-slate-200 text-slate-400 cursor-not-allowed' }} py-2 px-2.5 rounded-xl text-[11px] font-bold flex items-center justify-center gap-1 transition-colors">
                                            <span class="material-symbols-outlined text-[15px]">add_circle</span>
                                            <span>Tambah Stok</span>
                                        </button>
                                        <button type="button"
                                            @click="openOpname({{ $menu->id }}, @js($menu->name), {{ $menu->current_stock }}, @js($saleUnit))"
                                            class="flex-1 bg-amber-50 hover:bg-amber-100 text-amber-800 py-2 px-2.5 rounded-xl text-[11px] font-bold flex items-center justify-center gap-1 transition-colors border border-amber-200/80 cursor-pointer">
                                            <span class="material-symbols-outlined text-[15px]">edit_note</span>
                                            <span>Koreksi Fisik</span>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- ================================ --}}
        {{-- MODAL: KEMAS ULANG / TAMBAH STOK --}}
        {{-- ================================ --}}
        <div
            x-show="showProduction"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs px-4"
            style="display: none;">
            <div class="bg-white w-full max-w-sm rounded-3xl p-6 shadow-2xl relative border border-slate-100" @click.away="showProduction = false">
                <button type="button" @click="showProduction = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 bg-slate-100 rounded-full p-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>

                <h3 class="text-lg font-extrabold text-[#0F172A] mb-0.5 dark:text-slate-100">Kemas Ulang ke Etalase</h3>
                <p class="text-xs text-[#0D9488] font-bold mb-4" x-text="selectedMenuName"></p>

                <form action="{{ route('inventory.produce') }}" method="POST">
                    @csrf
                    <input type="hidden" name="menu_id" x-model="selectedMenuId">

                    <div class="bg-[#ECFDF5] rounded-2xl p-4 mb-4 border border-emerald-200">
                        <p class="text-[11px] text-[#065F46] font-semibold mb-0.5">Stok Gudang Tersedia untuk Dikemas:</p>
                        <p class="text-xl font-extrabold text-[#065F46] tabular-nums">
                            <span x-text="maxCapacity"></span>
                            <span class="text-xs font-bold uppercase" x-text="selectedUnit"></span>
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold text-[#475569] uppercase tracking-wider mb-1">
                                Jumlah (<span x-text="selectedUnit"></span>) yang Dikemas ke Etalase
                            </label>
                            <input
                                type="number"
                                name="quantity"
                                min="1"
                                :max="maxCapacity"
                                required
                                class="w-full text-lg border border-slate-200 bg-[#F8FAFC] rounded-xl focus:ring-2 focus:ring-[#0D9488] font-black p-3 text-center text-[#0D9488]"
                                placeholder="0">
                        </div>
                        <div class="pt-1">
                            <button type="submit"
                                class="w-full bg-[#0D9488] hover:bg-[#0F766E] text-white font-bold py-3 rounded-xl shadow-md shadow-teal-600/20 active:scale-95 transition-transform cursor-pointer">
                                Simpan &amp; Potong Stok Gudang
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- ================================ --}}
        {{-- MODAL: KOREKSI FISIK ETALASE     --}}
        {{-- ================================ --}}
        <div
            x-show="showOpname"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs px-4"
            style="display: none;">
            <div class="bg-white w-full max-w-sm rounded-3xl p-6 shadow-2xl relative border border-slate-100" @click.away="showOpname = false">
                <button type="button" @click="showOpname = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 bg-slate-100 rounded-full p-1.5 cursor-pointer">
                    <span class="material-symbols-outlined text-[18px]">close</span>
                </button>

                <h3 class="text-lg font-extrabold text-[#0F172A] mb-0.5 dark:text-slate-100">Koreksi Stok Etalase Siap Jual</h3>
                <p class="text-xs text-amber-600 font-bold mb-4" x-text="selectedMenuName"></p>

                <form action="{{ route('inventory.production.opname') }}" method="POST">
                    @csrf
                    <input type="hidden" name="menu_id" x-model="selectedMenuId">

                    <div class="bg-amber-50 rounded-2xl p-4 mb-4 border border-amber-200">
                        <p class="text-[11px] text-amber-800 font-semibold mb-0.5">Stok Etalase Siap Jual Saat Ini:</p>
                        <p class="text-xl font-extrabold text-amber-950 tabular-nums">
                            <span x-text="currentStock"></span>
                            <span class="text-xs font-bold uppercase" x-text="selectedUnit"></span>
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-bold text-[#475569] uppercase tracking-wider mb-1">
                                Stok Fisik Nyata di Etalase (<span x-text="selectedUnit"></span>)
                            </label>
                            <input
                                type="number"
                                step="1"
                                min="0"
                                name="actual_stock"
                                required
                                class="w-full text-sm border border-slate-200 bg-[#F8FAFC] rounded-xl focus:ring-2 focus:ring-[#0D9488] font-bold p-2.5 text-[#DC2626]"
                                placeholder="Masukkan jumlah fisik layak jual">
                            <p class="text-[10px] text-slate-500 mt-1 leading-tight dark:text-slate-400">
                                Jika terdapat barang busuk/rusak di etalase, sistem otomatis mencatat selisihnya sebagai kerugian susut.
                            </p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-[#475569] uppercase tracking-wider mb-1">
                                Alasan / Keterangan Koreksi
                            </label>
                            <input
                                type="text"
                                name="reason"
                                required
                                class="w-full text-sm border border-slate-200 bg-[#F8FAFC] rounded-xl focus:ring-2 focus:ring-[#0D9488] font-medium p-2.5"
                                placeholder="Contoh: Layu sore hari, kemasan bocor, susut timbangan">
                        </div>
                        <div class="pt-1">
                            <button type="submit"
                                class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-xl shadow-md shadow-amber-500/20 active:scale-95 transition-transform cursor-pointer">
                                Simpan Koreksi Etalase
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('productionManager', () => ({
                showProduction: false,
                showOpname: false,
                searchQuery: '',
                selectedCategory: 'ALL',
                openSections: {
                    'Sayur & Buah Segar': true,
                    'Bumbu Dapur & Rempah': true,
                    'Minyak, Saus & Kecap': false,
                    'Sembako & Bahan Pokok': false,
                    'Lauk, Minuman & Rumah Tangga': false,
                },
                selectedMenuId: null,
                selectedMenuName: '',
                selectedUnit: 'satuan',
                maxCapacity: 0,
                currentStock: 0,

                toggleSection(cat) {
                    this.openSections[cat] = !this.openSections[cat];
                },

                isSectionOpen(cat) {
                    if (this.searchQuery.trim() !== '') return true;
                    if (this.selectedCategory !== 'ALL' && this.selectedCategory !== 'FIFO') {
                        return this.selectedCategory === cat;
                    }
                    if (this.selectedCategory === 'FIFO') return true;
                    return Boolean(this.openSections[cat]);
                },

                expandAll(state) {
                    Object.keys(this.openSections).forEach((k) => {
                        this.openSections[k] = state;
                    });
                },

                matchesSearch(name, category, isFifo = false, unit = '') {
                    if (this.selectedCategory === 'FIFO' && !isFifo) return false;
                    if (this.selectedCategory !== 'ALL' && this.selectedCategory !== 'FIFO' && category !== this.selectedCategory) {
                        return false;
                    }
                    const q = this.searchQuery.trim().toLowerCase();
                    if (!q) return true;
                    return String(name).toLowerCase().includes(q)
                        || String(category).toLowerCase().includes(q)
                        || String(unit).toLowerCase().includes(q);
                },

                openProduction(id, name, capacity, unit) {
                    if (capacity <= 0) return;
                    this.selectedMenuId = id;
                    this.selectedMenuName = name;
                    this.maxCapacity = capacity;
                    this.selectedUnit = unit || 'satuan';
                    this.showProduction = true;
                    this.showOpname = false;
                },

                openOpname(id, name, stock, unit) {
                    this.selectedMenuId = id;
                    this.selectedMenuName = name;
                    this.currentStock = stock;
                    this.selectedUnit = unit || 'satuan';
                    this.showOpname = true;
                    this.showProduction = false;
                }
            }));
        });
    </script>
</x-app-layout>
