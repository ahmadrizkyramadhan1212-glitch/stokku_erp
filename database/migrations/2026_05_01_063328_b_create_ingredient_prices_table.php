<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Riwayat harga beli bahan baku (untuk moving average & analisis historis)
        Schema::create('ingredient_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price_per_unit', 10, 2); // harga beli per unit saat itu
            $table->decimal('quantity', 10, 2);        // jumlah yang dibeli
            $table->date('purchased_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_prices');
    }
};
