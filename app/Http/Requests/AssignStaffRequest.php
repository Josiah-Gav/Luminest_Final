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
     * staff_id must be sent: either null (unassign) or the id of an active maintenance staff member.
     *
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
     * Friendlier error text when the chosen user is not active maintenance staff.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['staff_id.exists' => 'Choose an active maintenance staff member.'];
    }
}
