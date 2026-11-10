<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('BI Hub - Pusat Analisis') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Filter Tanggal Custom -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('bi-hub.index') }}" class="flex gap-4 items-end">
                    <div class="w-full">
                        <label class="block text-sm font-medium text-gray-700">Periode Analisis</label>
                        <select name="period" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" onchange="this.form.submit()">
                            <option value="last_30_days" {{ $period == 'last_30_days' ? 'selected' : '' }}>30 Hari Terakhir (Swipeable)</option>
                            <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
                            <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Bulan Lalu</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- A. Line Chart: Tren Penjualan -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h3 class="text-lg font-bold mb-2">Tren Penjualan (Revenue)</h3>
                <p class="text-sm text-gray-500 mb-4">Grafik ini bisa digeser (swipe/pan) ke kiri/kanan jika data terlalu panjang.</p>
                <div id="revenueChart" class="w-full"></div>
            </div>

            <!-- B. Ranking Menu -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h3 class="text-lg font-bold mb-4">Top 10 Menu Terlaris</h3>
                <ul class="divide-y divide-gray-200">
                    @forelse($topMenus as $index => $menu)
                        <li class="py-3 flex justify-between items-center">
                            <div class="flex items-center">
                                <span class="text-xl font-bold {{ $index == 0 ? 'text-yellow-500' : ($index == 1 ? 'text-gray-400' : ($index == 2 ? 'text-amber-600' : 'text-gray-500')) }} w-8">
                                    #{{ $index + 1 }}
                                </span>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $menu->menuItem->name ?? 'Komoditas Dihapus' }}</p>
                                    <p class="text-xs text-gray-500">Terjual: {{ $menu->total_qty }} {{ $menu->menuItem->sale_unit ?? 'satuan' }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Margin: Rp {{ number_format($menu->total_cm, 0, ',', '.') }}
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="py-3 text-gray-500 text-center">Belum ada data penjualan pada periode ini.</li>
                    @endforelse
                </ul>
            </div>

            <!-- C. Stacked Chart: Kemas Ulang vs Terjual -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h3 class="text-lg font-bold mb-2">Efisiensi Stok Etalase (Waste Analysis)</h3>
                <p class="text-sm text-gray-500 mb-4">Bagian merah menandakan barang sisa di etalase yang belum terjual.</p>
                <div id="wasteChart" class="w-full"></div>
            </div>

        </div>
    </div>

    <!-- Tambahkan library ApexCharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            // --- A. Line Chart Tren Penjualan ---
            var dates = {!! json_encode($chartDates) !!};
            var revenues = {!! json_encode($chartRevenues) !!};

            var optionsRevenue = {
                series: [{
                    name: 'Revenue (Rp)',
                    data: revenues
                }],
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: {
                        show: true,
                        tools: {
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: true, // Enable panning (swipe)
                            reset: false
                        }
                    },
                    animations: { enabled: true }
                },
                colors: ['#4f46e5'], // Indigo 600
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                xaxis: {
                    categories: dates,
                    // Batasi tampilan awal hanya 7 item terakhir agar bisa di swipe
                    min: Math.max(0, dates.length - 7),
                    max: dates.length - 1,
                    labels: { style: { fontSize: '10px' } }
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
                revenueChart.render();
            } else {
                document.querySelector("#revenueChart").innerHTML = '<p class="text-center text-gray-500">Belum ada data untuk dirender.</p>';
            }


            // --- C. Stacked Chart Produksi vs Waste ---
            var wasteLabels = {!! json_encode($stackedLabels) !!};
            var salesData = {!! json_encode($stackedSales) !!};
            var wasteData = {!! json_encode($stackedWaste) !!};

            var optionsWaste = {
                series: [{
                    name: 'Terjual',
                    data: salesData
                }, {
                    name: 'Sisa/Waste',
                    data: wasteData
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    stacked: true, // Membuat bar menjadi bertumpuk
                    toolbar: { show: false }
                },
                colors: ['#10b981', '#ef4444'], // Hijau (Terjual) dan Merah (Waste)
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 2
                    },
                },
                xaxis: {
                    categories: wasteLabels,
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left'
                },
                fill: {
                    opacity: 1
                }
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
