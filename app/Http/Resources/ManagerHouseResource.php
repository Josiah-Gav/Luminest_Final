<?php

namespace App\Http\Resources;

use App\Models\House;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A unit as the property manager sees it: model, status and owner (read-only).
 *
 * @mixin House
 */
class ManagerHouseResource extends JsonResource
{
    /**
     * Block, lot, status, model name and purchase date, plus the owner's id and name (null until sold).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'block' => $this->block,
            'lot' => $this->lot,
            'status' => $this->status,
            'house_type' => $this->houseType->name,
            // null until the unit is sold (R5)
            'owner' => $this->whenLoaded('owner', fn () => $this->owner ? [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ] : null),
            'purchased_at' => $this->purchased_at,
        ];
    }
}
