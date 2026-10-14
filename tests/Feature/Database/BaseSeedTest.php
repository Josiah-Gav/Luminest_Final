<?php

use App\Enums\MaintenanceCategory;
use App\Enums\Role;
use App\Models\House;
use App\Models\HouseType;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// What `php artisan migrate:fresh --seed` gives: the catalog, the units and the logins only.
beforeEach(fn () => $this->seed());

it('seeds the 4 house models with both texts', function () {
    expect(HouseType::count())->toBe(4)
        ->and(HouseType::pluck('slug')->sort()->values()->all())
        ->toBe(['aimee', 'angelique_duplex', 'armina_duplex', 'armina_single'])
        ->and(HouseType::whereNull('short_description')->orWhereNull('description')->count())->toBe(0);
});

it('seeds the 850 available units of the legacy block layout', function () {
    $lotsPerBlock = House::toBase()->selectRaw('block, count(*) as lots')->groupBy('block')->pluck('lots', 'block')->map(fn ($n) => (int) $n)->all();

    expect(House::count())->toBe(850)
        ->and(House::available()->count())->toBe(850)
        ->and(House::whereNotNull('owner_id')->count())->toBe(0)
        ->and($lotsPerBlock)->toBe([
            1 => 20, 2 => 10, 3 => 20, 4 => 30, 5 => 80, 6 => 80, 7 => 80, 8 => 20,
            9 => 50, 10 => 80, 11 => 100, 12 => 100, 13 => 100, 14 => 60, 15 => 20,
        ])
        ->and(House::where('block', 9)->first()->houseType->slug)->toBe('aimee');
});

it('rejects a second unit with the same block and lot', function () {
    $house = House::first();

    expect(fn () => House::factory()->create(['block' => $house->block, 'lot' => $house->lot]))
        ->toThrow(QueryException::class);
});

it('seeds one login per role with password "password"', function () {
    $logins = [
        'admin@luminest.test' => Role::Admin,
        'manager@luminest.test' => Role::PropertyManager,
        'staff.plumbing@luminest.test' => Role::MaintenanceStaff,
        'staff.electrical@luminest.test' => Role::MaintenanceStaff,
        'tenant@luminest.test' => Role::Tenant,
        'prospect@luminest.test' => Role::Prospect,
    ];

    foreach ($logins as $email => $role) {
        $user = User::where('email', $email)->firstOrFail();

        expect($user->role)->toBe($role)
            ->and($user->is_active)->toBeTrue()
            ->and(Hash::check('password', $user->password))->toBeTrue();
    }

    expect(User::count())->toBe(6)
        ->and(User::where('email', 'staff.plumbing@luminest.test')->first()->expertise)->toBe(MaintenanceCategory::Plumbing)
        ->and(User::where('email', 'staff.electrical@luminest.test')->first()->expertise)->toBe(MaintenanceCategory::Electrical);
});

it('leaves reservations, payments and maintenance requests empty', function () {
    expect(Reservation::count())->toBe(0)
        ->and(Payment::count())->toBe(0)
        ->and(MaintenanceRequest::count())->toBe(0);
});
