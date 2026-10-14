<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PATCH /api/manager/maintenance-requests/{id}/assignment. Null unassigns.
 * The expertise match depends on the request, so MaintenanceService::assign() checks it.
 */
class AssignStaffRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'staff_id' => [
                'present',
                'nullable',
                'integer',
                // An active maintenance staff member, whatever the dropdown offered.
                Rule::exists('users', 'id')->where('role', Role::MaintenanceStaff->value)->where('is_active', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['staff_id.exists' => 'Choose an active maintenance staff member.'];
    }
}
