<?php

namespace Database\Seeders;

use App\Models\DailySale;
use App\Models\DailySaleItem;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\IngredientPrice;
use App\Models\MenuItem;
use App\Models\ProductionLog;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * STOKKU ERP — Seeder Studi Kasus "WARUNG SAYUR ADIJAYA"
 *
 * Mengisi:
 *  - Akun Owner Warung Sayur Adijaya & Karyawan Gudang Adijaya
 *  - Supplier Pasar Induk & Agen Grosir Sembako
 *  - Seluruh daftar komoditas asli WARUNG SAYUR ADIJAYA (Kategori A s/d I)
 *    dengan satuan warung akurat (kg, ikat, ons, buah, sisir, butir, bungkus,
 *    renceng, pouch, dus, botol, kotak, kaleng, galon, kantong, pcs, pack, tabung)
 *  - Histori 29 hari terakhir + transaksi hari ini untuk perhitungan
 *    SPK Metode SAW (C1–C4), Matriks BCG, Regresi Linear, dan Laba Bersih.
 */
class StokkuSeeder extends Seeder
{
    /** Jumlah hari histori sebelum hari ini (untuk grafik BI, Regresi Linier, SAW C3 & BCG). */
    private const HISTORY_DAYS = 29;

    public function run(): void
    {
        mt_srand(20261031); // data deterministik

        DB::transaction(function () {
            $today = Carbon::today();

            /* ------------------------------------------------------------
             | 1. AKUN PENGGUNA (OWNER & KARYAWAN GUDANG)
             * ------------------------------------------------------------ */
            $owner = User::updateOrCreate(
                ['email' => 'owner@adhijaya.com'],
                ['name' => 'Owner Warung Adhi Jaya', 'password' => Hash::make('password'), 'role' => 'admin', 'email_verified_at' => now()]
            );

            $karyawan = User::updateOrCreate(
                ['email' => 'staf@adhijaya.com'],
                ['name' => 'Staf Gudang Adhi Jaya', 'password' => Hash::make('password'), 'role' => 'gudang', 'email_verified_at' => now()]
            );

            User::updateOrCreate(
                ['email' => 'gudang@adhijaya.com'],
                ['name' => 'Staf Gudang Adhi Jaya', 'password' => Hash::make('password'), 'role' => 'gudang', 'email_verified_at' => now()]
            );

            User::updateOrCreate(
                ['email' => 'owner@stokku.com'],
                ['name' => 'Owner Warung Adhi Jaya', 'password' => Hash::make('password'), 'role' => 'admin', 'email_verified_at' => now()]
            );

            User::updateOrCreate(
                ['email' => 'karyawan@stokku.com'],
                ['name' => 'Staf Gudang Adhi Jaya', 'password' => Hash::make('password'), 'role' => 'gudang', 'email_verified_at' => now()]
            );

            /* ------------------------------------------------------------
             | 2. PEMASOK / SUPPLIER WARUNG SAYUR ADIJAYA
             * ------------------------------------------------------------ */
            $pasarInduk = Supplier::create([
                'name'           => 'Pasar Induk Sayur & Rempah',
                'contact'        => 'Lapak Bang Haji / Cak No',
                'phone'          => '081298765432',
                'address'        => 'Blok Sayur & Bumbu Pasar Induk',
                'kategori_bahan' => 'Sayur & Buah Segar, Bumbu Dapur & Rempah',
                'is_active'      => true,
            ]);

            $grosir = Supplier::create([
                'name'           => 'Agen Grosir Sembako & Harian Jaya',
                'contact'        => 'Koh Ahong',
                'phone'          => '081377712345',
                'address'        => 'Jl. Raya Pasar Grosir No. 18',
                'kategori_bahan' => 'Minyak, Saus & Kecap, Sembako & Bahan Pokok, Lauk, Minuman & Rumah Tangga',
                'is_active'      => true,
            ]);

            /* ------------------------------------------------------------
             | 3. KATALOG LENGKAP KOMODITAS "WARUNG SAYUR ADIJAYA"
             |    Kategori A s/d I dengan satuan & skor perishability akurat
             * ------------------------------------------------------------ */
            $rawCatalog = [
                // =========================================================
                // KATEGORI A - SAYUR & BUAH SEGAR (Perishability = 3)
                // =========================================================
                [
                    'name' => 'Jagung Manis', 'unit' => 'kg', 'sale_unit' => 'kantong', 'ratio' => 0.25,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 5,
                    'current_stock' => 3, 'min_stock' => 5, 'stock_status' => 'menipis', 'recommended_restock_qty' => 12,
                    'is_fifo' => true, 'fifo_note' => 'Kupas & kemas ke kantong plastik kecil sisa kemarin sebelum membongkar stok baru!',
                    'cost' => 12000, 'price' => [4000, 3700, 3500], 'etalase_stock' => 12,
                    'today' => [70, 40, 15], 'daily_base' => 90, 'repack' => true, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Terong', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 6, 'min_stock' => 4, 'stock_status' => 'aman', 'recommended_restock_qty' => 8,
                    'is_fifo' => false, 'cost' => 9000, 'price' => [13000, 12000, 11000], 'etalase_stock' => 6,
                    'today' => [4, 3, 1], 'daily_base' => 7, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Buncis', 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 4, 'min_stock' => 3, 'stock_status' => 'aman', 'recommended_restock_qty' => 6,
                    'is_fifo' => false, 'cost' => 14000, 'price' => [2000, 1800, 1700], 'etalase_stock' => 40,
                    'today' => [15, 10, 5], 'daily_base' => 25, 'repack' => true, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Labu Siam', 'unit' => 'buah', 'sale_unit' => 'buah', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 18, 'min_stock' => 10, 'stock_status' => 'aman', 'recommended_restock_qty' => 25,
                    'is_fifo' => false, 'cost' => 2500, 'price' => [4000, 3500, 3200], 'etalase_stock' => 18,
                    'today' => [8, 5, 2], 'daily_base' => 14, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Labu Kuning', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 3,
                    'current_stock' => 5, 'min_stock' => 3, 'stock_status' => 'aman', 'recommended_restock_qty' => 6,
                    'is_fifo' => false, 'cost' => 8000, 'price' => [12000, 11000, 10000], 'etalase_stock' => 5,
                    'today' => [2, 2, 1], 'daily_base' => 4, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Kol', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 7, 'min_stock' => 4, 'stock_status' => 'aman', 'recommended_restock_qty' => 10,
                    'is_fifo' => false, 'cost' => 8000, 'price' => [12000, 11000, 10500], 'etalase_stock' => 7,
                    'today' => [5, 3, 1], 'daily_base' => 8, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Sawi Putih', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 5, 'min_stock' => 3, 'stock_status' => 'aman', 'recommended_restock_qty' => 8,
                    'is_fifo' => false, 'cost' => 9000, 'price' => [13000, 12000, 11000], 'etalase_stock' => 5,
                    'today' => [4, 2, 1], 'daily_base' => 6, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Tomat', 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 5,
                    'current_stock' => 2, 'min_stock' => 4, 'stock_status' => 'menipis', 'recommended_restock_qty' => 10,
                    'is_fifo' => true, 'fifo_note' => 'Sortir tomat matang sisa kemarin di keranjang atas agar laku duluan!',
                    'cost' => 14000, 'price' => [2000, 1800, 1700], 'etalase_stock' => 20,
                    'today' => [25, 15, 8], 'daily_base' => 40, 'repack' => true, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Mentimun', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 8, 'min_stock' => 4, 'stock_status' => 'aman', 'recommended_restock_qty' => 10,
                    'is_fifo' => false, 'cost' => 8000, 'price' => [12000, 11000, 10000], 'etalase_stock' => 8,
                    'today' => [5, 3, 2], 'daily_base' => 9, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Sawi Hijau', 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 5,
                    'current_stock' => 12, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 2500, 'price' => [4000, 3500, 3200], 'etalase_stock' => 12,
                    'today' => [10, 6, 2], 'daily_base' => 16, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Selada', 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 3,
                    'current_stock' => 8, 'min_stock' => 5, 'stock_status' => 'aman', 'recommended_restock_qty' => 12,
                    'is_fifo' => false, 'cost' => 3000, 'price' => [5000, 4500, 4000], 'etalase_stock' => 8,
                    'today' => [4, 3, 1], 'daily_base' => 7, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Daun Kemangi', 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 10, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 1500, 'price' => [2500, 2200, 2000], 'etalase_stock' => 10,
                    'today' => [8, 4, 2], 'daily_base' => 12, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Seledri', 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 9, 'min_stock' => 5, 'stock_status' => 'aman', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 2000, 'price' => [3000, 2800, 2500], 'etalase_stock' => 9,
                    'today' => [6, 4, 2], 'daily_base' => 10, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Daun Bawang', 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 5,
                    'current_stock' => 10, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 2500, 'price' => [4000, 3500, 3200], 'etalase_stock' => 10,
                    'today' => [9, 5, 2], 'daily_base' => 14, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Tauge / Toge', 'unit' => 'ons', 'sale_unit' => 'ons', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 12, 'min_stock' => 15, 'stock_status' => 'menipis', 'recommended_restock_qty' => 30,
                    'is_fifo' => true, 'fifo_note' => 'Tauge sangat cepat asam! Kemas kantong plastik & keluarkan stok pagi ini sampai habis.',
                    'cost' => 1200, 'price' => [2000, 1800, 1600], 'etalase_stock' => 12,
                    'today' => [12, 8, 4], 'daily_base' => 20, 'repack' => true, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Alpukat', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 3,
                    'current_stock' => 6, 'min_stock' => 3, 'stock_status' => 'aman', 'recommended_restock_qty' => 8,
                    'is_fifo' => false, 'cost' => 22000, 'price' => [28000, 26500, 25000], 'etalase_stock' => 6,
                    'today' => [2, 1, 1], 'daily_base' => 4, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Pisang', 'unit' => 'sisir', 'sale_unit' => 'sisir', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 8, 'min_stock' => 4, 'stock_status' => 'aman', 'recommended_restock_qty' => 10,
                    'is_fifo' => false, 'cost' => 12000, 'price' => [16000, 15000, 14000], 'etalase_stock' => 8,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Mangga', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 3,
                    'current_stock' => 7, 'min_stock' => 3, 'stock_status' => 'aman', 'recommended_restock_qty' => 8,
                    'is_fifo' => false, 'cost' => 18000, 'price' => [24000, 22500, 21000], 'etalase_stock' => 7,
                    'today' => [2, 2, 1], 'daily_base' => 4, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Jeruk Makan', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 3,
                    'current_stock' => 6, 'min_stock' => 3, 'stock_status' => 'aman', 'recommended_restock_qty' => 8,
                    'is_fifo' => false, 'cost' => 20000, 'price' => [26000, 24500, 23000], 'etalase_stock' => 6,
                    'today' => [2, 1, 1], 'daily_base' => 4, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Jeruk Nipis', 'unit' => 'ons', 'sale_unit' => 'ons', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 25, 'min_stock' => 10, 'stock_status' => 'aman', 'recommended_restock_qty' => 30,
                    'is_fifo' => false, 'cost' => 1800, 'price' => [2500, 2300, 2100], 'etalase_stock' => 25,
                    'today' => [6, 4, 2], 'daily_base' => 10, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Kelapa', 'unit' => 'butir', 'sale_unit' => 'butir', 'ratio' => 1.0,
                    'kategori' => 'Sayur & Buah Segar', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 15, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 7000, 'price' => [10000, 9500, 9000], 'etalase_stock' => 15,
                    'today' => [5, 3, 2], 'daily_base' => 9, 'repack' => false, 'supplier' => $pasarInduk,
                ],

                // =========================================================
                // KATEGORI B - BUMBU DAPUR & REMPAH
                // (Perishability = 2 Umbi/Rimpang, 3 Cabai/Daun, 1 Kemasan)
                // =========================================================
                [
                    'name' => 'Cabai Keriting', 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 3, 'popularity_score' => 5,
                    'current_stock' => 0, 'min_stock' => 3, 'stock_status' => 'habis', 'recommended_restock_qty' => 8,
                    'is_fifo' => false, 'cost' => 45000, 'price' => [6000, 5500, 5000], 'etalase_stock' => 0,
                    'today' => [80, 50, 20], 'daily_base' => 110, 'repack' => true, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Daun Jeruk Purut', 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 0, 'min_stock' => 5, 'stock_status' => 'habis', 'recommended_restock_qty' => 10,
                    'is_fifo' => false, 'cost' => 10000, 'price' => [12000, 11500, 11000], 'etalase_stock' => 0,
                    'today' => [15, 8, 2], 'daily_base' => 20, 'repack' => true, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Cabai Kecil / Rawit', 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 3, 'popularity_score' => 5,
                    'current_stock' => 1.5, 'min_stock' => 3, 'stock_status' => 'menipis', 'recommended_restock_qty' => 8,
                    'is_fifo' => true, 'fifo_note' => 'Keluarkan cabai rawit petikan kemarin lebih dulu agar tangkainya tidak menghitam!',
                    'cost' => 50000, 'price' => [6500, 6000, 5500], 'etalase_stock' => 15,
                    'today' => [30, 20, 10], 'daily_base' => 50, 'repack' => true, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Cabai Besar', 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 4, 'min_stock' => 3, 'stock_status' => 'aman', 'recommended_restock_qty' => 6,
                    'is_fifo' => false, 'cost' => 42000, 'price' => [5500, 5200, 4800], 'etalase_stock' => 40,
                    'today' => [12, 8, 4], 'daily_base' => 20, 'repack' => true, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Daun Salam', 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 12, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 1500, 'price' => [2500, 2200, 2000], 'etalase_stock' => 12,
                    'today' => [6, 4, 2], 'daily_base' => 10, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Bawang Merah', 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 2, 'popularity_score' => 5,
                    'current_stock' => 2.5, 'min_stock' => 5, 'stock_status' => 'menipis', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 32000, 'price' => [4000, 3800, 3500], 'etalase_stock' => 25,
                    'today' => [50, 35, 15], 'daily_base' => 80, 'repack' => true, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Bawang Putih', 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 2, 'popularity_score' => 5,
                    'current_stock' => 6, 'min_stock' => 4, 'stock_status' => 'aman', 'recommended_restock_qty' => 12,
                    'is_fifo' => false, 'cost' => 36000, 'price' => [4500, 4200, 4000], 'etalase_stock' => 60,
                    'today' => [30, 20, 10], 'daily_base' => 50, 'repack' => true, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Bawang Bombay', 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 2, 'popularity_score' => 3,
                    'current_stock' => 4, 'min_stock' => 2, 'stock_status' => 'aman', 'recommended_restock_qty' => 5,
                    'is_fifo' => false, 'cost' => 30000, 'price' => [4000, 3700, 3500], 'etalase_stock' => 40,
                    'today' => [8, 5, 2], 'daily_base' => 12, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Jahe', 'unit' => 'ons', 'sale_unit' => 'ons', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 2, 'popularity_score' => 4,
                    'current_stock' => 30, 'min_stock' => 15, 'stock_status' => 'aman', 'recommended_restock_qty' => 30,
                    'is_fifo' => false, 'cost' => 2500, 'price' => [3500, 3200, 3000], 'etalase_stock' => 30,
                    'today' => [6, 4, 2], 'daily_base' => 10, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Lengkuas', 'unit' => 'ons', 'sale_unit' => 'ons', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 2, 'popularity_score' => 4,
                    'current_stock' => 28, 'min_stock' => 12, 'stock_status' => 'aman', 'recommended_restock_qty' => 25,
                    'is_fifo' => false, 'cost' => 1500, 'price' => [2500, 2200, 2000], 'etalase_stock' => 28,
                    'today' => [5, 3, 2], 'daily_base' => 9, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Kunyit', 'unit' => 'ons', 'sale_unit' => 'ons', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 2, 'popularity_score' => 4,
                    'current_stock' => 25, 'min_stock' => 10, 'stock_status' => 'aman', 'recommended_restock_qty' => 25,
                    'is_fifo' => false, 'cost' => 1800, 'price' => [2500, 2300, 2100], 'etalase_stock' => 25,
                    'today' => [5, 3, 2], 'daily_base' => 8, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Kencur', 'unit' => 'ons', 'sale_unit' => 'ons', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 2, 'popularity_score' => 3,
                    'current_stock' => 20, 'min_stock' => 10, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 3000, 'price' => [4500, 4200, 4000], 'etalase_stock' => 20,
                    'today' => [4, 2, 1], 'daily_base' => 6, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Kemiri', 'unit' => 'ons', 'sale_unit' => 'ons', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 2, 'popularity_score' => 4,
                    'current_stock' => 25, 'min_stock' => 10, 'stock_status' => 'aman', 'recommended_restock_qty' => 25,
                    'is_fifo' => false, 'cost' => 4500, 'price' => [6000, 5700, 5500], 'etalase_stock' => 25,
                    'today' => [5, 3, 2], 'daily_base' => 9, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Asam Jawa', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 20, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 2000, 'price' => [3000, 2800, 2500], 'etalase_stock' => 20,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Terasi', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 24, 'min_stock' => 10, 'stock_status' => 'aman', 'recommended_restock_qty' => 24,
                    'is_fifo' => false, 'cost' => 3500, 'price' => [5000, 4700, 4500], 'etalase_stock' => 24,
                    'today' => [4, 3, 1], 'daily_base' => 7, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Bawang Goreng', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 18, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 7000, 'price' => [10000, 9500, 9000], 'etalase_stock' => 18,
                    'today' => [4, 2, 1], 'daily_base' => 6, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Ketumbar Bubuk', 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 15, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 8500, 'price' => [11000, 10500, 10000], 'etalase_stock' => 15,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Ketumbar Biji', 'unit' => 'ons', 'sale_unit' => 'ons', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 20, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 3000, 'price' => [4500, 4200, 4000], 'etalase_stock' => 20,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Lada Bubuk', 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 16, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 9000, 'price' => [12000, 11500, 11000], 'etalase_stock' => 16,
                    'today' => [4, 2, 1], 'daily_base' => 6, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Lada Biji', 'unit' => 'ons', 'sale_unit' => 'ons', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 15, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 9000, 'price' => [12000, 11500, 11000], 'etalase_stock' => 15,
                    'today' => [2, 1, 1], 'daily_base' => 4, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Garam Halus', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 30, 'min_stock' => 10, 'stock_status' => 'aman', 'recommended_restock_qty' => 30,
                    'is_fifo' => false, 'cost' => 2000, 'price' => [3000, 2800, 2500], 'etalase_stock' => 30,
                    'today' => [6, 4, 2], 'daily_base' => 10, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Garam Kasar', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Bumbu Dapur & Rempah', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 20, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 2500, 'price' => [3500, 3200, 3000], 'etalase_stock' => 20,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $grosir,
                ],

                // =========================================================
                // KATEGORI C - MINYAK GORENG, SAUS & KECAP (Perishability = 1)
                // =========================================================
                [
                    'name' => 'Minyak Goreng Refill 1 Liter', 'unit' => 'dus', 'sale_unit' => 'pouch', 'ratio' => 0.083, // 1 dus = 12 pouch
                    'kategori' => 'Minyak, Saus & Kecap', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 12, 'min_stock' => 3, 'stock_status' => 'aman', 'recommended_restock_qty' => 5,
                    'is_fifo' => false, 'cost' => 195000, 'price' => [18000, 17500, 17000], 'etalase_stock' => 48,
                    'today' => [40, 25, 10], 'daily_base' => 60, 'repack' => true, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Minyak Goreng Refill 2 Liter', 'unit' => 'pouch', 'sale_unit' => 'pouch', 'ratio' => 1.0,
                    'kategori' => 'Minyak, Saus & Kecap', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 18, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 12,
                    'is_fifo' => false, 'cost' => 32000, 'price' => [35000, 34000, 33500], 'etalase_stock' => 18,
                    'today' => [6, 4, 2], 'daily_base' => 10, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Minyak Goreng Botol 1/2 Liter', 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0,
                    'kategori' => 'Minyak, Saus & Kecap', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 24, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 24,
                    'is_fifo' => false, 'cost' => 8500, 'price' => [10000, 9500, 9200], 'etalase_stock' => 24,
                    'today' => [8, 5, 2], 'daily_base' => 12, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Saus Sambal/Tomat Botol', 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0,
                    'kategori' => 'Minyak, Saus & Kecap', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 15, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 12,
                    'is_fifo' => false, 'cost' => 7500, 'price' => [9500, 9000, 8500], 'etalase_stock' => 15,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Saus Sambal/Tomat Refill', 'unit' => 'pouch', 'sale_unit' => 'pouch', 'ratio' => 1.0,
                    'kategori' => 'Minyak, Saus & Kecap', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 14, 'min_stock' => 5, 'stock_status' => 'aman', 'recommended_restock_qty' => 12,
                    'is_fifo' => false, 'cost' => 9000, 'price' => [11500, 11000, 10500], 'etalase_stock' => 14,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Saus Lada Hitam', 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0,
                    'kategori' => 'Minyak, Saus & Kecap', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 10, 'min_stock' => 4, 'stock_status' => 'aman', 'recommended_restock_qty' => 10,
                    'is_fifo' => false, 'cost' => 11000, 'price' => [14000, 13500, 13000], 'etalase_stock' => 10,
                    'today' => [2, 1, 1], 'daily_base' => 3, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Kecap Manis Botol Kaca', 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0,
                    'kategori' => 'Minyak, Saus & Kecap', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 12, 'min_stock' => 5, 'stock_status' => 'aman', 'recommended_restock_qty' => 12,
                    'is_fifo' => false, 'cost' => 16000, 'price' => [19000, 18500, 18000], 'etalase_stock' => 12,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Kecap Kecil', 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0,
                    'kategori' => 'Minyak, Saus & Kecap', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 25, 'min_stock' => 10, 'stock_status' => 'aman', 'recommended_restock_qty' => 24,
                    'is_fifo' => false, 'cost' => 4000, 'price' => [5500, 5200, 5000], 'etalase_stock' => 25,
                    'today' => [6, 4, 2], 'daily_base' => 10, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Kecap Refill', 'unit' => 'pouch', 'sale_unit' => 'pouch', 'ratio' => 1.0,
                    'kategori' => 'Minyak, Saus & Kecap', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 20, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 14500, 'price' => [17500, 17000, 16500], 'etalase_stock' => 20,
                    'today' => [5, 3, 2], 'daily_base' => 8, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Cuka Besar/Kecil', 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0,
                    'kategori' => 'Minyak, Saus & Kecap', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 15, 'min_stock' => 5, 'stock_status' => 'aman', 'recommended_restock_qty' => 12,
                    'is_fifo' => false, 'cost' => 3500, 'price' => [5000, 4700, 4500], 'etalase_stock' => 15,
                    'today' => [2, 1, 1], 'daily_base' => 3, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Saus Saori Botol', 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0,
                    'kategori' => 'Minyak, Saus & Kecap', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 12, 'min_stock' => 5, 'stock_status' => 'aman', 'recommended_restock_qty' => 12,
                    'is_fifo' => false, 'cost' => 10500, 'price' => [13000, 12500, 12000], 'etalase_stock' => 12,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Saus Saori Renceng', 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0,
                    'kategori' => 'Minyak, Saus & Kecap', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 18, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 18,
                    'is_fifo' => false, 'cost' => 21000, 'price' => [25000, 24000, 23000], 'etalase_stock' => 18,
                    'today' => [4, 3, 1], 'daily_base' => 7, 'repack' => false, 'supplier' => $grosir,
                ],

                // =========================================================
                // KATEGORI D & E - BUMBU PELENGKAP, TEPUNG & BAHAN POKOK
                // (Perishability = 1, kecuali Mie Basah = 3 & Telur Ayam = 2)
                // =========================================================
                [
                    'name' => 'Tepung Terigu', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 2, 'min_stock' => 5, 'stock_status' => 'menipis', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 11000, 'price' => [13000, 12500, 12000], 'etalase_stock' => 2,
                    'today' => [15, 10, 5], 'daily_base' => 25, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Mie Basah', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 2, 'min_stock' => 4, 'stock_status' => 'menipis', 'recommended_restock_qty' => 10,
                    'is_fifo' => true, 'fifo_note' => 'Mie basah kuning gilingan kemarin wajib habis pagi ini untuk langganan bakso/mie ayam!',
                    'cost' => 10000, 'price' => [13000, 12500, 12000], 'etalase_stock' => 2,
                    'today' => [6, 4, 2], 'daily_base' => 10, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Telur Ayam', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 2, 'popularity_score' => 5,
                    'current_stock' => 14, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 26000, 'price' => [29000, 28500, 28000], 'etalase_stock' => 14,
                    'today' => [10, 8, 4], 'daily_base' => 18, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Beras', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 50, 'min_stock' => 20, 'stock_status' => 'aman', 'recommended_restock_qty' => 50,
                    'is_fifo' => false, 'cost' => 13000, 'price' => [15000, 14500, 14200], 'etalase_stock' => 50,
                    'today' => [20, 12, 6], 'daily_base' => 30, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Masako', 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 24, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 24,
                    'is_fifo' => false, 'cost' => 4200, 'price' => [5500, 5200, 5000], 'etalase_stock' => 24,
                    'today' => [8, 5, 2], 'daily_base' => 12, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Royco', 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 24, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 24,
                    'is_fifo' => false, 'cost' => 4200, 'price' => [5500, 5200, 5000], 'etalase_stock' => 24,
                    'today' => [7, 5, 2], 'daily_base' => 11, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Ladaku', 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 20, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 9000, 'price' => [12000, 11500, 11000], 'etalase_stock' => 20,
                    'today' => [4, 3, 1], 'daily_base' => 7, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Sajiku Nasi Goreng', 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 18, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 18,
                    'is_fifo' => false, 'cost' => 16500, 'price' => [20000, 19000, 18500], 'etalase_stock' => 18,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Bumbu Racik Ayam/Ikan/Sop', 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 22, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 24,
                    'is_fifo' => false, 'cost' => 16000, 'price' => [20000, 19000, 18500], 'etalase_stock' => 22,
                    'today' => [5, 3, 2], 'daily_base' => 8, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Kacang Tanah', 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 8, 'min_stock' => 3, 'stock_status' => 'aman', 'recommended_restock_qty' => 10,
                    'is_fifo' => false, 'cost' => 28000, 'price' => [3500, 3300, 3100], 'etalase_stock' => 80,
                    'today' => [12, 8, 4], 'daily_base' => 20, 'repack' => true, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Kacang Hijau', 'unit' => 'kg', 'sale_unit' => 'ons', 'ratio' => 0.1,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 7, 'min_stock' => 3, 'stock_status' => 'aman', 'recommended_restock_qty' => 8,
                    'is_fifo' => false, 'cost' => 24000, 'price' => [3000, 2800, 2700], 'etalase_stock' => 70,
                    'today' => [8, 5, 2], 'daily_base' => 12, 'repack' => true, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Gula Pasir 1 Kg', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 20, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 16000, 'price' => [18500, 18000, 17500], 'etalase_stock' => 20,
                    'today' => [8, 5, 3], 'daily_base' => 14, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Gula Pasir 1/2 Kg', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 25, 'min_stock' => 10, 'stock_status' => 'aman', 'recommended_restock_qty' => 24,
                    'is_fifo' => false, 'cost' => 8000, 'price' => [9500, 9200, 9000], 'etalase_stock' => 25,
                    'today' => [10, 6, 3], 'daily_base' => 16, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Mentega', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 18, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 18,
                    'is_fifo' => false, 'cost' => 6000, 'price' => [8000, 7500, 7200], 'etalase_stock' => 18,
                    'today' => [4, 2, 1], 'daily_base' => 6, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Tepung Tapioka', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 20, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 6500, 'price' => [8500, 8000, 7800], 'etalase_stock' => 20,
                    'today' => [4, 3, 1], 'daily_base' => 7, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Tepung Kanji', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 18, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 18,
                    'is_fifo' => false, 'cost' => 6000, 'price' => [8000, 7500, 7200], 'etalase_stock' => 18,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Tepung Beras', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 22, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 7000, 'price' => [9000, 8500, 8200], 'etalase_stock' => 22,
                    'today' => [5, 3, 2], 'daily_base' => 8, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Agar-agar/Puding', 'unit' => 'kotak', 'sale_unit' => 'kotak', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 12, 'min_stock' => 4, 'stock_status' => 'aman', 'recommended_restock_qty' => 10,
                    'is_fifo' => false, 'cost' => 4500, 'price' => [6000, 5700, 5500], 'etalase_stock' => 12,
                    'today' => [2, 1, 1], 'daily_base' => 3, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Sagu Mutiara', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 16, 'min_stock' => 5, 'stock_status' => 'aman', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 2500, 'price' => [3500, 3200, 3000], 'etalase_stock' => 16,
                    'today' => [2, 1, 1], 'daily_base' => 3, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Sirup', 'unit' => 'botol', 'sale_unit' => 'botol', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 10, 'min_stock' => 4, 'stock_status' => 'aman', 'recommended_restock_qty' => 10,
                    'is_fifo' => false, 'cost' => 18000, 'price' => [22000, 21000, 20000], 'etalase_stock' => 10,
                    'today' => [1, 1, 1], 'daily_base' => 2, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Kerupuk Kering', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 20, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 5000, 'price' => [7000, 6500, 6000], 'etalase_stock' => 20,
                    'today' => [4, 3, 1], 'daily_base' => 7, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Kulit Lumpia', 'unit' => 'ikat', 'sale_unit' => 'ikat', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 15, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 4000, 'price' => [6000, 5500, 5000], 'etalase_stock' => 15,
                    'today' => [4, 2, 1], 'daily_base' => 6, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Mie Instan', 'unit' => 'dus', 'sale_unit' => 'bungkus', 'ratio' => 0.025, // 1 dus = 40 bungkus
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 6, 'min_stock' => 2, 'stock_status' => 'aman', 'recommended_restock_qty' => 4,
                    'is_fifo' => false, 'cost' => 112000, 'price' => [3500, 3200, 3000], 'etalase_stock' => 120,
                    'today' => [20, 15, 10], 'daily_base' => 35, 'repack' => true, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Bihun/Mie Kering', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Sembako & Bahan Pokok', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 20, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 4500, 'price' => [6000, 5700, 5500], 'etalase_stock' => 20,
                    'today' => [4, 3, 1], 'daily_base' => 7, 'repack' => false, 'supplier' => $grosir,
                ],

                // =========================================================
                // KATEGORI F, G, H, I - LAUK OLAHAN, MINUMAN, ES & RUMAH TANGGA
                // =========================================================
                [
                    'name' => 'Kembang Kol', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 3, 'popularity_score' => 4,
                    'current_stock' => 4, 'min_stock' => 3, 'stock_status' => 'aman', 'recommended_restock_qty' => 6,
                    'is_fifo' => false, 'cost' => 16000, 'price' => [22000, 20000, 19000], 'etalase_stock' => 4,
                    'today' => [2, 1, 1], 'daily_base' => 4, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Ikan Asin', 'unit' => 'ons', 'sale_unit' => 'ons', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 2, 'popularity_score' => 4,
                    'current_stock' => 25, 'min_stock' => 10, 'stock_status' => 'aman', 'recommended_restock_qty' => 25,
                    'is_fifo' => false, 'cost' => 4500, 'price' => [6500, 6000, 5800], 'etalase_stock' => 25,
                    'today' => [5, 3, 2], 'daily_base' => 8, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Sarden', 'unit' => 'kaleng', 'sale_unit' => 'kaleng', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 14, 'min_stock' => 5, 'stock_status' => 'aman', 'recommended_restock_qty' => 12,
                    'is_fifo' => false, 'cost' => 9500, 'price' => [12000, 11500, 11000], 'etalase_stock' => 14,
                    'today' => [2, 1, 1], 'daily_base' => 3, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Nugget', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 2, 'popularity_score' => 4,
                    'current_stock' => 12, 'min_stock' => 5, 'stock_status' => 'aman', 'recommended_restock_qty' => 12,
                    'is_fifo' => false, 'cost' => 18000, 'price' => [22000, 21000, 20000], 'etalase_stock' => 12,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Bakso', 'unit' => 'bungkus', 'sale_unit' => 'bungkus', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 2, 'popularity_score' => 4,
                    'current_stock' => 15, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 14000, 'price' => [18000, 17000, 16500], 'etalase_stock' => 15,
                    'today' => [4, 2, 1], 'daily_base' => 6, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Ubi Putih/Ungu', 'unit' => 'kg', 'sale_unit' => 'kg', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 2, 'popularity_score' => 3,
                    'current_stock' => 8, 'min_stock' => 4, 'stock_status' => 'aman', 'recommended_restock_qty' => 10,
                    'is_fifo' => false, 'cost' => 7000, 'price' => [10000, 9500, 9000], 'etalase_stock' => 8,
                    'today' => [2, 2, 1], 'daily_base' => 4, 'repack' => false, 'supplier' => $pasarInduk,
                ],
                [
                    'name' => 'Air Mineral Aqua', 'unit' => 'dus', 'sale_unit' => 'dus', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 10, 'min_stock' => 4, 'stock_status' => 'aman', 'recommended_restock_qty' => 8,
                    'is_fifo' => false, 'cost' => 34000, 'price' => [40000, 38500, 37500], 'etalase_stock' => 10,
                    'today' => [2, 1, 1], 'daily_base' => 3, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Air Minum Galon', 'unit' => 'galon', 'sale_unit' => 'galon', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 12, 'min_stock' => 5, 'stock_status' => 'aman', 'recommended_restock_qty' => 12,
                    'is_fifo' => false, 'cost' => 17000, 'price' => [20000, 19500, 19000], 'etalase_stock' => 12,
                    'today' => [4, 3, 2], 'daily_base' => 8, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Es Batu Kristal', 'unit' => 'kantong', 'sale_unit' => 'kantong', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 35, 'min_stock' => 15, 'stock_status' => 'aman', 'recommended_restock_qty' => 40,
                    'is_fifo' => false, 'cost' => 1000, 'price' => [2000, 1800, 1500], 'etalase_stock' => 35,
                    'today' => [15, 12, 8], 'daily_base' => 30, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Es Krim', 'unit' => 'pcs', 'sale_unit' => 'pcs', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 2, 'popularity_score' => 4,
                    'current_stock' => 30, 'min_stock' => 10, 'stock_status' => 'aman', 'recommended_restock_qty' => 30,
                    'is_fifo' => false, 'cost' => 3500, 'price' => [5000, 4500, 4200], 'etalase_stock' => 30,
                    'today' => [5, 6, 3], 'daily_base' => 12, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Sabun Mandi', 'unit' => 'pcs', 'sale_unit' => 'pcs', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 24, 'min_stock' => 8, 'stock_status' => 'aman', 'recommended_restock_qty' => 24,
                    'is_fifo' => false, 'cost' => 4000, 'price' => [5500, 5200, 5000], 'etalase_stock' => 24,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Deterjen Rinso Bubuk/Cair', 'unit' => 'renceng', 'sale_unit' => 'renceng', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 20, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 5000, 'price' => [6500, 6200, 6000], 'etalase_stock' => 20,
                    'today' => [4, 3, 1], 'daily_base' => 7, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Pasta Gigi', 'unit' => 'pcs', 'sale_unit' => 'pcs', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 18, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 18,
                    'is_fifo' => false, 'cost' => 6500, 'price' => [8500, 8000, 7800], 'etalase_stock' => 18,
                    'today' => [2, 1, 1], 'daily_base' => 3, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Tisu', 'unit' => 'pack', 'sale_unit' => 'pack', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 1, 'popularity_score' => 4,
                    'current_stock' => 16, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 7500, 'price' => [10000, 9500, 9000], 'etalase_stock' => 16,
                    'today' => [3, 2, 1], 'daily_base' => 5, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Sabut Cuci Piring', 'unit' => 'pcs', 'sale_unit' => 'pcs', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 1, 'popularity_score' => 3,
                    'current_stock' => 20, 'min_stock' => 6, 'stock_status' => 'aman', 'recommended_restock_qty' => 20,
                    'is_fifo' => false, 'cost' => 2000, 'price' => [3000, 2800, 2500], 'etalase_stock' => 20,
                    'today' => [2, 1, 1], 'daily_base' => 3, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Gas LPG 3 Kg', 'unit' => 'tabung', 'sale_unit' => 'tabung', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 1, 'popularity_score' => 5,
                    'current_stock' => 10, 'min_stock' => 4, 'stock_status' => 'aman', 'recommended_restock_qty' => 15,
                    'is_fifo' => false, 'cost' => 19000, 'price' => [22000, 21500, 21000], 'etalase_stock' => 10,
                    'today' => [5, 3, 2], 'daily_base' => 9, 'repack' => false, 'supplier' => $grosir,
                ],
                [
                    'name' => 'Pisau Cutter', 'unit' => 'pcs', 'sale_unit' => 'pcs', 'ratio' => 1.0,
                    'kategori' => 'Lauk, Minuman & Rumah Tangga', 'perishability_score' => 1, 'popularity_score' => 2,
                    'current_stock' => 12, 'min_stock' => 4, 'stock_status' => 'aman', 'recommended_restock_qty' => 12,
                    'is_fifo' => false, 'cost' => 3500, 'price' => [5000, 4500, 4200], 'etalase_stock' => 12,
                    'today' => [1, 0, 0], 'daily_base' => 1, 'repack' => false, 'supplier' => $grosir,
                ],
            ];

            $items = [];
            $featuredNames = [
                'Cabai Keriting', 'Daun Jeruk Purut', 'Jagung Manis', 'Bawang Merah',
                'Cabai Kecil / Rawit', 'Tomat', 'Tauge / Toge', 'Mie Basah',
                'Tepung Terigu', 'Minyak Goreng Refill 1 Liter', 'Telur Ayam',
                'Beras', 'Es Batu Kristal', 'Gas LPG 3 Kg', 'Sawi Hijau', 'Mie Instan',
            ];

            foreach ($rawCatalog as $idx => $cfg) {
                $statusTime = sprintf('%02d:%02d', 8 + ($idx % 14), ($idx * 7) % 60);

                $slug = 'komoditas-' . ($idx + 1) . '-' . \Illuminate\Support\Str::slug($cfg['name']);
                $ingredient = Ingredient::create([
                    'name'                    => $cfg['name'],
                    'unit'                    => $cfg['unit'],
                    'kategori'                => $cfg['kategori'],
                    'image_path'              => "images/commodities/{$slug}.jpg",
                    'current_stock'           => $cfg['current_stock'],
                    'min_stock'               => $cfg['min_stock'],
                    'cost_per_unit'           => $cfg['cost'],
                    'supplier_id'             => $cfg['supplier']->id,
                    'is_active'               => true,
                    'stock_status'            => $cfg['stock_status'],
                    'perishability_score'     => $cfg['perishability_score'],
                    'popularity_score'        => $cfg['popularity_score'],
                    'recommended_restock_qty' => $cfg['recommended_restock_qty'],
                    'is_fifo'                 => $cfg['is_fifo'] ?? false,
                    'fifo_note'               => $cfg['fifo_note'] ?? null,
                    'last_status_updated_at'  => $today->copy()->setTimeFromTimeString($statusTime),
                    'notes'                   => "Harga beli acuan Rp " . number_format($cfg['cost'], 0, ',', '.') . " / {$cfg['unit']}",
                ]);

                // Konversi Kemas Ulang / Satuan Etalase 1-to-1 (Tanpa istilah "Resep" / "Porsi")
                $recipe = Recipe::create([
                    'name'           => "Kemas Etalase {$cfg['name']} (per {$cfg['sale_unit']})",
                    'description'    => "1 {$cfg['sale_unit']} = {$cfg['ratio']} {$cfg['unit']} {$cfg['name']}",
                    'serving_qty'    => 1,
                    'packaging_cost' => ($cfg['repack'] ?? false) ? 100 : 0,
                    'overhead_cost'  => 0,
                ]);

                RecipeItem::create([
                    'recipe_id'     => $recipe->id,
                    'ingredient_id' => $ingredient->id,
                    'quantity'      => $cfg['ratio'],
                ]);

                $hppPerSaleUnit = round($cfg['cost'] * $cfg['ratio'], 2) + (($cfg['repack'] ?? false) ? 100 : 0);

                $menu = MenuItem::create([
                    'recipe_id'      => $recipe->id,
                    'name'           => $cfg['name'],
                    'category'       => $cfg['kategori'],
                    'sale_unit'      => $cfg['sale_unit'],
                    'current_stock'  => $cfg['etalase_stock'],
                    'price_eceran'   => $cfg['price'][0],
                    'price_reseller' => $cfg['price'][1],
                    'price_agen'     => $cfg['price'][2],
                    'hpp'            => $hppPerSaleUnit,
                    'description'    => "Satuan keluar per {$cfg['sale_unit']}",
                    'is_active'      => true,
                ]);

                $items[$cfg['name']] = [
                    'ingredient' => $ingredient,
                    'menu'       => $menu,
                    'cfg'        => $cfg,
                    'featured'   => in_array($cfg['name'], $featuredNames, true),
                ];
            }

            /* ------------------------------------------------------------
             | 4. HISTORI 29 HARI TERAKHIR (SUPER CEPAT & REALISTIS)
             * ------------------------------------------------------------ */
            for ($d = self::HISTORY_DAYS; $d >= 1; $d--) {
                $date = $today->copy()->subDays($d);
                $this->seedHistoricalDay($date, $d, $items, $owner, $karyawan, $pasarInduk, $grosir);
            }

            /* ------------------------------------------------------------
             | 5. TRANSAKSI HARI INI WARUNG SAYUR ADIJAYA
             * ------------------------------------------------------------ */
            $this->seedToday($today, $items, $owner, $karyawan, $pasarInduk, $grosir);
        });
    }

