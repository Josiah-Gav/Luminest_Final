<?php

namespace App\Models;

use App\Enums\MaintenanceCategory;
use App\Enums\MaintenanceStatus;
use App\Enums\Priority;
use Database\Factories\MaintenanceRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    /** @use HasFactory<MaintenanceRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'house_id',
        'assigned_staff_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'resolution_notes',
        'completed_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => MaintenanceCategory::class,
            'priority' => Priority::class,
            'status' => MaintenanceStatus::class,
            'completed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    /** @return BelongsTo<User, $this> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_staff_id');
    }

    /** @return BelongsTo<House, $this> */
    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    /**
     * The PM queue: everything the tenant hasn't confirmed as resolved yet.
     *
     * @param  Builder<MaintenanceRequest>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', MaintenanceStatus::Resolved);
    }

    /**
     * History: work that staff has finished.
     *
     * @param  Builder<MaintenanceRequest>  $query
     */
    public function scopeClosed(Builder $query): void
    {
        $query->whereIn('status', [MaintenanceStatus::Completed, MaintenanceStatus::Resolved]);
    }
}
