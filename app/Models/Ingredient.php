<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    /** Status stok 1-Tap karyawan */
    public const STATUS_AMAN    = 'aman';
    public const STATUS_MENIPIS = 'menipis';
    public const STATUS_HABIS   = 'habis';

    public const STATUSES = [self::STATUS_AMAN, self::STATUS_MENIPIS, self::STATUS_HABIS];

    /** Kategori komoditas warung */
    public const KATEGORI = ['Sayur Segar', 'Bumbu Kecil', 'Sembako Kering'];

    protected $fillable = [
        'name', 'unit', 'kategori', 'image_path', 'current_stock', 'min_stock',
        'cost_per_unit', 'supplier_id', 'notes', 'is_active',
        // STOKKU ERP
        'stock_status', 'perishability_score', 'popularity_score',
        'recommended_restock_qty', 'is_fifo', 'fifo_note', 'last_status_updated_at',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'perishability_score'     => 'integer',
        'popularity_score'        => 'integer',
        'recommended_restock_qty' => 'decimal:2',
        'is_fifo'                 => 'boolean',
        'last_status_updated_at'  => 'datetime',
    ];

    /**
     * Sinkronisasi otomatis stock_status setiap kali current_stock / min_stock berubah.
     * Jika stock_status ikut diubah secara eksplisit pada save yang sama
     * (override manual tombol 1-Tap karyawan), nilai manual tersebut dihormati.
     */
    protected static function booted(): void
    {
        static::saving(function (Ingredient $ingredient) {
            $manualOverride = $ingredient->isDirty('stock_status');
            $stockChanged   = $ingredient->isDirty('current_stock') || $ingredient->isDirty('min_stock');

            if (! $manualOverride && ($stockChanged || ! $ingredient->exists)) {
                $ingredient->stock_status = $ingredient->determineStockStatus();
            }

            // Otomatis matikan penanda FIFO jika stok komoditas sudah habis (0 atau kurang)
            if ((float) $ingredient->current_stock <= 0) {
                $ingredient->is_fifo = false;
            }

            if ($ingredient->isDirty('stock_status') && ! $ingredient->isDirty('last_status_updated_at')) {
                $ingredient->last_status_updated_at = now();
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(IngredientPrice::class);
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Apakah stok di bawah minimum?
     */
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }

    /**
     * Hitung ulang cost_per_unit menggunakan Moving Average
     * dipanggil setiap ada pembelian baru
     */
    public function recalculateMovingAverage(): void
    {
        $lastPrices = $this->prices()->latest('purchased_at')->take(5)->get();
        if ($lastPrices->isNotEmpty()) {
            $this->cost_per_unit = $lastPrices->avg('price_per_unit');
            $this->save();
        }
    }

    /* =====================================================================
     |  STOKKU ERP — Status Stok, 1-Tap & FIFO
     * ===================================================================== */

    /**
     * Alias agar kode baru bisa memakai istilah `minimum_stock` (kolom fisik: min_stock).
     */
    public function getMinimumStockAttribute(): float
    {
        return (float) $this->min_stock;
    }

    /**
     * Hitung status stok otomatis dari angka stok:
     *  - stok <= 0             -> habis
     *  - stok <= minimum stok  -> menipis
     *  - selain itu            -> aman
     */
    public function determineStockStatus(): string
    {
        $stock = (float) $this->current_stock;
        $min   = (float) $this->min_stock;

        if ($stock <= 0) {
            return self::STATUS_HABIS;
        }

        if ($stock <= $min) {
            return self::STATUS_MENIPIS;
        }

        return self::STATUS_AMAN;
    }

    /**
     * Sinkronkan stock_status dengan current_stock (tanpa menyimpan kecuali $save = true).
     */
    public function syncStockStatus(bool $save = true): static
    {
        $this->stock_status = $this->determineStockStatus();

        if ($save) {
            $this->save();
        }

        return $this;
    }

    /**
     * Override manual status stok via tombol 1-Tap karyawan (Aman / Menipis / Habis).
     * Status 'habis' sekaligus menolkan current_stock (sesuai PRD 4.2) kecuali $zeroStockOnHabis = false.
     */
    public function setManualStockStatus(string $status, bool $zeroStockOnHabis = true): static
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException("Status stok tidak valid: {$status}");
        }

        $this->stock_status           = $status;
        $this->last_status_updated_at = now();

        if ($status === self::STATUS_HABIS && $zeroStockOnHabis) {
            $this->current_stock = 0;
            $this->is_fifo       = false;
        }

        $this->save();

        return $this;
    }

    /**
     * Perbarui stok (mis. setelah belanja / opname / kemas ulang) dan sinkronkan status otomatis.
     */
    public function adjustStock(float $delta): static
    {
        $this->current_stock = max(0, (float) $this->current_stock + $delta);
        $this->stock_status  = $this->determineStockStatus();
        $this->save();

        return $this;
    }

    /**
     * Apakah barang ini wajib diprioritaskan FIFO (dikemas/dijual duluan)?
     */
    public function isFifoPriority(): bool
    {
        return (bool) $this->is_fifo && (float) $this->current_stock > 0;
    }

    public function isHabis(): bool
    {
        return $this->stock_status === self::STATUS_HABIS;
    }

    public function isMenipis(): bool
    {
        return $this->stock_status === self::STATUS_MENIPIS;
    }

    /**
     * Estimasi modal belanja pasar berdasarkan saran restock x HPP rata-rata.
     */
    public function getEstimatedRestockCostAttribute(): float
    {
        return round((float) $this->recommended_restock_qty * (float) $this->cost_per_unit, 2);
    }

    /**
     * URL foto riil komoditas (dari storage upload, folder public/images/commodities, atau fallback).
     */
    public function getImageUrlAttribute(): string
    {
        return self::resolveImageUrl($this->image_path, $this->id, $this->name);
    }

    public static function resolveImageUrl(?string $imagePath, ?int $id = null, ?string $name = null): string
    {
        if ($imagePath) {
            if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
                return $imagePath;
            }
            $clean = ltrim($imagePath, '/');
            if (str_starts_with($clean, 'images/')) {
                return asset($clean);
            }
            return asset('storage/' . $clean);
        }

        if ($id && $name) {
            $slug = 'komoditas-' . $id . '-' . \Illuminate\Support\Str::slug($name);
            $candidate = "images/commodities/{$slug}.jpg";
            if (file_exists(public_path($candidate))) {
                return asset($candidate);
            }
        }

        return asset('images/commodities/default-commodity.svg');
    }

    /* ---------------------------- Query Scopes ---------------------------- */

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('stock_status', $status);
    }

    /** Barang berstatus habis / menipis (antrean SPK SAW & laporan WA 23:00). */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->whereIn('stock_status', [self::STATUS_HABIS, self::STATUS_MENIPIS]);
    }

    public function scopeFifo(Builder $query): Builder
    {
        return $query->where('is_fifo', true);
    }

    public function scopeKategori(Builder $query, string $kategori): Builder
    {
        return $query->where('kategori', $kategori);
    }
}
