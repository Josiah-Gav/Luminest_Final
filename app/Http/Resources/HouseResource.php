<?php

namespace App\Http\Resources;

use App\Models\House;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A unit as "Aimee Rowhouse, Block 9 Lot 1".
 *
 * @mixin House
 */
class HouseResource extends JsonResource
{
    /**
     * Just enough to name a unit: id, block, lot and the house model's name.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'block' => $this->block,
            'lot' => $this->lot,
            'house_type' => $this->houseType->name,
        ];
    }
}
