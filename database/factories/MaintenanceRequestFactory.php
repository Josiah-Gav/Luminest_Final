<?php

namespace Database\Factories;

use App\Enums\MaintenanceCategory;
use App\Enums\MaintenanceStatus;
use App\Enums\Priority;
use App\Enums\Role;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceRequest>
 */
class MaintenanceRequestFactory extends Factory
{
    /**
     * A new, unassigned request from a tenant.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => User::factory()->state(['role' => Role::Tenant]),
            'house_id' => null,
            'assigned_staff_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(MaintenanceCategory::cases()),
            'priority' => Priority::Medium,
            'status' => MaintenanceStatus::Pending,
        ];
    }
}
