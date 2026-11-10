@php
    $user = Auth::user();
    $isOwner = $user ? $user->isOwner() : false;
    $waCardData = $waCard ?? [];
    $waPhone = $waCardData['phone'] ?? '6281234567890';
    $waDisplayPhone = $waCardData['display_phone'] ?? '0812-3456-7890';
    $waDateLabel = $waCardData['date_label'] ?? now()->translatedFormat('d F Y');
    $waTimeLabel = $waCardData['time_label'] ?? now()->format('H:i');
    $waRevenue = (float) ($waCardData['today_revenue'] ?? 0);
    $waExpenses = (float) ($waCardData['today_expenses'] ?? 0);
    $waPurchases = (float) ($waCardData['today_purchases'] ?? 0);
    $waProfit = (float) ($waCardData['today_net_profit'] ?? 0);
    $waAssetValue = (float) ($waCardData['total_asset_value'] ?? 0);
    $waCriticalCount = (int) ($waCardData['critical_count'] ?? (($waCardData['habis_count'] ?? 0) + ($waCardData['menipis_count'] ?? 0)));
    $waFifoCount = (int) ($waCardData['fifo_count'] ?? 0);
    $waTopSaw = $waCardData['top_saw'] ?? collect();
    $waFifoItems = $waCardData['fifo_items'] ?? collect();
    $waDefaultText = $waCardData['message'] ?? $waCardData['wa_text'] ?? "*LAPORAN TUTUP WARUNG - ADHI JAYA*\nWarung Sembako & Sayur Adhi Jaya";
    $cardId = 'wa_card_' . ($variant ?? 'default');
@endphp

