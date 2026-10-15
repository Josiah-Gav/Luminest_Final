<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A staff member for the assign dropdown, with request counts on the Staff page.
 *
 * @mixin User
 */
class StaffResource extends JsonResource
{
    /**
     * Id, name and expertise only (no email or phone), plus the request counts when they were queried.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'expertise' => $this->expertise,
            'active_requests_count' => $this->whenCounted('active_requests_count'),
            'completed_requests_count' => $this->whenCounted('completed_requests_count'),
        ];
    }
}
