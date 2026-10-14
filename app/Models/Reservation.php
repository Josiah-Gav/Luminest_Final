<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'house_id',
        'status',
        'total_price',
        'cancelled_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'total_price' => 'integer',
            'cancelled_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<House, $this> */
    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    /**
     * Oldest first, so the payment list reads like a statement.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('id');
    }

    /**
     * Sum of payments. Uses withSum('payments', 'amount') when it was loaded,
     * so lists don't run one query per reservation.
     *
     * @return Attribute<int, never>
     */
    protected function totalPaid(): Attribute
    {
        return Attribute::get(
            fn () => (int) ($this->payments_sum_amount ?? $this->payments()->sum('amount'))
        );
    }

    /**
     * Calculated, never stored, so it can't drift (plan §1.2 R3).
     *
     * @return Attribute<int, never>
     */
    protected function balance(): Attribute
    {
        return Attribute::get(fn () => $this->total_price - $this->total_paid);
    }

    /**
     * The reservation that blocks its owner from reserving again (R2).
     *
     * @param  Builder<Reservation>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', ReservationStatus::Reserved);
    }
}