    /**
     * Satu hari histori: belanja pasar, kemas ulang, barang keluar 2 sesi, biaya operasional & barang busuk.
     */
    private function seedHistoricalDay(Carbon $date, int $daysAgo, array $items, User $owner, User $karyawan, Supplier $pasarInduk, Supplier $grosir): void
    {
        $trend   = 0.72 + (self::HISTORY_DAYS - $daysAgo) * 0.009;
        $weekend = $date->isWeekend() ? 1.15 : 1.0;

        // Fokus histori harian pada komoditas utama + rotasi seluruh komoditas agar setiap barang punya histori BCG & SAW C3
        $activeSubset = [];
        foreach ($items as $name => $row) {
            if ($row['featured'] || (($row['ingredient']->id + $daysAgo) % 3 === 0)) {
                $activeSubset[$name] = $row;
            }
        }

        $soldPlan = [];
        foreach ($activeSubset as $name => $row) {
            $noise = mt_rand(85, 115) / 100;
            $soldPlan[$name] = max(2, (int) round($row['cfg']['daily_base'] * $trend * $weekend * $noise));
        }

        // Belanja pasar subuh
        $sayurSubset = array_filter($activeSubset, fn ($r) => $r['cfg']['supplier']->is($pasarInduk));
        $this->createPurchase($date->copy()->setTime(5, 30), $owner, $pasarInduk, $sayurSubset, $soldPlan, 'Kulakan subuh Pasar Induk', true);

        // Kulakan grosir sembako setiap 3 hari
        if ($daysAgo % 3 === 0) {
            $sembakoSubset = array_filter($activeSubset, fn ($r) => $r['cfg']['supplier']->is($grosir));
            $this->createPurchase($date->copy()->setTime(9, 15), $owner, $grosir, $sembakoSubset, $soldPlan, 'Kulakan agen grosir sembako', true, 3);
        }

        // Kemas ulang komoditas segar
        foreach ($activeSubset as $name => $row) {
            if (! ($row['cfg']['repack'] ?? false)) {
                continue;
            }
            $waste = $row['cfg']['perishability_score'] >= 3 ? mt_rand(0, (int) ceil($soldPlan[$name] * 0.05)) : 0;
            $this->stamp(new ProductionLog([
                'menu_item_id'    => $row['menu']->id,
                'quantity'        => $soldPlan[$name] + $waste,
                'production_date' => $date->toDateString(),
            ]), $date->copy()->setTime(6, 30))->save();
        }

        // Rekap Barang Keluar 2 sesi (Pasca Jam Sibuk Pagi 11:15 & Pasca Jam Sibuk Sore 18:00)
        $sessions = [
            ['share' => 0.62, 'hour' => 11, 'buyer' => ($daysAgo % 2 === 0 ? 'Reseller' : 'Eceran')],
            ['share' => 0.38, 'hour' => 18, 'buyer' => 'Eceran'],
        ];

        $remaining = $soldPlan;
        foreach ($sessions as $idx => $session) {
            $isLast = $idx === array_key_last($sessions);
            $at     = $date->copy()->setTime($session['hour'], mt_rand(10, 45));

            $lines = [];
            foreach ($activeSubset as $name => $row) {
                $qty = $isLast ? $remaining[$name] : (int) round($soldPlan[$name] * $session['share']);
                $qty = min($qty, $remaining[$name]);
                $remaining[$name] -= $qty;
                if ($qty > 0) {
                    $lines[] = [$row['menu'], $qty];
                }
            }

            if (! empty($lines)) {
                $this->createSale($at, $karyawan, 'Tunai', $session['buyer'], $lines);
            }
        }

        // Biaya operasional harian
        $this->stamp(new Expense([
            'user_id'      => $owner->id,
            'category'     => 'Plastik Kresek & Kemasan',
            'description'  => 'Plastik kresek eceran, karet gelang & bensin pasar',
            'amount'       => mt_rand(5, 9) * 5000,
            'expense_date' => $date->toDateString(),
        ]), $date->copy()->setTime(21, 0))->save();

        // Kerugian barang busuk/susut sesekali
        if ($daysAgo % 4 === 0) {
            $perishables = array_values(array_filter($activeSubset, fn ($r) => $r['cfg']['perishability_score'] >= 3));
            if (! empty($perishables)) {
                $row  = $perishables[array_rand($perishables)];
                $qty  = 0.5;
                $loss = max(2000, round($qty * $row['cfg']['cost'], -2));

                $this->stamp(new Expense([
                    'user_id'      => $owner->id,
                    'category'     => 'Barang Busuk/Susut',
                    'description'  => "Koreksi Fisik (Barang Busuk/Susut): {$row['cfg']['name']} (-{$qty} {$row['cfg']['unit']})",
                    'amount'       => $loss,
                    'expense_date' => $date->toDateString(),
                ]), $date->copy()->setTime(22, 30))->save();
            }
        }
    }

