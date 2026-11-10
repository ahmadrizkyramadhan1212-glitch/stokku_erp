<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('reports.index') }}" class="p-2 bg-gray-100 rounded-full text-gray-600 hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tren Penjualan') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('reports.sales_trend') }}" class="flex gap-4 items-end">
                    <div class="w-full">
                        <label class="block text-sm font-medium text-gray-700">Periode Analisis</label>
                        <select name="period" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" onchange="this.form.submit()">
                            <option value="last_30_days" {{ $period == 'last_30_days' ? 'selected' : '' }}>30 Hari Terakhir</option>
                            <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                            <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Bulan Lalu</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- CHART CONTAINER -->
            <div class="bg-white shadow-sm sm:rounded-lg p-4 transition-all duration-300 flex flex-col" id="chartContainer">
                <div class="flex justify-between items-start mb-2 shrink-0">
                    <div>
                        <h3 class="text-lg font-bold">Pergerakan Omset (Revenue)</h3>
                        <p class="text-[10px] text-gray-500">Geser (Swipe) layar ke kiri/kanan untuk melihat riwayat.</p>
                    </div>
                    <button id="fullscreenBtn" class="relative z-[10000] text-gray-400 hover:text-indigo-600 focus:outline-none p-2 bg-gray-50 rounded-lg active:scale-95 transition-all" title="Perbesar Tampilan">
                        <!-- Icon Expand -->
                        <svg id="iconExpand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                        </svg>
                        <!-- Icon Shrink (Hidden initially) -->
                        <svg id="iconShrink" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5 hidden">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
                        </svg>
                    </button>
                </div>
                
                <!-- Native Horizontal Scroll Container untuk Swipe Super Mulus -->
                <div class="w-full overflow-x-auto touch-pan-x flex-1 pb-2" id="scrollContainer" style="-webkit-overflow-scrolling: touch;">
                    <div id="revenueChart" class="bg-white"></div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            var dates = {!! json_encode($chartDates) !!};
            var revenues = {!! json_encode($chartRevenues) !!};

            // Trik Native Scroll CSS: Set lebar chart menjadi (Jumlah Hari * 55px)
            var chartDynamicWidth = dates.length > 7 ? dates.length * 55 : '100%';

            var optionsRevenue = {
                series: [{ name: 'Revenue (Rp)', data: revenues }],
                chart: {
                    type: 'area',
                    height: 400,
                    width: chartDynamicWidth, // Paksa lebar melebihi layar agar men-trigger native scrollbar CSS
                    toolbar: { show: false }, // Matikan toolbar JS agar tidak bentrok dengan swipe sentuhan layar
                    animations: { enabled: false }, // Matikan animasi render agar sangat responsif
                    parentHeightOffset: 0
                },
                colors: ['#4f46e5'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                xaxis: {
                    categories: dates,
                    labels: { style: { fontSize: '10px' } }
                    // 'min' dan 'max' dihapus! Sekarang kita murni mengandalkan CSS Scroll Browser
                },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return (value / 1000).toFixed(0) + 'K';
                            return value;
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return "Rp " + new Intl.NumberFormat('id-ID').format(val)
                        }
                    }
                }
            };

            if (dates.length > 0) {
                var revenueChart = new ApexCharts(document.querySelector("#revenueChart"), optionsRevenue);
                revenueChart.render().then(() => {
                    // Otomatis scroll kontainer ke sisi paling kanan (ke hari terbaru)
                    const scrollContainer = document.getElementById('scrollContainer');
                    scrollContainer.scrollLeft = scrollContainer.scrollWidth;
                });
            } else {
                document.querySelector("#revenueChart").innerHTML = '<p class="text-center text-gray-500">Belum ada data untuk dirender.</p>';
            }

            // --- UI Overlay Fullscreen Logic ---
            const container = document.getElementById('chartContainer');
            const btn = document.getElementById('fullscreenBtn');
            const iconExpand = document.getElementById('iconExpand');
            const iconShrink = document.getElementById('iconShrink');
            
            let isFullscreenUI = false;

            btn.addEventListener('click', () => {
                isFullscreenUI = !isFullscreenUI;
                
                if (isFullscreenUI) {
                    // Masuk Mode Penuh
                    container.classList.remove('sm:rounded-lg');
                    container.classList.add('fixed', 'inset-0', 'z-[9999]', 'w-full', 'h-full', 'rounded-none');
                    
                    // Ganti Ikon Button
                    iconExpand.classList.add('hidden');
                    iconShrink.classList.remove('hidden');
                    btn.classList.add('bg-indigo-100', 'text-indigo-700');

                    // Sesuaikan Tinggi Chart
                    revenueChart.updateOptions({
                        chart: { height: window.innerHeight - 100 }
                    });

                } else {
                    // Keluar Mode Penuh
                    container.classList.remove('fixed', 'inset-0', 'z-[9999]', 'w-full', 'h-full', 'rounded-none');
                    container.classList.add('sm:rounded-lg');
                    
                    // Kembalikan Ikon Button
                    iconExpand.classList.remove('hidden');
                    iconShrink.classList.add('hidden');
                    btn.classList.remove('bg-indigo-100', 'text-indigo-700');

                    // Kembalikan Tinggi Chart
                    revenueChart.updateOptions({
                        chart: { height: 400 }
                    });
                }
            });

        });
    </script>
</x-app-layout>
