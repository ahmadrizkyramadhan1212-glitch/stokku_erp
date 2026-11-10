<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientPrice extends Model
{
    protected $fillable = [
        'ingredient_id', 'supplier_id', 'price_per_unit', 'quantity', 'purchased_at', 'notes'
    ];

    protected $casts = ['purchased_at' => 'date'];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