    /**
     * Transaksi hari ini untuk Warung Sayur Adijaya.
     */
    private function seedToday(Carbon $today, array $items, User $owner, User $karyawan, Supplier $pasarInduk, Supplier $grosir): void
    {
        // Belanja struk pasar hari ini
        $this->createFixedPurchase($today->copy()->setTime(5, 20), $owner, $pasarInduk, 'Struk belanja subuh Pasar Induk Sayur & Rempah', [
            [$items['Cabai Keriting']['ingredient'],      10, 45000],
            [$items['Daun Jeruk Purut']['ingredient'],     8, 10000],
            [$items['Jagung Manis']['ingredient'],        14, 12000],
            [$items['Bawang Merah']['ingredient'],         5, 32000],
        ]);

        $this->createFixedPurchase($today->copy()->setTime(9, 15), $owner, $grosir, 'Kulakan agen grosir sembako & minyak', [
            [$items['Tepung Terigu']['ingredient'],               12, 11000],
            [$items['Minyak Goreng Refill 1 Liter']['ingredient'], 1, 195000],
        ]);

        // Kemas ulang hari ini untuk barang repack utama
        foreach ($items as $row) {
            if (! ($row['cfg']['repack'] ?? false) || ! $row['featured']) {
                continue;
            }
            $this->stamp(new ProductionLog([
                'menu_item_id'    => $row['menu']->id,
                'quantity'        => array_sum($row['cfg']['today']) + (int) $row['menu']->current_stock,
                'production_date' => $today->toDateString(),
            ]), $today->copy()->setTime(6, 30))->save();
        }

        // Rekap Barang Keluar hari ini (3 sesi: Pagi 11:00, Sore 17:30, Malam 21:45)
        $sessions = [
            ['at' => '11:00', 'buyer' => 'Eceran'],
            ['at' => '17:30', 'buyer' => 'Reseller'],
            ['at' => '21:45', 'buyer' => 'Eceran'],
        ];

        foreach ($sessions as $idx => $session) {
            $lines = [];
            foreach ($items as $row) {
                if (! $row['featured']) {
                    continue;
                }
                $qty = $row['cfg']['today'][$idx] ?? 0;
                if ($qty > 0) {
                    $lines[] = [$row['menu'], $qty];
                }
            }
            $this->createSale($today->copy()->setTimeFromTimeString($session['at']), $karyawan, 'Tunai', $session['buyer'], $lines);
        }
    }

