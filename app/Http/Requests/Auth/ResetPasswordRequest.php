<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\RequestWrapper;

class ResetPasswordRequest extends RequestWrapper
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'verify_token' => ['required', 'string'],
            'verify_code' => ['required', 'string'],
            'password' => ['required', 'min:8', 'max:255', 'confirmed', 'regex:/^(?=.*?[A-Z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/'],
        ];
    }
    public function messages()
    {
        return [
            'verify_token.required' => 'The verify token field is required.',
            'verify_code.required' => 'The verify code field is required.',
            'password.required' => 'The password field is required.',
            'password.regex' => 'Enter strong password.',
            'password.confirmed' => 'The password confirmation does not match with password.',
        ];
    }
}
