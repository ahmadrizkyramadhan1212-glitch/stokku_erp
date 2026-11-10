<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'payment_method')) {
                $table->string('payment_method', 50)->default('Tunai')->after('total_amount');
            }
            if (! Schema::hasColumn('purchases', 'receipt_path')) {
                $table->string('receipt_path')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'receipt_path')) {
                $table->dropColumn('receipt_path');
            }
        });
    }
};
