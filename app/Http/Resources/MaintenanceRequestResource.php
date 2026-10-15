<?php

namespace App\Http\Resources;

use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MaintenanceRequest
 */
class MaintenanceRequestResource extends JsonResource
{
    /**
     * The request's details, status and dates. House, tenant (id and name only) and assigned staff
     * appear when loaded; staff is null while unassigned.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'resolution_notes' => $this->resolution_notes,
            'created_at' => $this->created_at,
            'completed_at' => $this->completed_at,
            'resolved_at' => $this->resolved_at,
            'house' => $this->whenLoaded('house', fn () => $this->house ? [
                'id' => $this->house->id,
                'block' => $this->house->block,
                'lot' => $this->house->lot,
                'house_type' => $this->house->houseType->name,
            ] : null),
            'tenant' => $this->whenLoaded('tenant', fn () => [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
            ]),
            // null while nobody is assigned
            'staff' => $this->whenLoaded('staff', fn () => $this->staff ? [
                'id' => $this->staff->id,
                'name' => $this->staff->name,
                'expertise' => $this->staff->expertise,
            ] : null),
        ];
    }
}
