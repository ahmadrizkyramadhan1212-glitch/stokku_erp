<x-app-layout>
    @php
        $initialExtracted = $initialExtracted ?? [];
        $sampleText = $sampleText ?? 'Beli cabai keriting 8kg 360000, daun jeruk 10 ikat 100000, jagung manis 12kg 144000';
        $ingredients = collect($ingredients ?? []);
        $recentPurchases = collect($recentPurchases ?? []);
        $todayOmsetKotor = (float) ($todayOmsetKotor ?? 0);
        $todayModalBelanja = (float) ($todayModalBelanja ?? 0);
        $todayNetProfit = (float) ($todayNetProfit ?? ($todayOmsetKotor - $todayModalBelanja));
        $authUser = Auth::user();
        $isOwner = $authUser ? $authUser->isOwner() : false;
        $todayIncomingCount = $recentPurchases->filter(fn($p) => \Carbon\Carbon::parse($p->purchase_date ?? $p->created_at)->isToday())->count();
    @endphp
    {{-- RBAC: harga modal (cost) hanya dikirim ke JS untuk Owner agar Staf tidak bisa melihat HPP lewat auto-fill --}}
    <div x-data="purchaseManager(@js($initialExtracted), @js($sampleText), @js($ingredients->map(fn($i) => [
            'id' => $i->id,
            'name' => $i->name ?? $i->nama_barang ?? '-',
            'unit' => $i->unit ?? 'Kg',
            'stock' => (float) ($i->current_stock ?? 0),
            'cost' => $isOwner ? (float) ($i->cost_per_unit ?? 0) : 0
        ])->values()))"
         class="flex flex-col gap-6 pb-10">

        <!-- ===================================================================
             FLASH NOTIFICATIONS
             =================================================================== -->
        @if(session('success'))
            <div class="bg-teal-50 dark:bg-slate-900 border border-slate-200 dark:border-teal-500/40 text-[#0F172A] dark:text-slate-100 px-4 py-3.5 rounded-2xl flex items-start gap-3 shadow-sm">
                <span class="material-symbols-outlined text-teal-600 dark:text-teal-300 mt-0.5">check_circle</span>
                <div class="text-xs font-bold leading-relaxed">{{ session('success') }}</div>
            </div>
        @endif

        @if($isOwner && session('price_recommendations') && count(session('price_recommendations')) > 0)
            <div class="bg-amber-50 dark:bg-amber-950/40 border-2 border-amber-400 dark:border-amber-500/50 text-amber-950 dark:text-amber-100 p-4 sm:p-5 rounded-3xl shadow-md flex flex-col gap-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                            <span class="material-symbols-outlined text-[22px]">price_change</span>
                        </div>
                        <div>
                            <span class="px-2 py-0.5 rounded-full bg-amber-200 dark:bg-amber-500/30 text-amber-900 dark:text-amber-200 text-[10px] font-extrabold uppercase">
                                Peringatan Kenaikan Modal Pasar
                            </span>
                            <h3 class="text-sm sm:text-base font-extrabold text-amber-950 dark:text-slate-100 mt-0.5">
                                Harga Kulakan Naik! Sesuaikan Harga Jual Warung Agar Untung Tidak Tergerus
                            </h3>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach(session('price_recommendations') as $rec)
                        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-amber-300 dark:border-amber-500/40 flex flex-col justify-between gap-3 shadow-2xs">
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-xs sm:text-sm font-extrabold text-[#0F172A] dark:text-slate-100">{{ $rec['name'] }}</h4>
                                    <span class="px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 text-[10px] font-extrabold">
                                        Modal Naik +{{ $rec['increase_pct'] }}%
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-600 dark:text-slate-400 mt-1">
                                    Modal Lama: <strong>Rp {{ number_format($rec['old_cost'], 0, ',', '.') }}</strong>
                                    &rarr; Modal Baru: <strong class="text-rose-600 dark:text-rose-400">Rp {{ number_format($rec['new_cost'], 0, ',', '.') }}/{{ $rec['unit'] }}</strong>
                                </p>
                                <p class="text-[11px] text-teal-700 dark:text-teal-300 font-bold mt-0.5">
                                    Harga Jual Saat Ini: Rp {{ number_format($rec['current_sell_price'], 0, ',', '.') }}
                                    &rarr; Saran Harga Jual Baru: <strong class="underline">Rp {{ number_format($rec['recommended_sell_price'], 0, ',', '.') }}/{{ $rec['sale_unit'] }}</strong>
                                </p>
                            </div>

                            @if(!empty($rec['ingredient_id']) || !empty($rec['menu_id']))
                                <form action="{{ route('purchases.update_price') }}" method="POST" class="flex items-center justify-end gap-2 pt-2 border-t border-amber-100 dark:border-slate-800">
                                    @csrf
                                    <input type="hidden" name="ingredient_id" value="{{ $rec['ingredient_id'] ?? $rec['menu_id'] }}">
                                    <input type="hidden" name="new_price" value="{{ $rec['recommended_sell_price'] }}">
                                    <button type="submit"
                                            class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white text-[11px] font-extrabold shadow-sm cursor-pointer transition active:scale-95">
                                        Terapkan Harga Jual Rp {{ number_format($rec['recommended_sell_price'], 0, ',', '.') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-500/40 text-rose-900 dark:text-rose-200 px-4 py-3 rounded-2xl flex items-center gap-3 shadow-sm">
                <span class="material-symbols-outlined text-rose-600 dark:text-rose-400">error</span>
                <span class="text-xs font-bold">{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-500/40 text-rose-900 dark:text-rose-200 px-4 py-3 rounded-2xl text-xs font-semibold">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- ===================================================================
             1. HERO HEADER BELANJA PASAR & KONVERSI GROSIR KE ECERAN (HD GLOSSY)
             =================================================================== -->
        <section class="relative overflow-hidden rounded-3xl p-6 sm:p-6 bg-gradient-to-br from-[#0D9488] to-[#0F766E] text-white shadow-xl shadow-teal-500/30 border border-white/25">
            <!-- Dekorasi Cahaya Glossy -->
            <div class="pointer-events-none absolute -top-12 -right-12 w-44 h-44 rounded-full bg-white/15 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-14 -left-10 w-40 h-40 rounded-full bg-white/15 blur-2xl"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center text-white shrink-0 shadow-md">
                        <span class="material-symbols-outlined text-[26px]">shopping_bag</span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="px-2.5 py-0.5 rounded-full bg-white/20 backdrop-blur-md border border-white/30 text-white text-[10px] font-extrabold uppercase tracking-wider">
                                Pencatatan Kulakan Subuh
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-400/25 border border-emerald-300/40 text-white text-[10px] font-extrabold">
                                Otomatis Tambah Stok Rak
                            </span>
                        </div>
                        <h1 class="text-lg sm:text-xl font-extrabold text-white tracking-tight">
                            Belanja Pasar &amp; Konversi Dus/Karung ke Eceran
                        </h1>
                        <p class="text-xs text-white/90 mt-0.5 leading-relaxed max-w-2xl">
                            Catat belanjaan pasar dalam satuan <strong class="text-white">Karung, Dus, Bal, Ikat, atau Papan</strong> — sistem otomatis menghitung stok eceran dan memberi saran harga jual jika harga pasar naik.
                        </p>
                    </div>
                </div>

                <!-- Ringkasan Keuangan Hari Ini & Tombol Tambah Barang Baru di Dalam Header -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 shrink-0">
                    @if($isOwner)
                        <div class="grid grid-cols-3 gap-2.5">
                            <div class="p-3 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-white/85 block">Omset Kotor</span>
                                <span class="text-xs sm:text-sm font-extrabold text-white tabular-nums mt-0.5 block">
                                    Rp {{ number_format($todayOmsetKotor, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="p-3 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-white/85 block">Total Pengeluaran Belanja</span>
                                <span class="text-xs sm:text-sm font-extrabold text-white tabular-nums mt-0.5 block">
                                    Rp {{ number_format($todayModalBelanja, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="p-3 rounded-2xl bg-white/20 backdrop-blur-md border border-white/35">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-white/95 block">Omset Bersih</span>
                                <span class="text-xs sm:text-sm font-extrabold text-white tabular-nums mt-0.5 block">
                                    Rp {{ number_format($todayNetProfit, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- Staf: ringkasan operasional tanpa angka rupiah --}}
                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="p-3 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-white/85 block">Nota Masuk Hari Ini</span>
                                <span class="text-xs sm:text-sm font-extrabold text-white tabular-nums mt-0.5 block">
                                    {{ $todayIncomingCount }} Nota
                                </span>
                            </div>
                            <div class="p-3 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-white/85 block">Komoditas Terdaftar</span>
                                <span class="text-xs sm:text-sm font-extrabold text-white tabular-nums mt-0.5 block">
                                    {{ $ingredients->count() }} Barang
                                </span>
                            </div>
                        </div>
                    @endif

                    <button type="button"
                            @click="showAddModal = true"
                            class="px-4 py-3 rounded-2xl bg-white text-[#0D9488] hover:bg-teal-50 text-xs font-extrabold shadow-md flex items-center justify-center gap-1.5 cursor-pointer transition active:scale-95 shrink-0">
                        <span class="material-symbols-outlined text-[18px]">add_circle</span>
                        <span>+ Barang Baru</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- ===================================================================
             2. GRID UTAMA: FORM KONVERSI GROSIR KE ECERAN (KIRI) & SCAN NOTA / WA (KANAN)
             =================================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

            <!-- KOLOM KIRI (7 KOLOM): INPUT BELANJA KONVERSI GROSIR KE ECERAN -->
            <section class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between gap-5">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-4 pb-3 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-500/20 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[22px]">calculate</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-teal-600 dark:text-teal-300">Input Cepat Tanpa Pusing Hitung</span>
                                <h2 class="text-sm sm:text-base font-extrabold text-[#0F172A] dark:text-slate-100">
                                    Catat Belanja Grosir (Karung / Dus / Ikat &rarr; Eceran)
                                </h2>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('purchases.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <!-- Pilih Barang Warung -->
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100">
                                    1. Pilih Barang yang Dibeli di Pasar
                                </label>
                                <button type="button"
                                        @click="showAddModal = true"
                                        class="text-[11px] font-extrabold text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1 cursor-pointer">
                                    <span class="material-symbols-outlined text-[15px]">add_box</span>
                                    <span>Barang Belum Ada? Tambah Baru</span>
                                </button>
                            </div>
                            <select name="ingredient_id"
                                    x-model="manualIngredientId"
                                    @change="onManualIngredientChange()"
                                    required
                                    class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2.5 text-xs sm:text-sm font-bold text-[#0F172A] dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-teal-600">
                                <option value="">-- Pilih Komoditas Warung ({{ $ingredients->count() }} Barang) --</option>
                                @foreach($ingredients as $ing)
                                    <option value="{{ $ing->id }}">
                                        {{ $ing->name }} (Stok: {{ number_format($ing->current_stock, 0, ',', '.') }} {{ $ing->unit }}@if($isOwner) • Modal Lama: Rp {{ number_format($ing->cost_per_unit, 0, ',', '.') }}/{{ $ing->unit }}@endif)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Pilih Jenis Kemasan Grosir & Isi per Kemasan -->
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 space-y-3.5">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-teal-600 dark:text-teal-300 flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[16px]">inventory</span>
                                    <span>2. Konversi Otomatis Kemasan Grosir ke Satuan Eceran</span>
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full bg-teal-100 dark:bg-teal-500/25 text-teal-700 dark:text-teal-200 text-[10px] font-extrabold"
                                      x-text="'Satuan Rak: ' + manualUnit"></span>
                            </div>

                            <!-- Tombol Preset Kemasan Grosir -->
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" @click="applyPackPreset('Eceran Langsung', 1)"
                                        :class="packType === 'Eceran Langsung' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700'"
                                        class="px-3 py-1.5 rounded-xl text-[11px] font-extrabold cursor-pointer transition">
                                    Langsung (<span x-text="manualUnit"></span>)
                                </button>
                                <button type="button" @click="applyPackPreset('Karung 25 Kg', 25)"
                                        :class="packType === 'Karung 25 Kg' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700'"
                                        class="px-3 py-1.5 rounded-xl text-[11px] font-extrabold cursor-pointer transition">
                                    Karung (25 Kg)
                                </button>
                                <button type="button" @click="applyPackPreset('Karung 50 Kg', 50)"
                                        :class="packType === 'Karung 50 Kg' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700'"
                                        class="px-3 py-1.5 rounded-xl text-[11px] font-extrabold cursor-pointer transition">
                                    Karung (50 Kg)
                                </button>
                                <button type="button" @click="applyPackPreset('Dus Isi 40', 40)"
                                        :class="packType === 'Dus Isi 40' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700'"
                                        class="px-3 py-1.5 rounded-xl text-[11px] font-extrabold cursor-pointer transition">
                                    Dus Mie (Isi 40)
                                </button>
                                <button type="button" @click="applyPackPreset('Dus Isi 12', 12)"
                                        :class="packType === 'Dus Isi 12' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700'"
                                        class="px-3 py-1.5 rounded-xl text-[11px] font-extrabold cursor-pointer transition">
                                    Dus Minyak (Isi 12)
                                </button>
                                <button type="button" @click="applyPackPreset('Peti / Ikat 10', 10)"
                                        :class="packType === 'Peti / Ikat 10' ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-[#0F172A] dark:text-slate-300 border border-slate-200 dark:border-slate-700'"
                                        class="px-3 py-1.5 rounded-xl text-[11px] font-extrabold cursor-pointer transition">
                                    Ikat / Pak (Isi 10)
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">
                                        Jumlah Beli (<span x-text="packType"></span>)
                                    </label>
                                    <input type="number" step="0.01" min="0.1"
                                           x-model="packCount"
                                           @input="recalcConversion()"
                                           class="w-full rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm font-extrabold text-[#0F172A] dark:text-slate-100 tabular-nums" />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">
                                        Isi per Kemasan (<span x-text="manualUnit"></span>)
                                    </label>
                                    <input type="number" step="0.01" min="0.1"
                                           x-model="packSize"
                                           @input="recalcConversion()"
                                           class="w-full rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-3 py-2 text-sm font-extrabold text-[#0F172A] dark:text-slate-100 tabular-nums" />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-extrabold text-teal-600 dark:text-teal-300 mb-1">
                                        Total Masuk Rak (<span x-text="manualUnit"></span>)
                                    </label>
                                    <input type="number" step="0.01" min="0.1" name="quantity"
                                           x-model="manualQty"
                                           required
                                           class="w-full rounded-xl bg-teal-50 dark:bg-teal-950/60 border border-teal-300 dark:border-teal-500/50 px-3 py-2 text-sm font-extrabold text-teal-700 dark:text-teal-200 tabular-nums" />
                                </div>
                            </div>
                        </div>

                        <!-- Total Harga Nota & Pemasok -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">
                                    3. Total Harga Beli di Nota (Rp)
                                </label>
                                <input type="number" step="100" min="0" name="total_price"
                                       x-model="manualTotal"
                                       required
                                       placeholder="Contoh: 350000"
                                       class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2.5 text-sm font-extrabold text-[#0F172A] dark:text-slate-100 tabular-nums" />
                            </div>

                            <div>
                                <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100 mb-1">
                                    4. Nama Pedagang / Pasar (Opsional)
                                </label>
                                <input type="text" name="supplier_name"
                                       list="suppliers_datalist"
                                       placeholder="Contoh: Agen Grosir Pasar Induk"
                                       class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3.5 py-2.5 text-xs sm:text-sm font-semibold text-[#0F172A] dark:text-slate-100" />
                                <datalist id="suppliers_datalist">
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->name }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        </div>

                        <!-- Upload Struk / Nota Belanja Pasar (Opsional) -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-dashed border-slate-300 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 flex items-center justify-center">
                                    <template x-if="manualReceiptPreview">
                                        <img :src="manualReceiptPreview" alt="Preview Struk" width="48" height="48" style="width:48px;height:48px;object-fit:cover;" class="w-full h-full object-cover object-center">
                                    </template>
                                    <template x-if="!manualReceiptPreview">
                                        <span class="material-symbols-outlined text-[22px] text-teal-600 dark:text-teal-400">receipt</span>
                                    </template>
                                </div>
                                <div class="min-w-0">
                                    <label class="block text-xs font-extrabold text-[#0F172A] dark:text-slate-100">
                                        5. Upload Struk / Foto Nota Belanja Pasar (Opsional)
                                    </label>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                        Lampirkan foto nota agar pengeluaran kas warung tercatat rapi (JPG/PNG/WEBP, maks 4MB).
                                    </p>
                                </div>
                            </div>
                            <input type="file"
                                   name="receipt"
                                   accept="image/*"
                                   @change="previewManualReceipt($event)"
                                   class="text-[11px] text-slate-600 dark:text-slate-300 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[11px] file:font-extrabold file:bg-teal-600 file:text-white hover:file:bg-teal-700 cursor-pointer shrink-0">
                        </div>

                        <!-- Kotak Hasil Hitung Otomatis Modal per Eceran & Saran Harga Jual -->
                        <div class="p-4 rounded-2xl bg-gradient-to-r from-teal-50 via-violet-50 to-purple-50 dark:from-[#112620] dark:via-[#151226] dark:to-[#1C1632] border border-slate-200 dark:border-teal-500/35 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="text-xs space-y-0.5">
                                @if($isOwner)
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-teal-600 dark:text-teal-300 block">
                                        Hasil Hitung Otomatis
                                    </span>
                                    <p class="text-[#0F172A] dark:text-slate-100 font-bold">
                                        Modal Baru per <span x-text="manualUnit"></span>:
                                        <strong class="text-sm font-extrabold text-teal-600 dark:text-teal-300 tabular-nums" x-text="formatRupiah(unitCostPreview)"></strong>
                                    </p>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400">
                                        Saran Harga Jual Warung (+25% Untung):
                                        <strong class="text-emerald-600 dark:text-emerald-400 font-extrabold tabular-nums" x-text="formatRupiah(recommendedSellPreview)"></strong>/<span x-text="manualUnit"></span>
                                    </p>
                                @else
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-teal-600 dark:text-teal-300 block">
                                        Barang Masuk Rak
                                    </span>
                                    <p class="text-[#0F172A] dark:text-slate-100 font-bold">
                                        +<span class="tabular-nums" x-text="manualQty"></span> <span x-text="manualUnit"></span> akan ditambahkan ke stok
                                    </p>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-400">Salin total harga persis seperti yang tertulis di nota pasar.</p>
                                @endif
                            </div>

                            <button type="submit"
                                    class="py-3 px-5 rounded-2xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white text-xs sm:text-sm font-extrabold shadow-lg shadow-teal-500/35 flex items-center justify-center gap-2 transition active:scale-95 cursor-pointer shrink-0">
                                <span class="material-symbols-outlined text-[19px]">save</span>
                                <span>Simpan Belanja &amp; Tambah Stok</span>
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- KOLOM KANAN (5 KOLOM): TEMPEL CATATAN WA / FOTO STRUK PASAR (GEMINI AI SCANNER) -->
            <section class="lg:col-span-5 bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700 flex flex-col justify-between gap-4">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-200 dark:border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white shadow-md shadow-teal-500/20 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-[20px]">document_scanner</span>
                            </div>
                            <div>
                                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-teal-50 dark:bg-teal-950/60 text-[#0D9488] dark:text-teal-300 border border-teal-200/60 dark:border-teal-800/50 text-[10px] font-black uppercase tracking-wider">
                                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse"></span>
                                    Scan Nota AI (Gemini 2.5 Flash)
                                </div>
                                <h2 class="text-sm font-extrabold text-[#0F172A] dark:text-slate-100 mt-0.5">
                                    Scan Foto Nota / Catatan Teks Tengkulak
                                </h2>
                            </div>
                        </div>

                        <button type="button"
                                @click="fillSampleText()"
                                class="px-2.5 py-1 rounded-xl bg-teal-50 dark:bg-slate-950 hover:bg-teal-100 text-teal-600 dark:text-teal-300 border border-slate-200 dark:border-slate-700 text-[11px] font-extrabold cursor-pointer transition">
                            Contoh Nota
                        </button>
                    </div>

                    <textarea x-model="rawText"
                              rows="4"
                              placeholder="Ketik atau tempel catatan belanja dari pasar, contoh:&#10;Cabai Rawit Merah 10 Kg 420000&#10;Telur Ayam 15 Kg 405000&#10;Bawang Merah 8 kg 304000"
                              class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 p-3 text-xs font-semibold text-[#0F172A] dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600"></textarea>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button"
                                @click="parseTextNote()"
                                :disabled="isParsing"
                                class="flex-1 py-2.5 px-3.5 rounded-xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white text-xs font-extrabold shadow-md shadow-teal-500/25 flex items-center justify-center gap-1.5 cursor-pointer transition disabled:opacity-50">
                            <template x-if="isParsing">
                                <span class="flex items-center gap-1.5">
                                    <svg class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>AI Sedang Memindai...</span>
                                </span>
                            </template>
                            <template x-if="!isParsing">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[17px]">auto_fix_high</span>
                                    <span>Pindai Catatan AI</span>
                                </span>
                            </template>
                        </button>

                        <label class="py-2.5 px-3.5 rounded-xl bg-teal-50 dark:bg-slate-950 hover:bg-teal-100 dark:hover:bg-teal-950/70 text-teal-700 dark:text-teal-300 border border-slate-200 dark:border-slate-700 text-xs font-extrabold flex items-center justify-center gap-1.5 cursor-pointer transition">
                            <span class="material-symbols-outlined text-[17px]">photo_camera</span>
                            <span>Upload Foto Nota</span>
                            <input type="file" name="receipt" accept="image/*" class="hidden" @change="uploadReceiptImage($event)">
                        </label>
                    </div>

                    <!-- Indikator Sumber & Status Parsing AI -->
                    <div x-show="parseMessage" x-cloak class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-200">
                            <span class="material-symbols-outlined text-[18px] text-emerald-600 dark:text-emerald-400">check_circle</span>
                            <span x-text="parseMessage" class="font-semibold text-[11px]"></span>
                        </div>
                        <span x-text="parseSource" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-200/80 dark:bg-emerald-900 text-emerald-900 dark:text-emerald-100"></span>
                    </div>

                    <!-- Preview Foto Struk / Nota Pasar yang Baru Diunggah -->
                    <div x-show="receiptPreviewUrl" x-cloak class="p-3 rounded-2xl bg-teal-50/70 dark:bg-slate-950 border border-teal-200 dark:border-teal-800/60 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-11 h-11 rounded-xl overflow-hidden shrink-0 border border-teal-300 dark:border-teal-700 bg-white">
                                <img :src="receiptPreviewUrl" alt="Struk" width="44" height="44" style="width:44px;height:44px;object-fit:cover;" class="w-full h-full object-cover">
                            </div>
                            <div class="min-w-0">
                                <span class="text-[11px] font-extrabold text-teal-800 dark:text-teal-300 block truncate">Foto Nota Pasar Terlampir</span>
                                <span class="text-[10px] text-slate-600 dark:text-slate-400 block">Siap disimpan bersama stok belanja di bawah</span>
                            </div>
                        </div>
                        <button type="button" @click="receiptPreviewUrl = null; uploadedReceiptPath = ''" class="text-xs font-extrabold text-rose-600 hover:underline shrink-0 cursor-pointer">
                            Hapus
                        </button>
                    </div>

                    <!-- Tabel Preview Item Hasil Baca Nota -->
                    <form action="{{ route('purchases.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3 pt-2 border-t border-slate-200 dark:border-slate-800">
                        @csrf
                        <input type="hidden" name="receipt_path" :value="uploadedReceiptPath">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-[#0F172A] dark:text-slate-100">
                                Daftar Barang Siap Disimpan (<span x-text="extractedItems.length"></span>)
                            </span>
                            <button type="button"
                                    @click="addEmptyRow()"
                                    class="text-[11px] font-extrabold text-teal-600 dark:text-teal-300 hover:underline cursor-pointer">
                                + Tambah Baris
                            </button>
                        </div>

                        <div class="max-h-56 overflow-y-auto space-y-2 pr-1">
                            <template x-for="(row, index) in extractedItems" :key="index">
                                <div class="p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 flex flex-col gap-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <select :name="'items[' + index + '][ingredient_id]'"
                                                x-model="row.ingredient_id"
                                                required
                                                class="flex-1 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs font-bold text-[#0F172A] dark:text-slate-100">
                                            @foreach($ingredients as $ing)
                                                <option value="{{ $ing->id }}">{{ $ing->name }} ({{ $ing->unit }})</option>
                                            @endforeach
                                        </select>
                                        <button type="button" @click="removeRow(index)" class="text-rose-500 hover:text-rose-700 cursor-pointer">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="number" step="0.01" min="0.1"
                                               :name="'items[' + index + '][quantity]'"
                                               x-model="row.quantity"
                                               placeholder="Jumlah"
                                               required
                                               class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs font-bold text-[#0F172A] dark:text-slate-100" />
                                        <input type="number" step="100" min="0"
                                               :name="'items[' + index + '][subtotal]'"
                                               x-model="row.subtotal"
                                               placeholder="Total Rp"
                                               required
                                               class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1.5 text-xs font-bold text-[#0F172A] dark:text-slate-100" />
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button type="submit"
                                x-show="extractedItems.length > 0"
                                class="w-full py-2.5 px-4 rounded-2xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white text-xs font-extrabold shadow-md shadow-teal-500/30 flex items-center justify-center gap-1.5 cursor-pointer transition">
                            <span class="material-symbols-outlined text-[18px]">done_all</span>
                            <span>Simpan Semua ke Stok Rak</span>
                        </button>
                    </form>
                </div>
            </section>
        </div>

        <!-- ===================================================================
             3. RIWAYAT BELANJA PASAR TERAKHIR
             =================================================================== -->
        <section class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-6 shadow-md dark:shadow-lg dark:shadow-teal-950/50 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-teal-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-teal-600 dark:text-teal-300 flex items-center justify-center">
                        <span class="material-symbols-outlined text-[20px]">history</span>
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-extrabold text-[#0F172A] dark:text-slate-100">
                            Riwayat Belanja Pasar Terakhir
                        </h2>
                        <p class="text-xs text-slate-600 dark:text-slate-400">
                            Catatan barang masuk ke gudang warung{{ $isOwner ? ' beserta harga beli terbaru' : '' }} &amp; bukti nota pasar
                        </p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700 text-[11px] font-extrabold uppercase tracking-wider text-teal-600 dark:text-teal-300">
                            <th class="py-2.5 px-3">Waktu</th>
                            <th class="py-2.5 px-3">Nama Barang</th>
                            <th class="py-2.5 px-3">Jumlah Masuk</th>
                            <th class="py-2.5 px-3">Bukti Nota</th>
                            @if($isOwner)
                                <th class="py-2.5 px-3 text-right">Total Nota (Rp)</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-teal-100/60 dark:divide-teal-500/20 text-xs">
                        @forelse($recentPurchases as $pur)
                            @php
                                $firstItem = $pur->items->first();
                                $extraCount = max(0, $pur->items->count() - 1);
                                $totalQtyText = $pur->items->map(function($it) {
                                    return '+' . number_format((float)$it->quantity, 0, ',', '.') . ' ' . ($it->ingredient->unit ?? 'Kg');
                                })->take(2)->implode(', ');
                                $itemTitle = $firstItem?->ingredient?->name ?? ($pur->notes ?: 'Belanja Komoditas Pasar');
                            @endphp
                            <tr class="hover:bg-teal-50/40 dark:hover:bg-teal-950/30 transition">
                                <td class="py-2.5 px-3 font-semibold text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    {{ Carbon\Carbon::parse($pur->purchase_date ?? $pur->created_at)->translatedFormat('d M Y') }}
                                </td>
                                <td class="py-2.5 px-3 font-extrabold text-[#0F172A] dark:text-slate-100">
                                    {{ $itemTitle }}
                                    @if($extraCount > 0)
                                        <span class="ml-1 text-[10px] font-bold text-teal-600 dark:text-teal-300">(+{{ $extraCount }} barang lainnya)</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full bg-teal-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-teal-700 dark:text-teal-300 font-extrabold text-[11px] tabular-nums">
                                        {{ $totalQtyText ?: 'Stok Masuk' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3">
                                    @if($pur->receipt_url)
                                        <button type="button"
                                                @click="openReceiptPreview('{{ $pur->receipt_url }}', '{{ addslashes($itemTitle) }}')"
                                                class="px-2.5 py-1 rounded-xl bg-teal-50 dark:bg-teal-950/60 hover:bg-teal-100 border border-teal-200 dark:border-teal-700 text-teal-700 dark:text-teal-300 font-extrabold text-[11px] inline-flex items-center gap-1 cursor-pointer transition">
                                            <span class="material-symbols-outlined text-[14px]">receipt_long</span>
                                            <span>Lihat Struk</span>
                                        </button>
                                    @else
                                        <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Tanpa Foto</span>
                                    @endif
                                </td>
                                @if($isOwner)
                                    <td class="py-2.5 px-3 text-right font-extrabold text-teal-600 dark:text-teal-300 tabular-nums whitespace-nowrap">
                                        Rp {{ number_format($pur->total_amount ?? 0, 0, ',', '.') }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isOwner ? 5 : 4 }}" class="py-6 text-center text-xs text-slate-400">
                                    Belum ada riwayat belanja pasar yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ===================================================================
             4. MODAL TAMBAH BARANG BARU (DARI HALAMAN BELANJA PASAR)
             =================================================================== -->
        <div x-show="showAddModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-xs"
             x-transition.opacity>
            <div @click.away="showAddModal = false"
                 class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-teal-600 dark:text-teal-400 block">
                            Master Komoditas Warung
                        </span>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-slate-100">
                            Tambah Barang / Komoditas Baru
                        </h3>
                    </div>
                    <button type="button" @click="showAddModal = false" class="p-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <form action="{{ route('inventory.materials.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3.5">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200 mb-1">Nama Barang Warung</label>
                            <input type="text" name="name" required placeholder="Contoh: Beras Pandan Wangi 5Kg"
                                   class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100" />
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200 mb-1">Kategori Rak (A–E)</label>
                            <select name="kategori" required
                                    class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100">
                                <option value="Sayur & Buah Segar">Kategori A — Sayur &amp; Buah Segar</option>
                                <option value="Bumbu Dapur & Rempah">Kategori B — Bumbu Dapur &amp; Rempah</option>
                                <option value="Minyak, Saus & Kecap">Kategori C — Minyak, Saus &amp; Kecap</option>
                                <option value="Sembako & Bahan Pokok">Kategori D — Sembako &amp; Bahan Pokok</option>
                                <option value="Lauk, Minuman & Rumah Tangga">Kategori E — Lauk, Minuman &amp; Rumah Tangga</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200 mb-1">Satuan Jual Eceran</label>
                            <input type="text" name="unit" value="Kg" required placeholder="Kg / Liter / Bungkus / Ikat"
                                   class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100" />
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200 mb-1">Stok Awal Masuk Rak</label>
                            <input type="number" step="0.01" min="0" name="current_stock" value="15" required
                                   class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 tabular-nums" />
                        </div>

                        <div>
                            <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200 mb-1">Batas Aman Minimal</label>
                            <input type="number" step="0.01" min="0" name="min_stock" value="5" required
                                   class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 tabular-nums" />
                        </div>

                        @if($isOwner)
                            <div>
                                <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200 mb-1">Harga Modal Beli / Satuan (Rp)</label>
                                <input type="number" step="100" min="0" name="cost_per_unit" value="12000" required
                                       class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 tabular-nums" />
                            </div>

                            <div>
                                <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200 mb-1">Harga Jual Warung (Rp)</label>
                                <input type="number" step="100" min="0" name="price_eceran" value="15000" required
                                       class="w-full rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 tabular-nums" />
                            </div>
                        @else
                            <input type="hidden" name="cost_per_unit" value="0">
                            <input type="hidden" name="price_eceran" value="0">
                            <div class="sm:col-span-2 p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-500/30 text-[11px] font-semibold text-amber-800 dark:text-amber-200">
                                Harga modal &amp; harga jual akan diatur oleh Owner setelah barang tersimpan.
                            </div>
                        @endif

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-extrabold text-slate-800 dark:text-slate-200 mb-1">
                                Foto Riil Komoditas (Opsional — JPG, PNG, WEBP maks 2MB)
                            </label>
                            <div class="flex items-center gap-3">
                                <div class="w-14 h-14 rounded-xl overflow-hidden shrink-0 border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                    <template x-if="addImagePreview">
                                        <img :src="addImagePreview" alt="Preview" width="56" height="56" style="width:56px;height:56px;object-fit:cover;" class="w-full h-full object-cover object-center">
                                    </template>
                                    <template x-if="!addImagePreview">
                                        <span class="material-symbols-outlined text-slate-400 text-[22px]">image</span>
                                    </template>
                                </div>
                                <input type="file"
                                       name="image"
                                       accept="image/*"
                                       @change="previewAddImage($event)"
                                       class="flex-1 text-xs text-slate-600 dark:text-slate-300 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-teal-50 file:text-teal-700 dark:file:bg-teal-950 dark:file:text-teal-300 hover:file:bg-teal-100 cursor-pointer" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-extrabold shadow-md cursor-pointer">
                            Simpan Barang Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===================================================================
             5. MODAL PREVIEW BUKTI STRUK / NOTA BELANJA PASAR
             =================================================================== -->
        <div x-show="receiptModalOpen"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-xs"
             x-transition.opacity>
            <div @click.away="receiptModalOpen = false"
                 class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-3xl max-w-md w-full p-5 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-teal-600 dark:text-teal-400 block">
                            Bukti Pengeluaran Kas Warung
                        </span>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-slate-100" x-text="receiptModalTitle"></h3>
                    </div>
                    <button type="button" @click="receiptModalOpen = false" class="p-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>

                <div class="rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 flex items-center justify-center max-h-[65vh]">
                    <img :src="receiptModalUrl" alt="Bukti Nota Belanja" class="max-h-[60vh] w-auto object-contain mx-auto">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <a :href="receiptModalUrl" target="_blank" class="px-4 py-2 rounded-xl bg-teal-50 dark:bg-teal-950 text-teal-700 dark:text-teal-300 text-xs font-extrabold hover:underline">
                        Buka Ukuran Penuh
                    </a>
                    <button type="button" @click="receiptModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        window.purchaseManager = function(initialExtracted, sampleText, ingredientsList) {
            return {
                extractedItems: initialExtracted || [],
                rawText: '',
                sampleText: sampleText || '',
                ingredients: ingredientsList || [],
                isParsing: false,
                parseMessage: '',
                parseSource: '',

                // State Modal Tambah Barang Baru & Preview Struk
                showAddModal: false,
                addImagePreview: null,
                manualReceiptPreview: null,
                receiptPreviewUrl: null,
                uploadedReceiptPath: '',
                receiptModalOpen: false,
                receiptModalUrl: '',
                receiptModalTitle: 'Nota Belanja Pasar',

                // State untuk Konversi Grosir ke Eceran
                manualIngredientId: ingredientsList && ingredientsList.length > 0 ? ingredientsList[0].id : '',
                manualUnit: ingredientsList && ingredientsList.length > 0 ? ingredientsList[0].unit : 'Kg',
                packType: 'Eceran Langsung',
                packCount: 10,
                packSize: 1,
                manualQty: 10,
                manualTotal: ingredientsList && ingredientsList.length > 0 ? Math.round(ingredientsList[0].cost * 10) : 150000,

                get unitCostPreview() {
                    const qty = parseFloat(this.manualQty) || 0;
                    const total = parseFloat(this.manualTotal) || 0;
                    return qty > 0 ? Math.round(total / qty) : 0;
                },
                get recommendedSellPreview() {
                    const rawSell = this.unitCostPreview * 1.25;
                    return Math.ceil(rawSell / 500) * 500;
                },

                previewManualReceipt(event) {
                    const file = event.target.files && event.target.files[0];
                    if (file) {
                        this.manualReceiptPreview = URL.createObjectURL(file);
                    }
                },

                previewAddImage(event) {
                    const file = event.target.files && event.target.files[0];
                    if (file) {
                        this.addImagePreview = URL.createObjectURL(file);
                    }
                },

                openReceiptPreview(url, title) {
                    this.receiptModalUrl = url;
                    this.receiptModalTitle = title || 'Bukti Nota Belanja Pasar';
                    this.receiptModalOpen = true;
                },

                onManualIngredientChange() {
                    const found = this.ingredients.find(i => String(i.id) === String(this.manualIngredientId));
                    if (found) {
                        this.manualUnit = found.unit || 'Kg';
                        this.recalcConversion();
                        if (found.cost > 0) {
                            this.manualTotal = Math.round(this.manualQty * found.cost);
                        }
                    }
                },

                applyPackPreset(type, size) {
                    this.packType = type;
                    this.packSize = size;
                    if (size > 1 && this.packCount > 10) {
                        this.packCount = 1;
                    }
                    this.recalcConversion();
                },

                recalcConversion() {
                    const count = parseFloat(this.packCount) || 0;
                    const size = parseFloat(this.packSize) || 1;
                    this.manualQty = +(count * size).toFixed(2);
                    const found = this.ingredients.find(i => String(i.id) === String(this.manualIngredientId));
                    if (found && found.cost > 0) {
                        this.manualTotal = Math.round(this.manualQty * found.cost);
                    }
                },

                formatRupiah(val) {
                    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
                },

                fillSampleText() {
                    this.rawText = this.sampleText;
                },

                addEmptyRow() {
                    const defaultId = this.ingredients.length > 0 ? this.ingredients[0].id : 1;
                    this.extractedItems.push({
                        ingredient_id: defaultId,
                        quantity: 5,
                        subtotal: 75000
                    });
                },

                removeRow(idx) {
                    this.extractedItems.splice(idx, 1);
                },

                async parseTextNote() {
                    if (!this.rawText || this.rawText.trim().length < 3) return;
                    this.isParsing = true;
                    try {
                        const res = await fetch('{{ route("purchases.parse") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ note_text: this.rawText })
                        });
                        const data = await res.json();
                        if (data && Array.isArray(data.items)) {
                            this.extractedItems = data.items;
                        }
                        if (data && data.message) {
                            this.parseMessage = data.message;
                        }
                        if (data && data.source) {
                            this.parseSource = data.source;
                        }
                    } catch (e) {
                        this.parseMessage = 'Gagal memproses catatan. Menggunakan data lokal.';
                    } finally {
                        this.isParsing = false;
                    }
                },

                async uploadReceiptImage(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    this.receiptPreviewUrl = URL.createObjectURL(file);
                    this.isParsing = true;
                    this.parseMessage = 'Sedang memindai foto struk pasar dengan AI...';
                    const formData = new FormData();
                    formData.append('receipt', file);
                    try {
                        const res = await fetch('{{ route("purchases.parse") }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: formData
                        });
                        const data = await res.json();
                        if (data && Array.isArray(data.items)) {
                            this.extractedItems = data.items;
                        }
                        if (data && data.receipt_path) {
                            this.uploadedReceiptPath = data.receipt_path;
                        }
                        if (data && data.receipt_url) {
                            this.receiptPreviewUrl = data.receipt_url;
                        }
                        if (data && data.message) {
                            this.parseMessage = data.message;
                        }
                        if (data && data.source) {
                            this.parseSource = data.source;
                        }
                    } catch (e) {
                        this.parseMessage = 'Gagal memindai foto struk pasar.';
                    } finally {
                        this.isParsing = false;
                    }
                }
            };
        };

        document.addEventListener('alpine:init', () => {
            Alpine.data('purchaseManager', window.purchaseManager);
        });
    </script>
</x-app-layout>
