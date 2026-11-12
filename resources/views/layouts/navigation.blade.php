@php
    $user = Auth::user();
    $isOwner = $user ? $user->isOwner() : false;
    $userName = $user->name ?? 'Owner Adhi Jaya';
    $userRole = $isOwner ? 'Owner Warung' : 'Staf Gudang';
@endphp

<!-- =======================================================================
     DESKTOP / IPAD FIXED LEFT SIDEBAR (>= md)
     Tema: Hijau Teal Warung (#0D9488 - #0F766E)
     ======================================================================= -->
<aside class="hidden md:flex fixed left-0 top-0 h-full w-64 lg:w-72 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-400 border-r border-slate-200 dark:border-slate-800 z-50 flex-col justify-between shadow-sm select-none">
    <div class="flex flex-col overflow-y-auto">
        <!-- Brand Header -->
        <a href="{{ route('dashboard') }}" class="p-6 lg:p-6 flex items-center gap-3 hover:opacity-95 transition-opacity">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#0D9488] to-[#0F766E] flex items-center justify-center text-white shrink-0 shadow-lg shadow-teal-600/35 border border-white/25">
                <span class="material-symbols-outlined text-[22px]">storefront</span>
            </div>
            <div class="flex flex-col overflow-hidden">
                <div class="flex items-center gap-1.5">
                    <span class="text-base text-slate-900 dark:text-slate-100 font-extrabold tracking-tight truncate">STOKKU ERP</span>
                    <span class="px-2 py-0.5 rounded-full bg-teal-50 dark:bg-teal-500/20 border border-slate-200 dark:border-teal-400/30 text-teal-700 dark:text-teal-300 text-[10px] font-bold uppercase tracking-wider">Adhi Jaya</span>
                </div>
                <span class="text-xs text-slate-500 dark:text-slate-400 truncate">Manajemen Stok &amp; Belanja Warung</span>
            </div>
        </a>

        <!-- Active User & Shift Badge -->
        <div class="mx-4 lg:mx-5 mb-4 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 shrink-0 animate-pulse"></span>
                <div class="flex flex-col truncate">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate">{{ $userName }}</span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ $userRole }} • Aktif</span>
                </div>
            </div>
            <span class="material-symbols-outlined text-teal-400 text-[18px] shrink-0">verified</span>
        </div>

        <!-- Sidebar Navigation Links (5 Menu Utama Bersih & Aktif) -->
        <nav class="px-3 lg:px-4 flex flex-col gap-1.5" aria-label="Menu Utama">
            <x-nav-item :href="route('dashboard')" icon="home" label="Beranda" :active="request()->routeIs('dashboard')" />
            <x-nav-item :href="route('inventory.materials')" icon="stock" label="Stok" :active="request()->routeIs('inventory.materials') || request()->routeIs('inventory.index')" />
            <x-nav-item :href="route('purchases.index')" icon="shopping" label="Belanja" :active="request()->routeIs('purchases.*') || request()->routeIs('inventory.shopping*')" />
            @if($isOwner)
                <x-nav-item :href="route('reports.index')" icon="report" label="Laporan" :active="request()->routeIs('reports.*') || request()->routeIs('analysis.*')" />
            @endif
            <x-nav-item :href="route('spk.index')" icon="priority" label="Prioritas Beli" :active="request()->routeIs('spk.*')" />
        </nav>
    </div>

    <!-- Bottom Status & Logout -->
    <div class="p-4 lg:p-5 flex flex-col gap-3 border-t border-slate-200 dark:border-slate-800">
        <div class="flex flex-col gap-1.5 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-teal-400">sync</span>
                    <span class="text-[11px]">Sinkronisasi Stok</span>
                </div>
                <span class="text-[11px] font-bold text-slate-800 dark:text-slate-100 tabular-nums">{{ now()->format('H:i') }} WIB</span>
            </div>
            <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[16px] text-emerald-400">chat</span>
                    <span class="text-[11px]">Rekap WA</span>
                </div>
                <span class="text-[11px] text-emerald-400 font-bold">23:00 Siap Kirim</span>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-2xl bg-rose-50 dark:bg-rose-500/15 text-rose-600 dark:text-rose-300 hover:bg-rose-600 hover:text-white border border-rose-200 dark:border-rose-500/30 transition-all text-xs font-extrabold active:scale-95 cursor-pointer">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                    <span>Keluar Sistem</span>
                </span>
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            </button>
        </form>
    </div>
</aside>

<!-- =======================================================================
     DESKTOP / IPAD TOP BAR (>= md)
     ======================================================================= -->
<header x-data="{ isDark: document.documentElement.classList.contains('dark') }"
        @stokku-theme-changed.window="isDark = $event.detail.dark"
        class="hidden md:flex fixed top-0 md:left-64 lg:left-72 right-0 h-16 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border-b border-slate-200 dark:border-slate-700 shadow-sm z-40 items-center justify-between px-6 transition-colors duration-200">
    <div class="flex items-center gap-6 flex-1 max-w-2xl mr-4">
        <div class="flex flex-col min-w-0">
            <span class="text-[10px] text-[#0D9488] dark:text-teal-300 uppercase tracking-wider font-extrabold">
                Warung Sembako &amp; Sayur Adhi Jaya
            </span>
            <span class="text-sm text-slate-900 dark:text-slate-100 font-extrabold truncate">Selamat Datang, {{ $userName }}</span>
        </div>
        <form action="{{ route('inventory.materials') }}" method="GET" class="relative flex-1 hidden lg:block">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#0D9488] dark:text-teal-300 text-[18px]">search</span>
            <input name="q" type="text"
                   class="w-full h-10 pl-9 pr-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0D9488] transition-all"
                   placeholder="Cari komoditas sayur, bumbu, atau sembako..." />
        </form>
    </div>

    <div class="flex items-center gap-3 ml-6 lg:ml-8">
        <!-- Tombol Toggle Mode Gelap/Terang (Desktop) -->
        <button type="button"
                @click="isDark = window.toggleStokkuTheme()"
                class="w-10 h-10 rounded-full bg-teal-50 dark:bg-slate-950 text-[#0D9488] dark:text-amber-300 border border-slate-200 dark:border-slate-700 hover:bg-teal-100 dark:hover:bg-teal-950/60 flex items-center justify-center transition cursor-pointer shadow-xs"
                title="Ubah Mode Terang / Gelap">
            <span class="material-symbols-outlined text-[20px]" x-text="isDark ? 'light_mode' : 'dark_mode'">dark_mode</span>
        </button>

        <div class="flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-teal-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 text-xs font-extrabold border border-slate-200 dark:border-slate-700 shadow-xs">
            <span class="material-symbols-outlined text-[16px] text-[#0D9488] dark:text-teal-300">calendar_today</span>
            <span>{{ now()->translatedFormat('d M Y') }}</span>
        </div>

        <a href="{{ route('profile.edit') }}" title="Profil Akun"
           class="w-10 h-10 rounded-full bg-gradient-to-tr from-[#0D9488] to-[#0F766E] text-white flex items-center justify-center shadow-md shadow-teal-600/30 hover:opacity-90 transition border border-white/25">
            <span class="material-symbols-outlined text-[18px]">person</span>
        </a>
    </div>
</header>

<!-- =======================================================================
     FLOATING PILL BOTTOM BAR (SLEEK ICON-ONLY PILL) + TOMBOL BULAT AI "FALAH"
     Persis gambar referensi: Kapsul putih mengambang murni Icon-Only tanpa teks label
     ======================================================================= -->
<div class="md:hidden fixed bottom-4 left-4 right-4 z-40 flex items-center justify-between gap-3 max-w-md mx-auto pointer-events-none">
    @php
        $navIsDashboard = request()->routeIs('dashboard');
        $navIsInventory = request()->routeIs('inventory.materials') || request()->routeIs('inventory.index');
        $navIsPurchases = request()->routeIs('purchases.*') || request()->routeIs('inventory.shopping*');
        $navIsReports   = request()->routeIs('reports.*') || request()->routeIs('spk.*') || request()->routeIs('analysis.*');
    @endphp

    <!-- A. Kapsul Putih/Gelap Mengambang (Icon-Only Pill: Beranda, Stok, Belanja, Laporan / Prioritas) -->
    <nav aria-label="Navigasi Utama"
         class="pointer-events-auto bg-white dark:bg-slate-900 backdrop-blur-xl rounded-full px-3 py-2 shadow-[0_8px_30px_rgb(0,0,0,0.08)] border border-slate-200 dark:border-slate-700 flex items-center justify-between flex-1 transition-colors duration-200">
        <x-nav-item variant="bottom" :href="route('dashboard')" icon="home" label="Beranda" :active="$navIsDashboard" />
        <x-nav-item variant="bottom" :href="route('inventory.materials')" icon="stock" label="Stok Warung" :active="$navIsInventory" />
        <x-nav-item variant="bottom" :href="route('purchases.index')" icon="shopping" label="Belanja Pasar" :active="$navIsPurchases" />
        @if($isOwner)
            <x-nav-item variant="bottom" :href="route('reports.index')" icon="report" label="Laporan" :active="$navIsReports" />
        @else
            <x-nav-item variant="bottom" :href="route('spk.index')" icon="priority" label="Prioritas Beli" :active="request()->routeIs('spk.*')" />
        @endif
    </nav>

    <!-- B. Tombol Bulat Tanya Stokku AI di Kanan Bawah (100% Vector SVG Custom Karakter Falah + Bintang AI) -->
    <button type="button"
            onclick="window.dispatchEvent(new CustomEvent('toggle-falah-ai'))"
            title="Buka Tanya Stokku AI (Google Gemini 2.5 Flash)"
            aria-label="Buka Tanya Stokku AI"
            class="pointer-events-auto w-14 h-14 rounded-full bg-gradient-to-tr from-[#0F766E] via-[#0D9488] to-emerald-500 shadow-xl shadow-teal-600/40 border border-white/30 flex items-center justify-center text-white shrink-0 hover:scale-105 active:scale-95 transition-all cursor-pointer">
        <svg class="w-9 h-9" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <!-- Bintang AI Vector di Sudut Kanan Atas -->
            <path d="M51 8L52.6 12.4L57 14L52.6 15.6L51 20L49.4 15.6L45 14L49.4 12.4L51 8Z" fill="#FBBF24"/>
            <circle cx="14" cy="14" r="1.8" fill="#CCFBF1" opacity="0.85"/>
            <!-- Peci / Kopiah Gelap dengan Pita Emas -->
            <path d="M22 14C22 12.3431 23.3431 11 25 11H39C40.6569 11 42 12.3431 42 14L44 21H20L22 14Z" fill="#134E4A"/>
            <rect x="19" y="20" width="26" height="3.5" rx="1.75" fill="#FBBF24"/>
            <!-- Badan Kotak Stok Putih Bersudut Membulat -->
            <rect x="14" y="23" width="36" height="31" rx="8" fill="#FFFFFF"/>
            <!-- Tutup Atas Kotak Mint Tipis -->
            <path d="M14 30C14 26.134 17.134 23 21 23H43C46.866 23 50 26.134 50 30V32C50 33.1046 49.1046 34 48 34H16C14.8954 34 14 33.1046 14 32V30Z" fill="#CCFBF1"/>
            <!-- Pita Vertikal Kuning Emas di Tengah Atas Kotak -->
            <rect x="29" y="22" width="6" height="12" rx="1.5" fill="#FBBF24"/>
            <!-- Mata Bulat Gelap + Pantulan Cahaya Putih -->
            <circle cx="24.5" cy="41.5" r="3.2" fill="#0F172A"/>
            <circle cx="23.5" cy="40.5" r="1.1" fill="#FFFFFF"/>
            <circle cx="39.5" cy="41.5" r="3.2" fill="#0F172A"/>
            <circle cx="38.5" cy="40.5" r="1.1" fill="#FFFFFF"/>
            <!-- Pipi Pink Lembut -->
            <circle cx="19.5" cy="45" r="2.2" fill="#F472B6" opacity="0.75"/>
            <circle cx="44.5" cy="45" r="2.2" fill="#F472B6" opacity="0.75"/>
            <!-- Mulut Tersenyum Ramah -->
            <path d="M28.5 45.5C28.5 45.5 30 48 32 48C34 48 35.5 45.5 35.5 45.5H28.5Z" fill="#0F172A"/>
        </svg>
    </button>
</div>

<!-- Tombol Bulat Tanya Stokku AI untuk Layar Desktop (>= md) (100% Vector SVG Custom Karakter Falah) -->
<button type="button"
        onclick="window.dispatchEvent(new CustomEvent('toggle-falah-ai'))"
        title="Buka Tanya Stokku AI (Google Gemini 2.5 Flash)"
        aria-label="Buka Tanya Stokku AI"
        class="hidden md:flex fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full bg-gradient-to-tr from-[#0F766E] via-[#0D9488] to-emerald-500 shadow-xl shadow-teal-600/40 border border-white/30 items-center justify-center text-white hover:scale-105 active:scale-95 transition-all cursor-pointer">
    <svg class="w-9 h-9" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <!-- Bintang AI Vector di Sudut Kanan Atas -->
        <path d="M51 8L52.6 12.4L57 14L52.6 15.6L51 20L49.4 15.6L45 14L49.4 12.4L51 8Z" fill="#FBBF24"/>
        <circle cx="14" cy="14" r="1.8" fill="#CCFBF1" opacity="0.85"/>
        <!-- Peci / Kopiah Gelap dengan Pita Emas -->
        <path d="M22 14C22 12.3431 23.3431 11 25 11H39C40.6569 11 42 12.3431 42 14L44 21H20L22 14Z" fill="#134E4A"/>
        <rect x="19" y="20" width="26" height="3.5" rx="1.75" fill="#FBBF24"/>
        <!-- Badan Kotak Stok Putih Bersudut Membulat -->
        <rect x="14" y="23" width="36" height="31" rx="8" fill="#FFFFFF"/>
        <!-- Tutup Atas Kotak Mint Tipis -->
        <path d="M14 30C14 26.134 17.134 23 21 23H43C46.866 23 50 26.134 50 30V32C50 33.1046 49.1046 34 48 34H16C14.8954 34 14 33.1046 14 32V30Z" fill="#CCFBF1"/>
        <!-- Pita Vertikal Kuning Emas di Tengah Atas Kotak -->
        <rect x="29" y="22" width="6" height="12" rx="1.5" fill="#FBBF24"/>
        <!-- Mata Bulat Gelap + Pantulan Cahaya Putih -->
        <circle cx="24.5" cy="41.5" r="3.2" fill="#0F172A"/>
        <circle cx="23.5" cy="40.5" r="1.1" fill="#FFFFFF"/>
        <circle cx="39.5" cy="41.5" r="3.2" fill="#0F172A"/>
        <circle cx="38.5" cy="40.5" r="1.1" fill="#FFFFFF"/>
        <!-- Pipi Pink Lembut -->
        <circle cx="19.5" cy="45" r="2.2" fill="#F472B6" opacity="0.75"/>
        <circle cx="44.5" cy="45" r="2.2" fill="#F472B6" opacity="0.75"/>
        <!-- Mulut Tersenyum Ramah -->
        <path d="M28.5 45.5C28.5 45.5 30 48 32 48C34 48 35.5 45.5 35.5 45.5H28.5Z" fill="#0F172A"/>
    </svg>
</button>
