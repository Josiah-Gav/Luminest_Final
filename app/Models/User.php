<?php

namespace App\Models;

use App\Enums\MaintenanceCategory;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'role',
        'expertise',
        'is_active',
        'google_id',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'expertise' => MaintenanceCategory::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Reservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Houses this user owns (set when a reservation is fully paid).
     *
     * @return HasMany<House, $this>
     */
    public function houses(): HasMany
    {
        return $this->hasMany(House::class, 'owner_id');
    }

    /**
     * Requests this user filed as a tenant.
     *
     * @return HasMany<MaintenanceRequest, $this>
     */
    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'tenant_id');
    }

    /**
     * Requests assigned to this user as maintenance staff.
     *
     * @return HasMany<MaintenanceRequest, $this>
     */
    public function assignedRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'assigned_staff_id');
    }

    /** @param Builder<User> $query */
    public function scopeStaff(Builder $query): void
    {
        $query->where('role', Role::MaintenanceStaff);
    }

    /** @param Builder<User> $query */
    public function scopeTenants(Builder $query): void
    {
        $query->where('role', Role::Tenant);
    }
}