    /**
     * Buat 1 rekap barang keluar (DailySale + items).
     */
    private function createSale(Carbon $at, User $user, string $payment, string $buyerType, array $lines): DailySale
    {
        $sale = $this->stamp(new DailySale([
            'user_id'        => $user->id,
            'sale_date'      => $at->toDateString(),
            'payment_method' => $payment,
            'total_revenue'  => 0,
            'total_hpp'      => 0,
            'gross_profit'   => 0,
            'notes'          => "Rekap Barang Keluar ({$buyerType})",
        ]), $at);
        $sale->save();

        foreach ($lines as [$menu, $qty]) {
            $this->stamp(DailySaleItem::makeFromQty($sale, $menu, $qty, $buyerType), $at)->save();
        }

        $sale->timestamps = false;
        $sale->recalculateTotals();

        return $sale;
    }

    /**
     * Belanja histori.
     */
    private function createPurchase(Carbon $at, User $owner, Supplier $supplier, array $rows, array $soldPlan, string $notes, bool $vary, int $coverDays = 1): void
    {
        $lines = [];
        foreach ($rows as $name => $row) {
            $need  = ($soldPlan[$name] ?? 2) * $row['cfg']['ratio'] * $coverDays;
            $qty   = max(1, $row['cfg']['unit'] === 'kg' ? round($need * 2) / 2 : ceil($need));
            $price = $vary ? max(500, round($row['cfg']['cost'] * mt_rand(95, 105) / 100, -2)) : $row['cfg']['cost'];
            $lines[] = [$row['ingredient'], $qty, $price];
        }

        if (! empty($lines)) {
            $this->createFixedPurchase($at, $owner, $supplier, $notes, $lines);
        }
    }

