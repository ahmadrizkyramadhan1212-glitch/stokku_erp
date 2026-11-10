<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Recipe extends Model
{
    protected $fillable = ['name', 'description', 'serving_qty', 'packaging_cost', 'overhead_cost'];

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function menuItem(): HasOne
    {
        return $this->hasOne(MenuItem::class);
    }

    /**
     * Hitung HPP per porsi berdasarkan harga bahan terkini
     */
    public function calculateHpp(): float
    {
        $totalBahan = 0;
        foreach ($this->items as $item) {
            $ingredient = $item->ingredient;
            // cost_per_unit adalah harga per 1 unit (gram/ml/pcs)
            if ($ingredient) {
                $totalBahan += $item->quantity * $ingredient->cost_per_unit;
            }
        }
        
        $hppPerPorsi = ($totalBahan / max($this->serving_qty, 1)) + $this->packaging_cost + $this->overhead_cost;
        return round($hppPerPorsi, 2);
    }
}
