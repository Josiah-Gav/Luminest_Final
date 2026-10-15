<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceCategory;
use App\Enums\Role;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * POST /api/admin/users. Whether expertise is needed depends on the role, so UserService checks it.
 */
class StoreUserRequest extends FormRequest
{
    /** Normalizes the phone number first, so the unique check compares like with like. */
    protected function prepareForValidation(): void
    {
        $this->merge(['phone_number' => PhoneNumber::normalize($this->input('phone_number'))]);
    }

    /**
     * Name, unique email, password and role are required; phone, expertise and is_active are optional.
     * Whether expertise is required for the chosen role is checked in UserService.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'phone_number' => ['nullable', 'regex:/^09\d{9}$/', 'unique:users,phone_number'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::enum(Role::class)],
            'expertise' => ['nullable', Rule::enum(MaintenanceCategory::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Friendlier error text for a badly formatted phone number.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['phone_number.regex' => 'Enter a mobile number like 0917 123 4567.'];
    }
}
