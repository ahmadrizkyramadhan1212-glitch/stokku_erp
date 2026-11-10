<x-app-layout>
    <div class="flex flex-col gap-5 pb-8">
        <!-- Header Banner -->
        <section class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/70 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-headline-md text-xl text-[#0F172A] font-bold tracking-tight dark:text-slate-100">Manajemen Stok &amp; Gudang Warung</h1>
                    <span class="px-2.5 py-0.5 rounded-full bg-[#0D9488]/10 text-[#0D9488] text-[10px] font-bold uppercase tracking-wider">STOKKU ERP</span>
                </div>
                <p class="font-body-sm text-xs text-[#64748B] mt-1">
                    Kelola stok curah pasar (1-Tap Status &amp; FIFO), kemas ulang plastik eceran, dan catat barang baru.
                </p>
            </div>

            <a href="{{ route('inventory.materials', ['action' => 'add']) }}"
               class="inline-flex items-center justify-center gap-2 h-11 px-4 rounded-xl bg-[#0D9488] hover:bg-[#0F766E] text-white font-label-lg text-xs font-bold shadow-sm active:scale-95 transition-all shrink-0">
                <span class="material-symbols-outlined text-[20px]">add_circle</span>
                <span>+ Tambah Barang Baru</span>
            </a>
        </section>

        <!-- 3 Main Module Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Card 1: Gudang Komoditas & 1-Tap Status -->
            <a href="{{ route('inventory.materials') }}"
               class="relative overflow-hidden rounded-3xl p-6 bg-[#0D9488] text-white shadow-md hover:shadow-lg active:scale-[0.99] transition-all flex flex-col justify-between min-h-[190px]">
                <div class="relative z-10">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="px-3 py-1 rounded-full bg-white/20 text-[11px] font-bold uppercase tracking-wider">
                            Gudang &amp; Pasar Induk
                        </span>
                        <span class="px-2.5 py-1 rounded-full bg-[#FEE2E2] text-[#991B1B] text-[11px] font-bold">
                            {{ $criticalCount }} Stok Kritis
                        </span>
                    </div>
                    <h2 class="text-xl font-extrabold tracking-tight">Cek Stok Warung (1-Tap) &amp; FIFO</h2>
                    <p class="text-teal-50 text-xs mt-1.5 leading-relaxed max-w-md">
                        Update status stok instan (Aman / Menipis / Habis), pantau peringatan FIFO barang sisa kemarin, dan koreksi susut barang busuk.
                    </p>
                </div>

                <div class="relative z-10 mt-5 flex items-center justify-between">
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold bg-white text-[#0D9488] px-3.5 py-2 rounded-xl shadow-xs">
                        <span>Buka Cek Stok 1-Tap</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </span>
                    <span class="text-xs text-teal-100 font-semibold">{{ $ingredients->count() }} Komoditas Aktif</span>
                </div>

                <span aria-hidden="true" class="material-symbols-outlined absolute -bottom-5 -right-4 text-white/15 text-[128px] select-none pointer-events-none">
                    inventory_2
                </span>
            </a>

            <!-- Card 2: Scan Struk Pasar & Kulakan AI -->
            <a href="{{ route('purchases.index') }}"
               class="relative overflow-hidden rounded-3xl p-6 bg-[#0F172A] text-white shadow-md hover:shadow-lg active:scale-[0.99] transition-all flex flex-col justify-between min-h-[190px]">
                <div class="relative z-10">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="px-3 py-1 rounded-full bg-[#0D9488]/30 text-[#5EEAD4] text-[11px] font-bold uppercase tracking-wider">
                            AI OCR &amp; Moving Average
                        </span>
                        <span class="px-2.5 py-1 rounded-full bg-white/10 text-white text-[11px] font-bold">
                            Kulakan Pagi
                        </span>
                    </div>
                    <h2 class="text-xl font-extrabold tracking-tight">Scan Struk Pasar &amp; Kulakan AI</h2>
                    <p class="text-slate-300 text-xs mt-1.5 leading-relaxed max-w-md">
                        Foto nota pasar atau ketik cepat hasil belanja pagi. Otomatis restock gudang, ubah status Aman, dan hitung HPP Moving Average.
                    </p>
                </div>

                <div class="relative z-10 mt-5 flex items-center justify-between">
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold bg-[#0D9488] text-white px-3.5 py-2 rounded-xl shadow-xs">
                        <span>Scan / Ketik Struk</span>
                        <span class="material-symbols-outlined text-[16px]">document_scanner</span>
                    </span>
                    <span class="text-xs text-[#5EEAD4] font-semibold">Auto Restock</span>
                </div>

                <span aria-hidden="true" class="material-symbols-outlined absolute -bottom-5 -right-4 text-white/10 text-[128px] select-none pointer-events-none">
                    receipt_long
                </span>
            </a>

            <!-- Card 3: Kemas Ulang / Etalase Siap Jual -->
            <a href="{{ route('inventory.production') }}"
               class="relative overflow-hidden rounded-3xl p-6 bg-[#2563EB] text-white shadow-md hover:shadow-lg active:scale-[0.99] transition-all flex flex-col justify-between min-h-[190px]">
                <div class="relative z-10">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="px-3 py-1 rounded-full bg-white/20 text-[11px] font-bold uppercase tracking-wider">
                            Repacking &amp; Kontrol FIFO
                        </span>
                        <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-900 text-[11px] font-bold">
                            {{ $fifoCount }} Wajib FIFO
                        </span>
                    </div>
                    <h2 class="text-xl font-extrabold tracking-tight">Kemas Ulang Eceran &amp; Stok Etalase</h2>
                    <p class="text-blue-100 text-xs mt-1.5 leading-relaxed max-w-md">
                        Kemas stok curah gudang (mis. kupas jagung sisa kemarin ke kantong plastik eceran) dan kelola kapasitas fisik barang keluar.
                    </p>
                </div>

                <div class="relative z-10 mt-5 flex items-center justify-between">
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold bg-white text-[#2563EB] px-3.5 py-2 rounded-xl shadow-xs">
                        <span>Buka Kemas Ulang</span>
                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                    </span>
                    <span class="text-xs text-blue-100 font-semibold">{{ $menusCount }} Item Etalase</span>
                </div>

                <span aria-hidden="true" class="material-symbols-outlined absolute -bottom-5 -right-4 text-white/15 text-[128px] select-none pointer-events-none">
                    package_2
                </span>
            </a>
        </div>
    </div>
</x-app-layout>
