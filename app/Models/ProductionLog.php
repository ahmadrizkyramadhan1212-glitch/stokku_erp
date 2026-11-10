<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_item_id',
        'quantity',
        'production_date',
    ];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
