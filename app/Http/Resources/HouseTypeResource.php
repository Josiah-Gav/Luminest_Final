<?php

namespace App\Http\Resources;

use App\Models\HouseType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HouseType
 */
class HouseTypeResource extends JsonResource
{
    /**
     * Model details and price; the list adds an available-unit count, the detail page adds the units (id, block, lot).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'badge' => $this->badge,
            'image_path' => $this->image_path,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'carports' => $this->carports,
            'price' => $this->price,
            // List page: withCount('houses as available_units_count'). Detail page: the units themselves.
            'available_units_count' => $this->whenCounted('available_units_count'),
            'available_units' => $this->whenLoaded('houses', fn () => $this->houses->map(fn ($house) => [
                'id' => $house->id,
                'block' => $house->block,
                'lot' => $house->lot,
            ])),
        ];
    }
}
