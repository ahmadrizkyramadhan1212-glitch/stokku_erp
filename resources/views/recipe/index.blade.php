<x-app-layout>
    <div x-data="recipeManager({{ $ingredients->toJson() }})" class="pb-24">
        <div class="px-3 pt-4 pb-4 flex justify-between items-end sticky top-0 z-30 bg-white/90 backdrop-blur-md mb-4 shadow-sm border-b border-gray-100">
            <div>
                <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">Detail Kemasan &amp; Konversi Satuan</h2>
                <p class="text-xs text-gray-500 mt-1">Rasio Konversi Gudang ke Etalase &amp; Harga Jual</p>
            </div>
            <button @click="showModal = true" class="bg-indigo-600 text-white p-2.5 rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 active:scale-95 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </button>
        </div>

        @if(session('success'))
            <div class="mx-3 mb-4 p-3 bg-emerald-100 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-semibold text-center animate-pulse">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-4 px-3">
            @foreach($recipes as $recipe)
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden relative">
                    <div class="bg-indigo-50 px-4 py-3 border-b border-indigo-100 flex justify-between items-center">
                        <h3 class="font-bold text-indigo-900 text-sm">{{ $recipe->name }}</h3>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('recipe.edit', $recipe) }}" class="text-[10px] font-bold text-white bg-amber-500 hover:bg-amber-600 px-2.5 py-1.5 rounded-md shadow-sm transition-colors">Edit</a>
                            <form action="{{ route('recipe.destroy', $recipe) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus detail kemasan ini? Data komoditas pada etalase juga akan terhapus.')" class="m-0 p-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[10px] font-bold text-white bg-red-500 hover:bg-red-600 px-2.5 py-1.5 rounded-md shadow-sm transition-colors">Hapus</button>
                            </form>
                        </div>
                    </div>
                    <div class="p-4">
                        <ul class="space-y-2.5 mb-4">
                            @foreach($recipe->items as $item)
                                <li class="flex justify-between items-center text-xs text-gray-600 border-b border-gray-50 pb-2">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-1.5 h-1.5 bg-indigo-300 rounded-full"></div>
                                        <span>{{ $item->ingredient->name }}</span>
                                    </div>
                                    <span class="font-bold text-gray-900">{{ $item->quantity }} <span class="text-[10px] font-medium text-gray-500">{{ $item->ingredient->unit }}</span></span>
                                </li>
                            @endforeach
                            <!-- Overhead & Packaging -->
                            <li class="flex justify-between items-center text-xs text-gray-500 pt-1">
                                <div class="flex items-center space-x-2">
                                    <div class="w-1.5 h-1.5 bg-amber-300 rounded-full"></div>
                                    <span>Biaya Kemasan</span>
                                </div>
                                <span class="font-semibold text-gray-700">Rp{{ number_format($recipe->packaging_cost, 0, ',', '.') }}</span>
                            </li>
                            <li class="flex justify-between items-center text-xs text-gray-500">
                                <div class="flex items-center space-x-2">
                                    <div class="w-1.5 h-1.5 bg-emerald-300 rounded-full"></div>
                                    <span>Operasional (Gas/Tenaga)</span>
                                </div>
                                <span class="font-semibold text-gray-700">Rp{{ number_format($recipe->overhead_cost, 0, ',', '.') }}</span>
                            </li>
                        </ul>
                        
                        @php
                            $menu = $recipe->menuItem;
                        @endphp
                        @if($menu)
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <div class="bg-gray-50 rounded-xl p-2 text-center border border-gray-100">
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Harga Ecer</p>
                                    <p class="text-xs font-bold text-gray-900">Rp{{ number_format($menu->price_eceran, 0, ',', '.') }}</p>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-2 text-center border border-gray-100">
                                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Harga Reseller</p>
                                    <p class="text-xs font-bold text-gray-900">Rp{{ number_format($menu->price_reseller, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-between items-center bg-gray-900 p-4 rounded-xl shadow-inner">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Modal (HPP)</span>
                            <span class="text-base font-black text-emerald-400">Rp{{ number_format($recipe->calculateHpp(), 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- MODAL TAMBAH KEMASAN -->
        <div x-show="showModal" class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 pt-4 px-2" x-transition.opacity style="display: none;">
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl relative h-[90vh] flex flex-col" @click.away="showModal = false" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-10 opacity-0" x-transition:enter-end="translate-y-0 opacity-100">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-white rounded-t-3xl shrink-0">
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-900 leading-tight">Buat Detail Kemasan Baru</h3>
                        <p class="text-[10px] text-gray-500 font-semibold">Tersinkron dengan Stok Gudang &amp; Etalase</p>
                    </div>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 bg-gray-100 rounded-full p-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="p-4 overflow-y-auto flex-1">
                    <form id="recipe-form" action="{{ route('recipe.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="ingredients" :value="JSON.stringify(selectedIngredients)">

                        <!-- 1. Identitas Komoditas -->
                        <div class="mb-5">
                            <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-3">1. Identitas Komoditas</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nama Komoditas</label>
                                    <input type="text" name="name" required class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-bold p-2.5" placeholder="Cth: Cabai Keriting Eceran">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Kategori</label>
                                    <select name="category" required class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 font-semibold text-gray-700 p-2.5">
                                        <option value="Sayur & Buah Segar">Sayur &amp; Buah Segar</option>
                                        <option value="Bumbu Dapur & Rempah">Bumbu Dapur &amp; Rempah</option>
                                        <option value="Minyak, Saus & Kecap">Minyak, Saus &amp; Kecap</option>
                                        <option value="Sembako & Bahan Pokok">Sembako &amp; Bahan Pokok</option>
                                        <option value="Lauk, Minuman & Rumah Tangga">Lauk, Minuman &amp; Rumah Tangga</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Konversi Kemasan (Bahan Gudang) -->
                        <div class="mb-5">
                            <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-3 flex justify-between items-center">
                                2. Komposisi Barang Gudang
                                <button type="button" @click="addIngredientRow" class="text-[10px] bg-indigo-100 text-indigo-700 px-2 py-1 rounded-md hover:bg-indigo-200">+ Tambah Barang</button>
                            </h4>
                            
                            <div class="space-y-3">
                                <template x-for="(ing, index) in selectedIngredients" :key="index">
                                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 relative">
                                        <button type="button" @click="removeIngredientRow(index)" class="absolute -top-2 -right-2 bg-red-100 text-red-600 rounded-full w-5 h-5 flex items-center justify-center font-bold text-xs hover:bg-red-200">&times;</button>
                                        
                                        <div class="mb-2">
                                            <label class="block text-[9px] font-bold text-gray-400 uppercase mb-0.5">Pilih Barang (Dari Gudang)</label>
                                            <select x-model="ing.ingredient_id" @change="updateIngredientDetails(index)" class="w-full text-xs border-gray-200 bg-white rounded-lg p-2 font-semibold">
                                                <option value="">-- Buat Barang Baru --</option>
                                                @foreach($ingredients as $dbIng)
                                                    <option value="{{ $dbIng->id }}">{{ $dbIng->name }} ({{ $dbIng->unit }})</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Jika Bahan Baru -->
                                        <div x-show="!ing.ingredient_id" class="grid grid-cols-2 gap-2 mb-2 p-2 bg-white rounded-lg border border-amber-100">
                                            <div class="col-span-2">
                                                <label class="block text-[9px] font-bold text-amber-500 uppercase mb-0.5">Nama Barang Baru</label>
                                                <input type="text" x-model="ing.name" class="w-full text-xs border-gray-200 rounded p-1.5" placeholder="Cth: Bawang Merah Brebes">
                                            </div>
                                            <div>
                                                <label class="block text-[9px] font-bold text-amber-500 uppercase mb-0.5">Satuan</label>
                                                <input type="text" x-model="ing.unit" class="w-full text-xs border-gray-200 rounded p-1.5" placeholder="Cth: kg">
                                            </div>
                                            <div>
                                                <label class="block text-[9px] font-bold text-amber-500 uppercase mb-0.5">Harga / Satuan</label>
                                                <input type="number" x-model="ing.cost_per_unit" class="w-full text-xs border-gray-200 rounded p-1.5" placeholder="Cth: 35000">
                                            </div>
                                        </div>

                                        <!-- Kuantitas Konversi -->
                                        <div>
                                            <label class="block text-[9px] font-bold text-gray-500 uppercase mb-0.5">Rasio Konversi Per 1 Satuan Jual <span x-text="ing.unit ? '('+ing.unit+')' : ''"></span></label>
                                            <input type="number" step="0.001" x-model="ing.quantity" class="w-full text-xs border-gray-300 bg-white rounded-lg p-2 font-bold text-indigo-600" placeholder="Contoh: 0.1">
                                        </div>
                                    </div>
                                </template>
                                <p x-show="selectedIngredients.length === 0" class="text-xs text-gray-400 italic text-center py-2">Belum ada barang. Klik "+ Tambah Barang".</p>
                            </div>
                        </div>

                        <!-- 3. Biaya Operasional -->
                        <div class="mb-5">
                            <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-3">3. Biaya Kemasan Per Satuan Jual (Rp)</h4>
                            <div class="flex space-x-3">
                                <div class="flex-1">
                                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Kemasan (Plastik/Karet)</label>
                                                    <input type="number" name="packaging_cost" required x-model.number="packaging_cost" class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-2.5 font-bold">
                                                </div>
                                                <div class="flex-1">
                                                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Overhead Operasional</label>
                                                    <input type="number" name="overhead_cost" required x-model.number="overhead_cost" class="w-full text-sm border-gray-200 bg-gray-50 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 p-2.5 font-bold">
                                                </div>
                            </div>
                        </div>

                        <!-- 4. Target Harga Jual -->
                        <div class="mb-8">
                            <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-3">4. Target Harga Jual (Rp)</h4>
                            <div class="space-y-3">
                                <div class="flex items-center space-x-3">
                                    <label class="w-20 text-[10px] font-bold text-gray-500 uppercase">Eceran</label>
                                    <input type="number" name="price_eceran" required class="flex-1 text-sm border-gray-200 bg-emerald-50 rounded-xl focus:ring-emerald-500 focus:border-emerald-500 font-bold p-2 text-emerald-700" placeholder="0">
                                </div>
                                <div class="flex items-center space-x-3">
                                    <label class="w-20 text-[10px] font-bold text-gray-500 uppercase">Reseller</label>
                                    <input type="number" name="price_reseller" required class="flex-1 text-sm border-gray-200 bg-blue-50 rounded-xl focus:ring-blue-500 focus:border-blue-500 font-bold p-2 text-blue-700" placeholder="0">
                                </div>
                                <div class="flex items-center space-x-3">
                                    <label class="w-20 text-[10px] font-bold text-gray-500 uppercase">Agen</label>
                                    <input type="number" name="price_agen" required class="flex-1 text-sm border-gray-200 bg-amber-50 rounded-xl focus:ring-amber-500 focus:border-amber-500 font-bold p-2 text-amber-700" placeholder="0">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="p-4 border-t border-gray-100 bg-white rounded-b-3xl shrink-0 space-y-3">
                    <div class="flex justify-between items-center bg-gray-900 p-4 rounded-xl shadow-inner">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Estimasi Modal (HPP)</span>
                        <span class="text-base font-black text-emerald-400" x-text="'Rp' + new Intl.NumberFormat('id-ID').format(calculateTotalModal())"></span>
                    </div>
                    <button type="button" @click="submitForm" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3.5 rounded-xl shadow-lg shadow-indigo-200 active:scale-95 transition-transform">
                        SIMPAN DETAIL KEMASAN
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('recipeManager', (ingredientsData) => ({
                showModal: false,
                dbIngredients: ingredientsData,
                selectedIngredients: [],
                packaging_cost: 0,
                overhead_cost: 0,

                addIngredientRow() {
                    this.selectedIngredients.push({
                        ingredient_id: '',
                        name: '',
                        unit: '',
                        cost_per_unit: 0,
                        quantity: 1
                    });
                },

                removeIngredientRow(index) {
                    this.selectedIngredients.splice(index, 1);
                },

                updateIngredientDetails(index) {
                    const row = this.selectedIngredients[index];
                    if (row.ingredient_id) {
                        const dbIng = this.dbIngredients.find(i => i.id == row.ingredient_id);
                        if (dbIng) {
                            row.name = dbIng.name;
                            row.unit = dbIng.unit;
                            row.cost_per_unit = dbIng.cost_per_unit;
                        }
                    } else {
                        row.name = '';
                        row.unit = '';
                        row.cost_per_unit = 0;
                    }
                },

                calculateTotalModal() {
                    let total = 0;
                    this.selectedIngredients.forEach(ing => {
                        const qty = parseFloat(ing.quantity) || 0;
                        const cost = parseFloat(ing.cost_per_unit) || 0;
                        total += qty * cost;
                    });
                    total += parseFloat(this.packaging_cost) || 0;
                    total += parseFloat(this.overhead_cost) || 0;
                    return Math.round(total * 100) / 100;
                },

                submitForm() {
                    if(this.selectedIngredients.length === 0) {
                        alert('Detail kemasan harus memiliki minimal 1 komoditas gudang!');
                        return;
                    }
                    document.getElementById('recipe-form').submit();
                }
            }));
        });
    </script>
</x-app-layout>
