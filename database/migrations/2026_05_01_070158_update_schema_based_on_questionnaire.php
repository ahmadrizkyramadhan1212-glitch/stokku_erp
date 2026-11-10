<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update Tabel Recipes (Tambah Biaya Kemasan & Overhead per porsi)
        Schema::table('recipes', function (Blueprint $table) {
            $table->decimal('packaging_cost', 10, 2)->default(0)->after('serving_qty');
            $table->decimal('overhead_cost', 10, 2)->default(0)->after('packaging_cost');
        });

        // 2. Update Tabel Menu Items (Multi-harga)
        Schema::table('menu_items', function (Blueprint $table) {
            $table->renameColumn('selling_price', 'price_eceran');
        });
        
        Schema::table('menu_items', function (Blueprint $table) {
            $table->decimal('price_reseller', 10, 2)->default(0)->after('price_eceran');
            $table->decimal('price_agen', 10, 2)->default(0)->after('price_reseller');
        });

        // 3. Update Tabel Daily Sales (Metode Pembayaran)
        Schema::table('daily_sales', function (Blueprint $table) {
            $table->string('payment_method')->default('Tunai')->after('gross_profit'); // Tunai / Transfer
        });

        // 4. Update Tabel Daily Sale Items (Tipe Pembeli)
        Schema::table('daily_sale_items', function (Blueprint $table) {
            $table->string('buyer_type')->default('Eceran')->after('menu_item_id'); // Eceran / Reseller / Agen
        });

        // 5. Update Tabel Purchases (Metode Pembayaran)
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('payment_method')->default('Tunai')->after('total_amount'); // Tunai / Transfer
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });

        Schema::table('daily_sale_items', function (Blueprint $table) {
            $table->dropColumn('buyer_type');
        });

        Schema::table('daily_sales', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['price_reseller', 'price_agen']);
        });
        
        Schema::table('menu_items', function (Blueprint $table) {
            $table->renameColumn('price_eceran', 'selling_price');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['packaging_cost', 'overhead_cost']);
        });
    }
};
