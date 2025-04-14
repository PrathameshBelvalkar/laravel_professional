<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;

class ChangePasswordRequest extends RequestWrapper
{



    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'old_password' => 'required',
            'new_password' => 'required|min:8|max:255|regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*()\-_=+{};:,<.>]).{8,}$/',
            'confirm_password' => 'required|same:new_password',
        ];
    }
    public function messages()
    {
        return [
            'new_password.regex' => 'Enter strong password eg. John@1234.',
        ];
    }
}
