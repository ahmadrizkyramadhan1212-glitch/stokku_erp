<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySaleItem extends Model
{
    protected $fillable = [
        'daily_sale_id', 'menu_item_id', 'buyer_type', 'qty_sold',
        'selling_price', 'hpp_per_item',
        'subtotal_revenue', 'subtotal_hpp', 'contribution_margin'
    ];

    public function dailySale(): BelongsTo
    {
        return $this->belongsTo(DailySale::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * Hitung semua subtotal otomatis dari qty dan harga snapshot
     */
    public static function makeFromQty(DailySale $sale, MenuItem $menu, int $qty, string $buyerType = 'Eceran'): self
    {
        $sellingPrice = $menu->price_eceran;
        if ($buyerType === 'Reseller') {
            $sellingPrice = $menu->price_reseller ?: $menu->price_eceran;
        } elseif ($buyerType === 'Agen') {
            $sellingPrice = $menu->price_agen ?: $menu->price_eceran;
        }

        $subtotalRevenue = $qty * $sellingPrice;
        $subtotalHpp     = $qty * $menu->hpp;

        return new self([
            'daily_sale_id'      => $sale->id,
            'menu_item_id'       => $menu->id,
            'buyer_type'         => $buyerType,
            'qty_sold'           => $qty,
            'selling_price'      => $sellingPrice,
            'hpp_per_item'       => $menu->hpp,
            'subtotal_revenue'   => $subtotalRevenue,
            'subtotal_hpp'       => $subtotalHpp,
            'contribution_margin'=> $subtotalRevenue - $subtotalHpp,
        ]);
    }
}
