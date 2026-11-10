<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header penjualan harian (input manual oleh kasir)
        Schema::create('daily_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // siapa yang input
            $table->date('sale_date');                    // tanggal penjualan
            $table->decimal('total_revenue', 12, 2)->default(0);  // total pendapatan
            $table->decimal('total_hpp', 12, 2)->default(0);      // total HPP
            $table->decimal('gross_profit', 12, 2)->default(0);   // total laba kotor
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('sale_date'); // satu input per hari
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_sales');
    }
};
