<?php

namespace Database\Seeders;

use App\Enums\HouseStatus;
use App\Models\House;
use App\Models\HouseType;
use Illuminate\Database\Seeder;

/**
 * The 850 units of the legacy block layout (SYSTEM_DOCUMENTATION §5.3), all available.
 */
class HouseSeeder extends Seeder
{
    /** block => [lots, house type slug] */
    private const BLOCKS = [
        1 => [20, 'angelique_duplex'],
        2 => [10, 'armina_single'],
        3 => [20, 'armina_single'],
        4 => [30, 'armina_duplex'],
        5 => [80, 'armina_single'],
        6 => [80, 'armina_single'],
        7 => [80, 'armina_single'],
        8 => [20, 'angelique_duplex'],
        9 => [50, 'aimee'],
        10 => [80, 'aimee'],
        11 => [100, 'aimee'],
        12 => [100, 'aimee'],
        13 => [100, 'aimee'],
        14 => [60, 'aimee'],
        15 => [20, 'aimee'],
    ];

    public function run(): void
    {
        $typeIds = HouseType::pluck('id', 'slug');
        $now = now();
        $rows = [];

        foreach (self::BLOCKS as $block => [$lots, $slug]) {
            foreach (range(1, $lots) as $lot) {
                $rows[] = [
                    'house_type_id' => $typeIds[$slug],
                    'block' => $block,
                    'lot' => $lot,
                    'status' => HouseStatus::Available->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // One bulk insert per chunk instead of 850 single inserts.
        foreach (array_chunk($rows, 200) as $chunk) {
            House::insert($chunk);
        }
    }
}
