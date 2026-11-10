<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Purchase extends Model
{
    protected $fillable = [
        'user_id',
        'supplier_id',
        'purchase_date',
        'total_amount',
        'payment_method',
        'notes',
        'receipt_path',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount'  => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function recalculateTotal(): void
    {
        $this->total_amount = (float) $this->items->sum('subtotal');
        $this->save();
    }

    public function getReceiptUrlAttribute(): ?string
    {
        if (empty($this->receipt_path)) {
            return null;
        }

        $path = ltrim((string) $this->receipt_path, '/');

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        return asset('storage/' . $path);
    }
}
