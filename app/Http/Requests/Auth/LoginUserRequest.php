<?php

namespace App\Http\Requests\A
use App\Http\Requests\RequestWrapper;

class LoginUserRequest extends RequestWrapper
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
    public function messages()
    {
        return [
            'username.required' => 'The username field is required.',
            'password.required' => 'The password field is required.',
        ];
    }
}