    /**
     * Buat transaksi pembelian (Purchase + PurchaseItem + IngredientPrice).
     */
    private function createFixedPurchase(Carbon $at, User $owner, Supplier $supplier, string $notes, array $lines): Purchase
    {
        $purchase = $this->stamp(new Purchase([
            'user_id'        => $owner->id,
            'supplier_id'    => $supplier->id,
            'purchase_date'  => $at->toDateString(),
            'total_amount'   => 0,
            'payment_method' => 'Tunai',
            'notes'          => $notes,
        ]), $at);
        $purchase->save();

        foreach ($lines as [$ingredient, $qty, $price]) {
            $this->stamp(new PurchaseItem([
                'purchase_id'    => $purchase->id,
                'ingredient_id'  => $ingredient->id,
                'quantity'       => $qty,
                'price_per_unit' => $price,
                'subtotal'       => $qty * $price,
            ]), $at)->save();

            $this->stamp(new IngredientPrice([
                'ingredient_id'  => $ingredient->id,
                'supplier_id'    => $supplier->id,
                'price_per_unit' => $price,
                'quantity'       => $qty,
                'purchased_at'   => $at->toDateString(),
                'notes'          => $notes,
            ]), $at)->save();
        }

        $purchase->timestamps = false;
        $purchase->load('items')->recalculateTotal();

        return $purchase;
    }

    /**
     * @template T of Model
     * @param T $model
     * @return T
     */
    private function stamp(Model $model, Carbon $at): Model
    {
        $model->created_at = $at;
        $model->updated_at = $at;

        return $model;
    }
}
