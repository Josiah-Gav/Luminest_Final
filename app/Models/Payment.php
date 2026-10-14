<?php

namespace App\Models;

use App\Enums\PaymentType;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'type',
        'amount',
        'balance_after',
        'method',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'amount' => 'integer',
            'balance_after' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * The receipt number is built from the row id (LUM-2026-000123), so it is
     * set right after the insert, inside the caller's transaction.
     */
    protected static function booted(): void
    {
        static::created(function (Payment $payment) {
            $payment->receipt_number = sprintf('LUM-%d-%06d', $payment->paid_at->year, $payment->id);
            $payment->saveQuietly();
        });
    }

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
