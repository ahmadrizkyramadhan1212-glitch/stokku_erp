<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'recipe_id', 'name', 'category', 'sale_unit', 'current_stock',
        'price_eceran', 'price_reseller', 'price_agen', 'hpp', 'description', 'is_active'
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'current_stock' => 'integer',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function dailySaleItems(): HasMany
    {
        return $this->hasMany(DailySaleItem::class);
    }

    public function productionLogs(): HasMany
    {
        return $this->hasMany(ProductionLog::class);
    }

    /**
     * STOKKU ERP: barang gudang utama yang terhubung lewat resep 1-to-1
     * (mis. "Cabai Keriting Merah per ons" -> Ingredient "Cabai Keriting Merah" per kg).
     */
    public function getPrimaryIngredientAttribute(): ?Ingredient
    {
        return $this->recipe?->items->first()?->ingredient;
    }

    /**
     * Status stok mengikuti barang gudang utama (fallback ke stok etalase).
     */
    public function getStockStatusAttribute(): string
    {
        $ingredient = $this->primary_ingredient;
        if ($ingredient) {
            return $ingredient->stock_status ?? $ingredient->determineStockStatus();
        }

        return (int) $this->current_stock > 0 ? Ingredient::STATUS_AMAN : Ingredient::STATUS_HABIS;
    }

    /**
     * Margin keuntungan per porsi (Default pakai harga eceran)
     */
    public function getMarginAttribute(): float
    {
        return $this->price_eceran - $this->hpp;
    }

    /**
     * Food Cost % (HPP / Harga Jual × 100)
     */
    public function getFoodCostPercentAttribute(): float
    {
        if ($this->price_eceran == 0) return 0;
        return round(($this->hpp / $this->price_eceran) * 100, 2);
    }

    /**
     * Hitung berapa banyak porsi yang bisa dibuat dari stok bahan baku yang ada
     */
    public function getProductionCapacityAttribute(): int
    {
        if (!$this->recipe || $this->recipe->items->isEmpty()) {
            return 0; // Tidak bisa diproduksi jika tidak ada resep
        }

        $minPortions = -1;

        foreach ($this->recipe->items as $item) {
            $ingredient = $item->ingredient;
            if (!$ingredient || $item->quantity <= 0) {
                continue;
            }

            // Kapasitas berdasarkan bahan ini
            $possiblePortions = floor($ingredient->current_stock / $item->quantity);

            if ($minPortions === -1 || $possiblePortions < $minPortions) {
                $minPortions = $possiblePortions;
            }
        }

        return $minPortions === -1 ? 0 : (int) $minPortions;
    }

    /**
     * Sync HPP dari kalkulasi resep terbaru
     */
    public function syncHpp(): void
    {
        if ($this->recipe) {
            $this->load('recipe.items.ingredient');
            $hpp = $this->recipe->calculateHpp();
            $this->update(['hpp' => $hpp]);
        }
    }
}
