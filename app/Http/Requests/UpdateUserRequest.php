<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceCategory;
use App\Enums\Role;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * PUT /api/admin/users/{user}. Every field is optional ("sometimes"), so the
 * activate/deactivate button can send just { is_active }.
 */
class UpdateUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('phone_number')) {
            $this->merge(['phone_number' => PhoneNumber::normalize($this->input('phone_number'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('user')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:150', Rule::unique('users')->ignore($id)],
            'phone_number' => ['sometimes', 'nullable', 'regex:/^09\d{9}$/', Rule::unique('users')->ignore($id)],
            'password' => ['sometimes', 'nullable', Password::defaults()], // blank keeps the current one
            'role' => ['sometimes', 'required', Rule::enum(Role::class)],
            'expertise' => ['sometimes', 'nullable', Rule::enum(MaintenanceCategory::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['phone_number.regex' => 'Enter a mobile number like 0917 123 4567.'];
    }
}
