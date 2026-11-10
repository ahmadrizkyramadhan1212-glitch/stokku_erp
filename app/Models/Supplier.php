<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = ['name', 'contact', 'phone', 'address', 'kategori_bahan', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function ingredientPrices(): HasMany
    {
        return $this->hasMany(IngredientPrice::class);
    }
}
