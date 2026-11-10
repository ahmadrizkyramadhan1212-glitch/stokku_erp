<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailySale extends Model
{
    protected $fillable = [
        'user_id', 'sale_date', 'total_revenue', 'total_hpp', 'gross_profit', 'payment_method', 'notes'
    ];

    protected $casts = ['sale_date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DailySaleItem::class);
    }

    /**
     * Hitung ulang total dari items dan simpan
     */
    public function recalculateTotals(): void
    {
        $this->load('items');
        $this->total_revenue = $this->items->sum('subtotal_revenue');
        $this->total_hpp     = $this->items->sum('subtotal_hpp');
        $this->gross_profit  = $this->total_revenue - $this->total_hpp;
        $this->save();
    }

    /**
     * Food Cost % hari ini
     */
    public function getFoodCostPercentAttribute(): float
    {
        if ($this->total_revenue == 0) return 0;
        return round(($this->total_hpp / $this->total_revenue) * 100, 2);
    }
}
