{{-- Bottom Sheet / Modal Asisten AI "Tanya Stokku" (Google Gemini 2.5 Flash) --}}
<div x-data="falahAiChat()"
     @toggle-falah-ai.window="isOpen = !isOpen"
     class="relative z-50">

    {{-- Backdrop Gelap Halus Saat Modal Falah Dibuka --}}
    <div x-show="isOpen"
         x-cloak
         style="display: none;"
         @click="isOpen = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 backdrop-blur-xs z-40"></div>

    {{-- BOTTOM SHEET / MODAL MODERN ASISTEN AI "FALAH" --}}
    <div x-show="isOpen"
         x-cloak
         style="display: none;"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 translate-y-8 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-8 sm:scale-95"
         class="fixed bottom-0 sm:bottom-6 right-0 sm:right-6 left-0 sm:left-auto z-50 w-full sm:w-[440px] max-h-[90vh] bg-white dark:bg-slate-900 rounded-t-3xl sm:rounded-3xl shadow-2xl shadow-teal-950/70 border border-slate-200 dark:border-teal-500/35 flex flex-col overflow-hidden">

        {{-- ===============================================================
             HEADER TEAL WARUNG (#0D9488 - #0F766E) & ILUSTRASI VECTOR SVG "FALAH"
             Sesuai Gambar Referensi: Layout 2 Kolom (Maskot SVG Kiri + Identitas Kanan)
             =============================================================== --}}
        <div class="bg-gradient-to-b from-[#0D9488] to-[#0F766E] text-white rounded-t-3xl p-6 shadow-inner relative overflow-hidden shrink-0">
            {{-- Dekorasi Kilau Cahaya --}}
            <div class="pointer-events-none absolute -top-12 -right-12 w-44 h-44 rounded-full bg-white/15 blur-2xl"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-12 w-40 h-40 rounded-full bg-white/10 blur-2xl"></div>

            {{-- Tombol Reset & Tutup di Pojok Kanan Atas Header (100% Vector SVG) --}}
            <div class="absolute top-3.5 right-3.5 z-20 flex items-center gap-1.5">
                <button type="button"
                        @click="resetChat()"
                        title="Ulangi Percakapan"
                        aria-label="Ulangi Percakapan"
                        class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-md border border-white/25 flex items-center justify-center text-white transition cursor-pointer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                        <path d="M3 3v5h5"/>
                    </svg>
                </button>
                <button type="button"
                        @click="isOpen = false"
                        title="Tutup Asisten Falah"
                        aria-label="Tutup Asisten Falah"
                        class="w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 backdrop-blur-md border border-white/25 flex items-center justify-center text-white transition cursor-pointer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Layout 2 Kolom: Kolom Kiri (Maskot Vector Falah) & Kolom Kanan (Identitas Falah) --}}
            <div class="relative z-10 grid grid-cols-12 items-center gap-3">
                {{-- KOLOM KIRI (5 KOLOM): MASKOT FALAH FULL KODE SVG + CSS KEYFRAMES ANIMATION --}}
                <div class="col-span-5 flex items-center justify-center">
                    <svg class="w-36 h-32 sm:w-40 sm:h-36 select-none overflow-visible" viewBox="0 0 210 185" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Maskot Asisten AI Falah">
                        {{-- 1. Bayangan Elips Halus di Bawah Badan yang Ikut Membesar-Mengecil --}}
                        <ellipse class="falah-shadow" cx="96" cy="174" rx="42" ry="7" fill="#042F2E" opacity="0.32"/>

                        {{-- Grup Utama Karakter Falah yang Mengapung Halus (@keyframes float) --}}
                        <g class="falah-float">
                            {{-- 2. Tangan Kiri di Samping Badan (Melengkung Turun dengan Telapak Bulat) --}}
                            <path d="M52 118C37 120 31 132 31 144" stroke="#CCFBF1" stroke-width="9" stroke-linecap="round"/>
                            <circle cx="31" cy="146" r="7" fill="#CCFBF1"/>

                            {{-- 3. Tangan Kanan Terangkat Melambai (@keyframes wave) --}}
                            <g class="falah-wave">
                                <path d="M138 116C154 116 164 103 164 85" stroke="#CCFBF1" stroke-width="9" stroke-linecap="round"/>
                                <circle cx="164" cy="83" r="7.5" fill="#CCFBF1"/>
                            </g>

                            {{-- 4. Badan Kubus / Kotak Stok Putih Bersih (#FFFFFF) Bersudut Membulat Lembut --}}
                            <rect x="48" y="68" width="96" height="90" rx="20" fill="#FFFFFF"/>

                            {{-- 5. Tutup Atas Kotak Berwarna Mint Tipis (#CCFBF1) --}}
                            <path d="M48 86C48 76.0589 56.0589 68 66 68H126C135.941 68 144 76.0589 144 86V91C144 94.3137 141.314 97 138 97H54C50.6863 97 48 94.3137 48 91V86Z" fill="#CCFBF1"/>

                            {{-- 6. Peci / Kopiah Warna Gelap (#134E4A) di Atas Kepala Kotak --}}
                            <path d="M72 44C72 39.5817 75.5817 36 80 36H112C116.418 36 120 39.5817 120 44L125 63H67L72 44Z" fill="#134E4A"/>

                            {{-- 7. Pita Tepi Peci & Pita Vertikal Kuning Emas (#FBBF24) di Tengah Atas Kotak --}}
                            <rect x="64" y="61" width="64" height="7.5" rx="3.5" fill="#FBBF24"/>
                            <rect x="88" y="65" width="16" height="32" rx="2.5" fill="#FBBF24"/>

                            {{-- 8. Wajah Imut di Bagian Depan Kotak --}}
                            {{-- Dua Mata Bulat Gelap dengan Pantulan Cahaya Putih (@keyframes blink) --}}
                            <g class="falah-blink">
                                <circle cx="75" cy="117" r="8.5" fill="#0F172A"/>
                                <circle cx="72.2" cy="114.2" r="2.8" fill="#FFFFFF"/>

                                <circle cx="117" cy="117" r="8.5" fill="#0F172A"/>
                                <circle cx="114.2" cy="114.2" r="2.8" fill="#FFFFFF"/>
                            </g>

                            {{-- Dua Pipi Bulat Pink Lembut (#F472B6) --}}
                            <circle cx="62" cy="130" r="6.5" fill="#F472B6" opacity="0.65"/>
                            <circle cx="130" cy="130" r="6.5" fill="#F472B6" opacity="0.65"/>

                            {{-- Mulut Tersenyum Ramah dengan Lidah Pink --}}
                            <path d="M87 128H105C105 134.5 101 139 96 139C91 139 87 134.5 87 128Z" fill="#0F172A"/>
                            <path d="M90.5 134.5C92 132.8 94 132 96 132C98 132 100 132.8 101.5 134.5C100.1 137.2 98.2 138.6 96 138.6C93.8 138.6 91.9 137.2 90.5 134.5Z" fill="#F472B6"/>

                            {{-- 9. Balon Bicara Vector Putih Bertuliskan "Halo!" Berwarna Hijau Teal Tebal (#0F766E) --}}
                            <g>
                                <rect x="122" y="10" width="76" height="38" rx="13" fill="#FFFFFF" stroke="#99F6E4" stroke-width="3"/>
                                <path d="M138 46L131 58L150 46H138Z" fill="#FFFFFF"/>
                                <path d="M137 47L131 58L148 47" stroke="#99F6E4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <text x="160" y="34" text-anchor="middle" fill="#0F766E" font-family="Inter, sans-serif" font-size="16" font-weight="800">Halo!</text>
                            </g>
                        </g>
                    </svg>
                </div>

                {{-- KOLOM KANAN (7 KOLOM): IDENTITAS TANYA STOKKU AI --}}
                <div class="col-span-7 flex flex-col items-start justify-center pr-4">
                    {{-- Badge Kapsul Putih: TANYA STOKKU AI --}}
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white text-[#0F766E] text-[10px] font-black tracking-wider uppercase shadow-sm mb-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Tanya Stokku AI
                    </span>

                    {{-- Judul Besar: Asisten Pintar --}}
                    <h3 class="text-2xl font-extrabold text-white tracking-tight leading-none">
                        Tanya Stokku
                    </h3>

                    {{-- Tagline --}}
                    <p class="text-xs text-teal-100 font-medium leading-relaxed mt-1.5">
                        Asisten Cerdas &bull; Google Gemini 2.5 Flash
                    </p>
                </div>
            </div>
        </div>

        {{-- ===============================================================
             AREA PESAN PERCAKAPAN (CHAT BUBBLES + MINI FALAH SVG AVATAR)
             =============================================================== --}}
        <div id="chatMessagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50 dark:bg-slate-950 min-h-[220px] max-h-[310px]">
            <template x-for="(msg, index) in messages" :key="index">
                <div :class="msg.sender === 'user' ? 'flex justify-end' : 'flex justify-start items-start gap-3'">
                    {{-- Avatar Mini Vector Falah untuk Pesan Bot --}}
                    <template x-if="msg.sender === 'bot'">
                        <div class="w-8 h-8 rounded-2xl bg-gradient-to-tr from-[#0F766E] to-[#0D9488] flex items-center justify-center shrink-0 mt-0.5 shadow-sm border border-white/25">
                            <svg class="w-6 h-6" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M22 14C22 12.3431 23.3431 11 25 11H39C40.6569 11 42 12.3431 42 14L44 21H20L22 14Z" fill="#134E4A"/>
                                <rect x="19" y="20" width="26" height="3.5" rx="1.75" fill="#FBBF24"/>
                                <rect x="14" y="23" width="36" height="31" rx="8" fill="#FFFFFF"/>
                                <path d="M14 30C14 26.134 17.134 23 21 23H43C46.866 23 50 26.134 50 30V32C50 33.1046 49.1046 34 48 34H16C14.8954 34 14 33.1046 14 32V30Z" fill="#CCFBF1"/>
                                <rect x="29" y="22" width="6" height="12" rx="1.5" fill="#FBBF24"/>
                                <circle cx="24.5" cy="41.5" r="3.2" fill="#0F172A"/>
                                <circle cx="39.5" cy="41.5" r="3.2" fill="#0F172A"/>
                                <path d="M28.5 45.5C28.5 45.5 30 48 32 48C34 48 35.5 45.5 35.5 45.5H28.5Z" fill="#0F172A"/>
                            </svg>
                        </div>
                    </template>

                    <div :class="msg.sender === 'user'
                            ? 'bg-gradient-to-r from-[#0D9488] to-[#0F766E] text-white rounded-2xl rounded-tr-xs shadow-sm'
                            : 'bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-2xl rounded-tl-xs border border-slate-200 dark:border-slate-700 shadow-xs'"
                         class="max-w-[84%] px-3.5 py-2.5 text-xs leading-relaxed">
                        <div x-html="formatText(msg.text)" class="space-y-1"></div>
                        <div :class="msg.sender === 'user' ? 'text-white/80 text-right' : 'text-slate-400 text-right'"
                             class="text-[10px] mt-1 font-semibold tabular-nums"
                             x-text="msg.time">
                        </div>
                    </div>
                </div>
            </template>

            {{-- Indikator Loading Saat Falah Menyiapkan Jawaban --}}
            <div x-show="isLoading" class="flex items-center gap-3 text-slate-400">
                <div class="w-8 h-8 rounded-2xl bg-gradient-to-tr from-[#0F766E] to-[#0D9488] flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5 animate-spin text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                    </svg>
                </div>
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-2.5 shadow-xs flex items-center gap-1.5">
                    <div class="w-1.5 h-1.5 rounded-full bg-teal-600 animate-bounce"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-bounce [animation-delay:0.2s]"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-bounce [animation-delay:0.4s]"></div>
                </div>
            </div>
        </div>

        {{-- ===============================================================
             3 TOMBOL QUICK PROMPT SEKALI KLIK TANYA STOKKU AI (GEMINI 2.5 FLASH)
             =============================================================== --}}
        <div class="px-3.5 py-2.5 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700">
            <div class="flex flex-col gap-1.5">
                {{-- Quick Prompt 1: Sayur apa yang harus diobral sore ini biar nggak busuk? --}}
                <button type="button"
                        @click="sendQuick('obral_sore', 'Sayur apa yang harus diobral sore ini biar nggak busuk?')"
                        :disabled="isLoading"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-xl bg-amber-50/90 hover:bg-amber-100 dark:bg-amber-950/40 dark:hover:bg-amber-950/70 text-amber-900 dark:text-amber-200 border border-amber-200 dark:border-amber-500/30 text-[11px] font-bold text-left transition active:scale-98 disabled:opacity-50 cursor-pointer">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    <span class="truncate">Sayur apa yang harus diobral sore ini biar nggak busuk?</span>
                </button>

                {{-- Quick Prompt 2: Rekomendasi belanja besok pagi dengan modal Rp 750.000 --}}
                <button type="button"
                        @click="sendQuick('belanja_modal', 'Rekomendasi belanja besok pagi dengan modal Rp 750.000')"
                        :disabled="isLoading"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-xl bg-teal-50/90 hover:bg-teal-100 dark:bg-slate-950 dark:hover:bg-teal-950/70 text-slate-900 dark:text-teal-200 border border-slate-200 dark:border-slate-700 text-[11px] font-bold text-left transition active:scale-98 disabled:opacity-50 cursor-pointer">
                    <svg class="w-4 h-4 text-[#0D9488] dark:text-teal-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="8" cy="21" r="1"/>
                        <circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                    <span class="truncate">Rekomendasi belanja besok pagi dengan modal Rp 750.000</span>
                </button>

                {{-- Quick Prompt 3: Bagaimana performa omzet dan barang terlaris hari ini? --}}
                <button type="button"
                        @click="sendQuick('performa_omzet', 'Bagaimana performa omzet dan barang terlaris hari ini?')"
                        :disabled="isLoading"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-xl bg-emerald-50/90 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-950/70 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-500/30 text-[11px] font-bold text-left transition active:scale-98 disabled:opacity-50 cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 3v18h18"/>
                        <path d="m19 9-5 5-4-4-3 3"/>
                    </svg>
                    <span class="truncate">Bagaimana performa omzet dan barang terlaris hari ini?</span>
                </button>
            </div>
        </div>

        {{-- ===============================================================
             KOLOM INPUT PESAN & TOMBOL SUARA / KIRIM BERIKON <SVG>
             =============================================================== --}}
        <div class="p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700">
            <form @submit.prevent="send()" class="flex items-center gap-2">
                <input type="text"
                       x-model="userInput"
                       :disabled="isLoading"
                       placeholder="Tanya Stokku AI atau ketik: stok cabai habis..."
                       class="flex-1 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-2xl px-3.5 py-2.5 text-xs font-semibold text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0D9488] transition disabled:opacity-50">

                {{-- Tombol Input Suara (Microphone SVG) --}}
                <button type="button"
                        @click="toggleVoice()"
                        :class="isListening ? 'bg-rose-600 text-white animate-pulse' : 'bg-teal-50 dark:bg-slate-950 hover:bg-teal-100 text-[#0D9488] dark:text-teal-300 border border-slate-200 dark:border-slate-700'"
                        :title="isListening ? 'Sedang mendengarkan suara... Ketuk untuk berhenti' : 'Bicara dengan Tanya Stokku AI'"
                        aria-label="Input Suara"
                        class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 transition cursor-pointer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/>
                        <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                        <line x1="12" x2="12" y1="19" y2="22"/>
                    </svg>
                </button>

                {{-- Tombol Kirim Pesan (Send Plane SVG) --}}
                <button type="submit"
                        :disabled="isLoading || !userInput.trim()"
                        title="Kirim Pesan"
                        aria-label="Kirim Pesan"
                        class="w-10 h-10 rounded-2xl bg-gradient-to-r from-[#0D9488] to-[#0F766E] hover:opacity-95 disabled:opacity-40 text-white flex items-center justify-center shrink-0 transition shadow-md shadow-teal-600/30 cursor-pointer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m22 2-7 20-4-9-9-4Z"/>
                        <path d="M22 2 11 13"/>
                    </svg>
                </button>
            </form>
        </div>

    </div>
</div>

<script>
    window.falahAiChat = function() {
        return {
            isOpen: false,
            userInput: '',
            isLoading: false,
            isListening: false,
            messages: [
                {
                    sender: 'bot',
                    text: 'Assalamu\'alaikum! Saya **Tanya Stokku AI**, asisten cerdas Warung Adijaya. Siap bantu analisa stok kritis, rekomendasi kulakan pasar, obral sayur anti-busuk, dan pantau keuangan warung hari ini.',
                    time: '{{ now()->format('H:i') }}'
                }
            ],
            init() {
                this.$watch('messages', () => {
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                });
                this.$watch('isOpen', (val) => {
                    if (val) {
                        this.$nextTick(() => {
                            this.scrollToBottom();
                        });
                    }
                });
            },
            scrollToBottom() {
                const container = document.getElementById('chatMessagesContainer');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            },
            formatMessage(text) {
                if (!text) return '';
                let formatted = text
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
                formatted = formatted.replace(/^#### (.*$)/gim, '<div class="text-[11px] font-black text-teal-700 dark:text-teal-300 mt-2 mb-0.5">$1</div>');
                formatted = formatted.replace(/^### (.*$)/gim, '<div class="text-xs font-black text-teal-800 dark:text-teal-300 mt-2 mb-1">$1</div>');
                formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900 dark:text-slate-100">$1</strong>');
                formatted = formatted.replace(/\*(.*?)\*/g, '<strong class="font-semibold text-slate-800 dark:text-slate-200">$1</strong>');
                formatted = formatted.replace(/^\s*-\s+(.*$)/gim, '<div class="flex items-start gap-1.5 my-0.5"><span class="text-teal-500 font-bold">&bull;</span><span class="text-xs leading-relaxed">$1</span></div>');
                formatted = formatted.replace(/\n/g, '<br>');
                return formatted;
            },
            formatText(text) {
                return this.formatMessage(text);
            },
            getCurrentTime() {
                const d = new Date();
                const h = String(d.getHours()).padStart(2, '0');
                const m = String(d.getMinutes()).padStart(2, '0');
                return `${h}:${m}`;
            },
            sendQuick(actionType, label) {
                if (this.isLoading) return;
                this.messages.push({
                    sender: 'user',
                    text: label,
                    time: this.getCurrentTime()
                });
                this.executeRequest({ action: actionType, message: label });
            },
            send() {
                const txt = this.userInput.trim();
                if (!txt || this.isLoading) return;

                this.messages.push({
                    sender: 'user',
                    text: txt,
                    time: this.getCurrentTime()
                });
                this.userInput = '';

                this.executeRequest({ message: txt });
            },
            async executeRequest(payload) {
                this.isLoading = true;
                try {
                    const res = await fetch('{{ route('ai.chat') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    this.messages.push({
                        sender: 'bot',
                        text: data.reply || 'Maaf, sistem belum memahami instruksi tersebut. Silakan coba kembali.',
                        time: this.getCurrentTime()
                    });
                } catch (err) {
                    this.messages.push({
                        sender: 'bot',
                        text: 'Terjadi kendala jaringan. Pastikan koneksi aktif dan coba kembali.',
                        time: this.getCurrentTime()
                    });
                } finally {
                    this.isLoading = false;
                }
            },
            toggleVoice() {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                if (!SpeechRecognition) {
                    alert('Browser Anda belum mendukung input suara. Silakan ketik langsung pada kotak pesan.');
                    return;
                }

                if (this.isListening) {
                    this.isListening = false;
                    return;
                }

                const recognition = new SpeechRecognition();
                recognition.lang = 'id-ID';
                recognition.continuous = false;
                recognition.interimResults = false;

                this.isListening = true;

                recognition.onresult = (event) => {
                    const transcript = event.results[0][0].transcript;
                    if (transcript) {
                        this.userInput = transcript;
                        this.send();
                    }
                };

                recognition.onerror = () => {
                    this.isListening = false;
                };

                recognition.onend = () => {
                    this.isListening = false;
                };

                recognition.start();
            },
            resetChat() {
                this.messages = [
                    {
                        sender: 'bot',
                        text: 'Assalamu\'alaikum! Saya **Tanya Stokku AI**, asisten cerdas Warung Adijaya. Siap bantu analisa stok kritis, rekomendasi kulakan pasar, obral sayur anti-busuk, dan pantau keuangan warung hari ini.',
                        time: this.getCurrentTime()
                    }
                ];
            }
        };
    };

    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof window.falahAiChat === 'function') {
            Alpine.data('falahAiChat', window.falahAiChat);
        }
    });
</script>
