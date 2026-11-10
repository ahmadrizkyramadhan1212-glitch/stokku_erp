<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Riwayat pengiriman Laporan Tutup Warung (jam 23:00 WIB) ke WhatsApp Owner.
 */
class WaReportLog extends Model
{
    public const STATUS_PENDING     = 'pending';
    public const STATUS_SENT        = 'sent';
    public const STATUS_FAILED      = 'failed';
    public const STATUS_MANUAL_LINK = 'manual_link'; // dibuka via wa.me deep link

    protected $fillable = [
        'report_date', 'target_phone', 'message_content', 'status', 'sent_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'sent_at'     => 'datetime',
    ];

    public function markAsSent(): static
    {
        $this->update(['status' => self::STATUS_SENT, 'sent_at' => now()]);

        return $this;
    }

    public function markAsFailed(): static
    {
        $this->update(['status' => self::STATUS_FAILED]);

        return $this;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->whereDate('report_date', $date);
    }
}