<section x-data="{
        phone: @js($waPhone),
        messageText: @js($waDefaultText),
        copied: false,
        showPreview: false,
        get waLink() {
            const cleaned = String(this.phone || '6281234567890').replace(/[^0-9]/g, '');
            const normalized = cleaned.startsWith('0') ? ('62' + cleaned.slice(1)) : cleaned;
            return 'https://wa.me/' + normalized + '?text=' + encodeURIComponent(this.messageText);
        },
        copyMessage() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(this.messageText);
                this.copied = true;
                setTimeout(() => this.copied = false, 2200);
            }
        }
    }"
    id="{{ $cardId }}"
    class="relative overflow-hidden rounded-3xl p-6 sm:p-6 bg-gradient-to-b from-[#0D9488] to-[#0F766E] text-white shadow-xl shadow-teal-600/30 border border-white/25 flex flex-col gap-5">

    <!-- Dekorasi Kilau Glossy -->
    <div class="pointer-events-none absolute -top-12 -right-12 w-44 h-44 rounded-full bg-white/15 blur-2xl"></div>
    <div class="pointer-events-none absolute -bottom-14 -left-10 w-40 h-40 rounded-full bg-white/15 blur-2xl"></div>

    <!-- Top Header & Status Badge -->
    <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md border border-white/30 flex items-center justify-center shrink-0 shadow-md">
                <span class="material-symbols-outlined text-[26px] text-white">chat</span>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <span class="px-2.5 py-0.5 rounded-full bg-white/20 backdrop-blur-md text-white text-[10px] font-extrabold uppercase tracking-wider border border-white/30 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        Rekap Tutup Warung 23:00 WIB
                    </span>
                    <span class="text-[11px] font-semibold text-white/90">
                        {{ $waDateLabel }} • {{ $waTimeLabel }} WIB
                    </span>
                </div>
                <h2 class="text-base sm:text-lg font-extrabold tracking-tight text-white">
                    Laporan Tutup Warung &amp; Daftar Belanja ke WhatsApp Owner
                </h2>
                <p class="text-xs text-white/90 leading-relaxed max-w-2xl mt-0.5">
                    Rekap otomatis kondisi rak malam ini, barang prioritas belanja esok pagi, pengingat <strong>Sisa Kemarin - Jual Duluan</strong>, serta ringkasan pemasukan &amp; keuntungan bersih warung.
                </p>
            </div>
        </div>

        <!-- Nomor Tujuan WA -->
        <div class="bg-white/15 backdrop-blur-md border border-white/25 rounded-2xl p-3 flex flex-col gap-1.5 min-w-[230px]">
            <label class="text-[10px] font-extrabold uppercase tracking-wider text-white flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">call</span>
                <span>Nomor WhatsApp Owner Warung</span>
            </label>
            <div class="flex items-center gap-2">
                <input type="text"
                       x-model="phone"
                       class="w-full bg-black/25 border border-white/25 rounded-xl px-3 py-1.5 text-xs font-extrabold text-white placeholder:text-white/50 focus:outline-none focus:ring-2 focus:ring-white tabular-nums"
                       placeholder="6281234567890" />
            </div>
            <span class="text-[10px] text-white/80">Default: {{ $waDisplayPhone }} (Bisa diganti langsung)</span>
        </div>
    </div>

    <!-- 4 Mini Summary Pills -->
    <div class="relative z-10 grid grid-cols-2 sm:grid-cols-4 gap-3">
        @if($isOwner)
            <div class="bg-white/15 backdrop-blur-md border border-white/25 rounded-2xl p-3">
                <span class="text-[10px] font-bold uppercase tracking-wider text-white/85 block">Omzet Hari Ini</span>
                <span class="text-sm sm:text-base font-extrabold text-white tabular-nums mt-0.5 block">
                    Rp {{ number_format($waRevenue, 0, ',', '.') }}
                </span>
                <span class="text-[10px] text-white/85">Belanja: Rp {{ number_format($waPurchases, 0, ',', '.') }}</span>
            </div>

            <div class="bg-white/15 backdrop-blur-md border border-white/25 rounded-2xl p-3">
                <span class="text-[10px] font-bold uppercase tracking-wider text-white/85 block">Untung Bersih</span>
                <span class="text-sm sm:text-base font-extrabold text-white tabular-nums mt-0.5 block">
                    Rp {{ number_format($waProfit, 0, ',', '.') }}
                </span>
                <span class="text-[10px] text-white/85">Beban Ops: Rp {{ number_format($waExpenses, 0, ',', '.') }}</span>
            </div>
        @else
            <div class="bg-white/15 backdrop-blur-md border border-white/25 rounded-2xl p-3">
                <span class="text-[10px] font-bold uppercase tracking-wider text-white/85 block">Stok Habis</span>
                <span class="text-sm sm:text-base font-extrabold text-rose-300 tabular-nums mt-0.5 block">
                    {{ $waCardData['habis_count'] ?? 0 }} Barang
                </span>
                <span class="text-[10px] text-white/85">Wajib Dibeli Subuh</span>
            </div>

            <div class="bg-white/15 backdrop-blur-md border border-white/25 rounded-2xl p-3">
                <span class="text-[10px] font-bold uppercase tracking-wider text-white/85 block">Stok Menipis</span>
                <span class="text-sm sm:text-base font-extrabold text-amber-300 tabular-nums mt-0.5 block">
                    {{ $waCardData['menipis_count'] ?? 0 }} Barang
                </span>
                <span class="text-[10px] text-white/85">Di Bawah Batas Aman</span>
            </div>
        @endif

        <div class="bg-white/15 backdrop-blur-md border border-white/25 rounded-2xl p-3">
            <span class="text-[10px] font-bold uppercase tracking-wider text-white/85 block">Wajib Kulakan</span>
            <span class="text-sm sm:text-base font-extrabold text-white tabular-nums mt-0.5 block">
                {{ $waCriticalCount }} Barang
            </span>
            <span class="text-[10px] text-white/85">Stok Habis &amp; Menipis</span>
        </div>

        <div class="bg-white/15 backdrop-blur-md border border-white/25 rounded-2xl p-3">
            <span class="text-[10px] font-bold uppercase tracking-wider text-white/85 block">Sisa Kemarin - Jual Duluan</span>
            <span class="text-sm sm:text-base font-extrabold text-white tabular-nums mt-0.5 block">
                {{ $waFifoCount }} Komoditas
            </span>
            <span class="text-[10px] text-white/85">@if($isOwner) Nilai Aset: Rp {{ number_format($waAssetValue, 0, ',', '.') }} @else Prioritas Rak Depan @endif</span>
        </div>
    </div>

    <!-- Action Bar: Kirim Sekarang via WhatsApp + Salin Teks + Toggle Preview -->
    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1 border-t border-white/20">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    @click="showPreview = !showPreview"
                    class="px-3.5 py-2.5 rounded-xl bg-white/20 hover:bg-white/30 backdrop-blur-md text-white text-xs font-bold border border-white/30 inline-flex items-center gap-1.5 transition cursor-pointer">
                <span class="material-symbols-outlined text-[16px]" x-text="showPreview ? 'visibility_off' : 'visibility'">visibility</span>
                <span x-text="showPreview ? 'Tutup Isi Pesan WA' : 'Lihat / Edit Isi Pesan WA'">Lihat / Edit Isi Pesan WA</span>
            </button>

            <button type="button"
                    @click="copyMessage()"
                    class="px-3.5 py-2.5 rounded-xl bg-white/20 hover:bg-white/30 backdrop-blur-md text-white text-xs font-bold border border-white/30 inline-flex items-center gap-1.5 transition cursor-pointer">
                <span class="material-symbols-outlined text-[16px]" x-text="copied ? 'check' : 'content_copy'">content_copy</span>
                <span x-text="copied ? 'Teks Disalin!' : 'Salin Teks WA'">Salin Teks WA</span>
            </button>
        </div>

        <a :href="waLink"
           target="_blank"
           rel="noopener"
           class="py-3 px-5 rounded-2xl bg-white text-[#0F766E] hover:bg-teal-50 text-xs sm:text-sm font-extrabold shadow-lg flex items-center justify-center gap-2 transition active:scale-95">
            <svg class="w-4 h-4 text-[#0D9488] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m22 2-7 20-4-9-9-4Z"/>
                <path d="M22 2 11 13"/>
            </svg>
            <span>Kirim Rincian Stok &amp; Daftar Belanja ke WA Owner</span>
        </a>
    </div>

    <!-- Editable Message Preview Box -->
    <div x-show="showPreview" x-transition style="display: none;" class="relative z-10 bg-black/30 rounded-2xl p-4 border border-white/25">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[11px] font-bold text-white flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[15px]">edit_note</span>
                Pratinjau Pesan WhatsApp (Dapat diedit sebelum dikirim):
            </span>
            <span class="text-[10px] text-white/80">Format tebal otomatis (*teks*) di WhatsApp</span>
        </div>
        <textarea x-model="messageText"
                  rows="10"
                  class="w-full bg-black/35 border border-white/20 rounded-xl p-3 text-xs font-mono text-white leading-relaxed focus:outline-none focus:ring-2 focus:ring-white"></textarea>
    </div>
</section>
