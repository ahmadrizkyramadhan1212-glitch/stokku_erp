<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-3">
            <a href="{{ route('reports.index') }}" class="p-2 bg-gray-100 rounded-full text-gray-600 hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Ranking Menu') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('reports.menu_ranking') }}" class="flex gap-4 items-end">
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

        </div>
    </div>
</x-app-layout>
