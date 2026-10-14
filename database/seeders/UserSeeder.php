<?php

namespace Database\Seeders;

use App\Enums\MaintenanceCategory;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One login per role (two for staff), all with password "password".
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['Luminest Admin', 'admin@luminest.test', Role::Admin, null],
            ['Paula Manager', 'manager@luminest.test', Role::PropertyManager, null],
            ['Pedro Plumbing', 'staff.plumbing@luminest.test', Role::MaintenanceStaff, MaintenanceCategory::Plumbing],
            ['Elena Electrical', 'staff.electrical@luminest.test', Role::MaintenanceStaff, MaintenanceCategory::Electrical],
            ['Tina Tenant', 'tenant@luminest.test', Role::Tenant, null],
            ['Paolo Prospect', 'prospect@luminest.test', Role::Prospect, null],
        ];

        foreach ($users as $i => [$name, $email, $role, $expertise]) {
            User::create([
                'name' => $name,
                'email' => $email,
                'phone_number' => sprintf('0917000000%d', $i + 1),
                'password' => 'password', // hashed by the model cast
                'role' => $role,
                'expertise' => $expertise,
            ]);
        }
    }
}
