<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceCategory;
use App\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/tenant/maintenance-requests.
 */
class StoreMaintenanceRequest extends FormRequest
{
    /**
     * Title, description, category and priority are required, and the house must be one this tenant owns.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'category' => ['required', Rule::enum(MaintenanceCategory::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
            // Only a house this tenant owns (houses.owner_id).
            'house_id' => ['required', 'integer', Rule::exists('houses', 'id')->where('owner_id', $this->user()->id)],
        ];
    }

    /**
     * Friendlier error text when the house is not the tenant's own.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['house_id.exists' => 'Choose one of your own houses.'];
    }
}
