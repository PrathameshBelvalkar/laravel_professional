<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\RequestWrapper;

class ResetForgotPasswordRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "username" => ['required'],
            "password" => ['required', 'min:8', 'max:255', 'confirmed', 'regex:/^(?=.*?[A-Z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/'],
            "otp" => ['required']
        ];
    }
    public function messages()
    {
        return ['password.regex' => 'Enter strong password.',];
    }
}
