<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PATCH /api/staff/maintenance-requests/{id}. Whether the step is allowed
 * from the current status is decided by the enum, in MaintenanceService.
 */
class UpdateProgressRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(MaintenanceStatus::class)->only([MaintenanceStatus::InProgress, MaintenanceStatus::Completed])],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
