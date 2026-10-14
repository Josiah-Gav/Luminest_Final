<?php

use App\Enums\HouseStatus;
use App\Models\House;
use App\Models\HouseType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the house types for anyone', function () {
    HouseType::factory()->count(2)->create();

    $this->getJson('/api/house-types')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'slug', 'name', 'price', 'bedrooms', 'bathrooms', 'available_units_count']]]);
});

it('shows a house type with only its available units', function () {
    $type = HouseType::factory()->create(['slug' => 'aimee']);
    $free = House::factory()->for($type)->create(['block' => 9, 'lot' => 1]);
    House::factory()->for($type)->create(['block' => 9, 'lot' => 2, 'status' => HouseStatus::Sold]);

    $this->getJson('/api/house-types/aimee')
        ->assertOk()
        ->assertJsonPath('data.slug', 'aimee')
        ->assertJsonPath('data.available_units', [['id' => $free->id, 'block' => 9, 'lot' => 1]]);
});
