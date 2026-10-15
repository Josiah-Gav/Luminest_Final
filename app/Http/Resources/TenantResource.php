<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A tenant with the houses they own.
 *
 * @mixin User
 */
class TenantResource extends JsonResource
{
    /**
     * Contact details and active flag, plus the tenant's houses when loaded. No password or role.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'is_active' => $this->is_active,
            'houses' => HouseResource::collection($this->whenLoaded('houses')),
        ];
    }
}
