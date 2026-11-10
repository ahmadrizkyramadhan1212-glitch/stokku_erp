<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Detail per menu dalam satu hari penjualan
        Schema::create('daily_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->integer('qty_sold');                         // berapa porsi terjual hari ini
            $table->decimal('selling_price', 10, 2);             // snapshot harga jual saat itu
            $table->decimal('hpp_per_item', 10, 2);              // snapshot HPP saat itu
            $table->decimal('subtotal_revenue', 12, 2);          // qty × selling_price
            $table->decimal('subtotal_hpp', 12, 2);              // qty × hpp_per_item
            $table->decimal('contribution_margin', 12, 2);       // subtotal_revenue - subtotal_hpp
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_sale_items');
    }
};
