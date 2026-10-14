<?php

namespace App\Models;

use App\Enums\HouseStatus;
use Database\Factories\HouseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class House extends Model
{
    /** @use HasFactory<HouseFactory> */
    use HasFactory;

    protected $fillable = [
        'house_type_id',
        'block',
        'lot',
        'status',
        'owner_id',
        'purchased_at',
    ];

    protected function casts(): array
    {
        return [
            'block' => 'integer',
            'lot' => 'integer',
            'status' => HouseStatus::class,
            'purchased_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<HouseType, $this> */
    public function houseType(): BelongsTo
    {
        return $this->belongsTo(HouseType::class);
    }

    /**
     * Set only when a reservation is fully paid (R5); a reserved unit has no owner yet.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<Reservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** @return HasMany<MaintenanceRequest, $this> */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    /** @param Builder<House> $query */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('status', HouseStatus::Available);
    }
}
