<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The JSON shape of a user. Fields are listed one by one, so password,
 * remember_token and google_id can never leak.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Id, name, contact details, role, expertise, active flag, avatar and created date. Never the password or Google id.
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
            'role' => $this->role,
            'expertise' => $this->expertise,
            'is_active' => $this->is_active,
            'avatar_url' => $this->avatar_url,
            'created_at' => $this->created_at,
        ];
    }
}
