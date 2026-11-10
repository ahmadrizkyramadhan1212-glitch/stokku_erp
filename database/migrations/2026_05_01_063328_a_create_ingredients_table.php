<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit'); // gram, kg, liter, ml, pcs, butir
            $table->decimal('current_stock', 10, 2)->default(0); // stok saat ini
            $table->decimal('min_stock', 10, 2)->default(0);     // batas minimum alert
            $table->decimal('cost_per_unit', 10, 2)->default(0); // harga per unit (moving avg)
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
