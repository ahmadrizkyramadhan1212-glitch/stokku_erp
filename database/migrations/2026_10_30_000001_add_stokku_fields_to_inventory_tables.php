<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * STOKKU ERP — Fase 1
 * Upgrade non-destruktif tabel inventori untuk Warung Sembako & Sayur:
 *  - ingredients : kategori, status stok 1-Tap, kriteria SPK SAW (C2, C3), saran restock, FIFO.
 *  - menu_items  : satuan jual eceran (ons / bks / kantong / perempat / kg / liter).
 *  - wa_report_logs : riwayat laporan tutup warung jam 23:00 WIB ke WhatsApp Owner.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Stok Gudang / Barang Curah Pasar
        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('kategori')->default('Sayur Segar')->after('unit');           // Sayur Segar | Bumbu Kecil | Sembako Kering
            $table->string('stock_status', 20)->default('aman')->after('min_stock');    // aman | menipis | habis
            $table->unsignedTinyInteger('perishability_score')->default(3)->after('stock_status'); // C2 SAW: 3=Cepat Busuk, 2=Sedang, 1=Tahan Lama
            $table->unsignedTinyInteger('popularity_score')->default(4)->after('perishability_score'); // C3 SAW: skala 1-5 kelarisan
            $table->decimal('recommended_restock_qty', 10, 2)->default(10)->after('popularity_score'); // saran jumlah beli pasar
            $table->boolean('is_fifo')->default(false)->after('recommended_restock_qty'); // barang sisa kemarin wajib keluar duluan
            $table->string('fifo_note')->nullable()->after('is_fifo');                    // instruksi FIFO untuk karyawan
            $table->timestamp('last_status_updated_at')->nullable()->after('fifo_note');

            $table->index('kategori');
            $table->index('stock_status');
        });

        // 2. Barang Etalase Siap Jual (selaras dengan ingredients via recipes 1-to-1)
        Schema::table('menu_items', function (Blueprint $table) {
            $table->string('sale_unit', 30)->nullable()->after('category'); // satuan jual eceran: ons, bks, kantong, perempat, kg, liter
        });

        // 3. Riwayat Laporan Tutup Warung (WA 23:00 WIB)
        Schema::create('wa_report_logs', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->string('target_phone', 30);
            $table->text('message_content');
            $table->string('status', 20)->default('pending'); // pending | sent | failed | manual_link
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('report_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_report_logs');

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('sale_unit');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropIndex(['kategori']);
            $table->dropIndex(['stock_status']);
            $table->dropColumn([
                'kategori',
                'stock_status',
                'perishability_score',
                'popularity_score',
                'recommended_restock_qty',
                'is_fifo',
                'fifo_note',
                'last_status_updated_at',
            ]);
        });
    }
};

