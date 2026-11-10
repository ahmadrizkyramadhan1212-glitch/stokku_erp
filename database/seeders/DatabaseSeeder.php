<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed database STOKKU ERP — Studi Kasus: WARUNG SAYUR ADIJAYA.
     * Menggunakan katalog komoditas asli (Kategori A s/d I) dengan satuan warung akurat.
     */
    public function run(): void
    {
        $this->call([
            StokkuSeeder::class,
            RealisticTransactionSeeder::class,
        ]);
    }
}
