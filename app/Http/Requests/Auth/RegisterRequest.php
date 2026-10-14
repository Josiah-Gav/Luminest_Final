<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Normalize first, so "unique" compares 09XXXXXXXXX with 09XXXXXXXXX.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['phone_number' => PhoneNumber::normalize($this->input('phone_number'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'phone_number' => ['required', 'regex:/^09\d{9}$/', 'unique:users,phone_number'],
            'password' => ['required', 'confirmed', Password::defaults()],
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
