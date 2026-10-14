<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['phone_number' => PhoneNumber::normalize($this->input('phone_number'))]);
    }

    /**
     * Email and phone must be unique, except against the user's own row.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users')->ignore($userId)],
            'phone_number' => ['required', 'regex:/^09\d{9}$/', Rule::unique('users')->ignore($userId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Enter a mobile number like 0917 123 4567.',
        ];
    }
}
