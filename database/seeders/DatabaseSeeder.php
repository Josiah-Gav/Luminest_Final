<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Base data only: the house catalog, the 850 units and one login per role.
     * Demo reservations and maintenance requests are opt-in:
     * `php artisan db:seed --class=DemoSeeder`.
     */
    public function run(): void
    {
        $this->call([
            HouseTypeSeeder::class,
            HouseSeeder::class,
            UserSeeder::class,
        ]);
    }
}
