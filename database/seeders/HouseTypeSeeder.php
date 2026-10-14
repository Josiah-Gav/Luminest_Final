<?php

namespace Database\Seeders;

use App\Models\HouseType;
use Illuminate\Database\Seeder;

/**
 * The 4 house models (SYSTEM_DOCUMENTATION §5.4). Texts come from the legacy
 * catalog: short_description from models/House.php (cards), description from
 * view/house_types/*.php (detail pages).
 */
class HouseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'slug' => 'aimee',
                'name' => 'Aimee Rowhouse',
                'category' => 'One-Storey Rowhouse',
                'short_description' => 'Compact, practical, and easy to maintain.',
                'description' => 'Aimee Rowhouse is a compact and efficient home designed for practical everyday living. Its one-storey layout keeps movement simple, while the bright interior and smart floor plan make the space feel open, comfortable, and easy to maintain for first-time buyers or small families.',
                'badge' => 'Starter home',
                'bedrooms' => 1,
                'bathrooms' => 1,
                'carports' => 0,
                'price' => 1_500_000,
            ],
            [
                'slug' => 'angelique_duplex',
                'name' => 'Angelique Duplex',
                'category' => 'Two-Storey Duplex',
                'short_description' => 'A balanced layout with a quiet upstairs zone.',
                'description' => 'Angelique Duplex blends family-friendly comfort with a straightforward two-storey design. It offers a balanced layout that separates living and resting areas well, making it a practical choice for owners who want a home that feels organized, welcoming, and ready for long-term use.',
                'badge' => 'Family ready',
                'bedrooms' => 2,
                'bathrooms' => 1,
                'carports' => 1,
                'price' => 2_500_000,
            ],
            [
                'slug' => 'armina_single',
                'name' => 'Armina Single',
                'category' => 'Two-Storey Single',
                'short_description' => 'More room for growing households and flexible living.',
                'description' => 'Armina Single is built for buyers who want a little more space without losing a clean, modern layout. The two-storey design gives the home better separation between shared and private areas, while the overall footprint stays practical for families that value both comfort and efficiency.',
                'badge' => 'Room to grow',
                'bedrooms' => 3,
                'bathrooms' => 1,
                'carports' => 1,
                'price' => 3_500_000,
            ],
            [
                'slug' => 'armina_duplex',
                'name' => 'Armina Duplex',
                'category' => 'Two-Storey Duplex',
                'short_description' => 'A generous duplex plan with a modern, functional flow.',
                'description' => 'Armina Duplex offers a generous two-storey setup with room to settle in and grow. Its layout is ideal for families who want a home that feels modern and functional, with enough space for daily routines, guests, and a comfortable living environment over time.',
                'badge' => 'Spacious choice',
                'bedrooms' => 3,
                'bathrooms' => 1,
                'carports' => 1,
                'price' => 4_000_000,
            ],
        ];

        foreach ($types as $type) {
            HouseType::create($type + ['image_path' => "{$type['slug']}.jpg"]);
        }
    }
}
