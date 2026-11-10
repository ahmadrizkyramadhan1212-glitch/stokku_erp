<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('reports.index') }}" class="p-2 bg-gray-100 rounded-full text-gray-600 hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Analisis Kebocoran / Waste') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('reports.waste_analysis') }}" class="flex gap-4 items-end">
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h3 class="text-lg font-bold mb-2">Efisiensi Stok Etalase (Susut/Sisa)</h3>
                <p class="text-sm text-gray-500 mb-4">Bagian merah menandakan barang sisa di etalase yang belum terjual.</p>
                <div id="wasteChart" class="w-full"></div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var wasteLabels = {!! json_encode($stackedLabels) !!};
            var salesData = {!! json_encode($stackedSales) !!};
            var wasteData = {!! json_encode($stackedWaste) !!};

            var optionsWaste = {
                series: [{ name: 'Terjual', data: salesData }, { name: 'Sisa/Waste', data: wasteData }],
                chart: { type: 'bar', height: 400, stacked: true, toolbar: { show: false } },
                colors: ['#10b981', '#ef4444'], // Hijau (Terjual) dan Merah (Waste)
                plotOptions: { bar: { horizontal: true, borderRadius: 2 } },
                xaxis: { categories: wasteLabels },
                legend: { position: 'top', horizontalAlign: 'left' },
                fill: { opacity: 1 }
            };

            if (wasteLabels.length > 0) {
                var wasteChart = new ApexCharts(document.querySelector("#wasteChart"), optionsWaste);
                wasteChart.render();
            } else {
                document.querySelector("#wasteChart").innerHTML = '<p class="text-center text-gray-500">Belum ada data produksi/penjualan untuk dirender.</p>';
            }
        });
    </script>
</x-app-layout>
