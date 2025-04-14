<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;

class AddUserRequest extends RequestWrapper
{



    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'password' => 'required|min:8|max:255|regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*()\-_=+{};:,<.>]).{8,}$/',
            're_enter_password' => 'required|same:password',
            'email' =>'required|string|email|max:255|unique:users',
            'username' =>'required|string|min:3|max:255|unique:users',
            'phone_number' => ['numeric'],

        ];
    }
    public function messages()
    {
        return [
            'password.regex' => 'Enter strong password eg. John@1234.',
            'password.required' => 'The password field is required.',
           're_enter_password.required' => 'The re_enter_password field is required.',
           're_enter_password.same' => 'The re_enter_password field does not match with password.',
            'email_address.required' => 'The email field is required.',
            'username.required' => 'The username field is required.',
            'username.min' => 'The username field should be at least 3 characters.',
            'username.max' => 'The username field may not be greater than 255 characters.',
        ];
    }
}
