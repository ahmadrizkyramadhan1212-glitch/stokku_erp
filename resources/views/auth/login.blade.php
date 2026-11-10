<x-guest-layout>
    <div x-data="{
            email: '{{ old('email', 'owner@adhijaya.com') }}',
            password: 'password',
            showPassword: false,
            isDark: document.documentElement.classList.contains('dark'),
            fillDemo(demoEmail, demoPassword) {
                this.email = demoEmail;
                this.password = demoPassword;
                if (this.$refs.emailInput) this.$refs.emailInput.value = demoEmail;
                if (this.$refs.passwordInput) this.$refs.passwordInput.value = demoPassword;
            }
         }"
         class="w-full max-w-md mx-auto">

        <!-- Kartu Utama Login Bergaya Modern & Teduh (Tema Hijau Teal Warung #0D9488 - #0F766E) -->
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl shadow-teal-900/15 dark:shadow-teal-950/80 border border-slate-200 dark:border-teal-500/35 overflow-hidden transition-colors duration-200">

            <!-- Header Gradasi Hijau Teal Warung (#0D9488 - #0F766E) -->
            <div class="relative overflow-hidden bg-gradient-to-b from-[#0D9488] to-[#0F766E] text-white p-6 sm:p-8">
                <!-- Dekorasi Cahaya Halus -->
                <div class="pointer-events-none absolute -top-10 -right-10 w-36 h-36 rounded-full bg-white/15 blur-2xl"></div>
                <div class="pointer-events-none absolute -bottom-12 -left-10 w-36 h-36 rounded-full bg-white/10 blur-2xl"></div>

                <!-- Baris Atas: Brand & Tombol Toggle Mode Gelap/Terang -->
                <div class="relative z-10 flex items-center justify-between gap-2 mb-5">
                    <div class="inline-flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/15 border border-white/25 flex items-center justify-center text-white shadow-sm">
                            <span class="material-symbols-outlined text-[22px]">storefront</span>
                        </div>
                        <div>
                            <span class="text-sm font-extrabold tracking-tight text-white block leading-none">STOKKU ERP</span>
                            <span class="text-[10px] font-bold text-teal-100 uppercase tracking-wider">Warung Adhi Jaya</span>
                        </div>
                    </div>

                    <button type="button"
                            @click="isDark = window.toggleStokkuTheme()"
                            title="Ubah Mode Terang / Gelap"
                            class="w-9 h-9 rounded-full bg-white/15 hover:bg-white/25 border border-white/25 text-white flex items-center justify-center transition cursor-pointer">
                        <span class="material-symbols-outlined text-[18px]" x-text="isDark ? 'light_mode' : 'dark_mode'">dark_mode</span>
                    </button>
                </div>

                <!-- Judul Sapaan Login -->
                <div class="relative z-10">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 border border-white/25 text-[10px] font-extrabold uppercase tracking-wider text-white mb-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        Manajemen Stok &amp; Belanja Pasar
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Selamat Datang!
                    </h1>
                    <p class="text-xs sm:text-sm text-teal-50 mt-1">
                        Masuk untuk mengecek stok rak, mencatat belanja pasar, dan melihat keuntungan warung.
                    </p>
                </div>
            </div>

            <!-- Bagian Form Input Bulat Bersih -->
            <div class="p-6 sm:p-8 space-y-5">
                <!-- Session Status -->
                <x-auth-session-status class="mb-2" :status="session('status')" />

                @if(session('warning'))
                    <div class="mb-3 px-4 py-3 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-300 dark:border-amber-600/50 text-amber-900 dark:text-amber-200 text-xs font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-amber-600 dark:text-amber-400">info</span>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif

                <!-- Shortcut Akun Demo 1-Klik (Owner & Staf Gudang) -->
                <div>
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-[#0D9488] dark:text-teal-300 block mb-2">
                        Akses Cepat Akun Warung (Klik untuk Isi Otomatis):
                    </span>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button"
                                @click="fillDemo('owner@adhijaya.com', 'password')"
                                class="p-2.5 rounded-2xl bg-teal-50/90 dark:bg-slate-950 hover:bg-teal-100 dark:hover:bg-teal-950/60 border border-slate-200 dark:border-slate-700 text-left transition cursor-pointer group">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-[16px]">verified_user</span>
                                </span>
                                <div class="min-w-0">
                                    <span class="text-xs font-extrabold text-slate-900 dark:text-slate-100 block truncate">Owner Warung</span>
                                    <span class="text-[10px] text-[#0D9488] dark:text-teal-300 font-semibold block truncate">Akses Penuh &amp; Laba</span>
                                </div>
                            </div>
                        </button>

                        <button type="button"
                                @click="fillDemo('staf@adhijaya.com', 'password')"
                                class="p-2.5 rounded-2xl bg-teal-50/90 dark:bg-slate-950 hover:bg-teal-100 dark:hover:bg-teal-950/60 border border-slate-200 dark:border-slate-700 text-left transition cursor-pointer group">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-xl bg-gradient-to-tr from-[#0D9488] to-emerald-500 text-white flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-[16px]">inventory_2</span>
                                </span>
                                <div class="min-w-0">
                                    <span class="text-xs font-extrabold text-slate-900 dark:text-slate-100 block truncate">Staf Gudang</span>
                                    <span class="text-[10px] text-[#0D9488] dark:text-teal-300 font-semibold block truncate">Cek Stok &amp; Belanja</span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <!-- Input Email -->
                    <div>
                        <label for="email" class="block text-xs font-extrabold text-slate-900 dark:text-slate-100 mb-1.5">
                            Alamat Email Akun
                        </label>
                        <div class="relative">
                            <span class="material-symbols-outlined text-[#0D9488] dark:text-teal-300 absolute left-3.5 top-1/2 -translate-y-1/2 text-[19px]">mail</span>
                            <input id="email"
                                   x-ref="emailInput"
                                   x-model="email"
                                   type="email"
                                   name="email"
                                   value="{{ old('email', 'owner@adhijaya.com') }}"
                                   required
                                   autofocus
                                   autocomplete="username"
                                   placeholder="owner@adhijaya.com"
                                   class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 pl-10 pr-4 py-3 text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0D9488] transition" />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-rose-600" />
                    </div>

                    <!-- Input Kata Sandi -->
                    <div>
                        <label for="password" class="block text-xs font-extrabold text-slate-900 dark:text-slate-100 mb-1.5">
                            Kata Sandi
                        </label>
                        <div class="relative">
                            <span class="material-symbols-outlined text-[#0D9488] dark:text-teal-300 absolute left-3.5 top-1/2 -translate-y-1/2 text-[19px]">lock</span>
                            <input id="password"
                                   x-ref="passwordInput"
                                   x-model="password"
                                   :type="showPassword ? 'text' : 'password'"
                                   name="password"
                                   value="password"
                                   required
                                   autocomplete="current-password"
                                   placeholder="••••••••"
                                   class="w-full rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 pl-10 pr-10 py-3 text-xs sm:text-sm font-bold text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0D9488] transition" />
                            <button type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-[#0D9488] dark:hover:text-teal-400 cursor-pointer">
                                <span class="material-symbols-outlined text-[19px]" x-text="showPassword ? 'visibility_off' : 'visibility'">visibility</span>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-600" />
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between pt-0.5">
                        <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                            <input id="remember_me"
                                   type="checkbox"
                                   name="remember"
                                   checked
                                   class="rounded-md border-teal-300 text-[#0D9488] shadow-2xs focus:ring-[#0D9488]">
                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">Ingat saya di perangkat ini</span>
                        </label>
                    </div>

                    <!-- Tombol Login Hijau Teal Warung (#0D9488 - #0F766E) -->
                    <div class="pt-2">
                        <button type="submit"
                                class="w-full py-3.5 px-5 rounded-2xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 text-white text-sm font-extrabold shadow-lg shadow-teal-600/35 flex items-center justify-center gap-2 transition active:scale-[0.99] cursor-pointer">
                            <span>Masuk ke Aplikasi Warung</span>
                            <span class="material-symbols-outlined text-[19px]">arrow_forward</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
