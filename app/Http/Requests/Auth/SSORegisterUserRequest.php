<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class SSORegisterUserRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:255', 'unique:users'],
            'first_name' => ['string', 'min:3', 'max:255'],
            'last_name' => ['string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users', 'regex:/^\w.+@[a-zA-Z_]+?\.[a-zA-Z]{2,3}$/'],
            'password' => ['required_if:register_type,platform', 'min:8', 'max:255', 'confirmed', 'regex:/^(?=.*?[A-Z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/'],
            'register_type' => ['required', Rule::in(['platform', 'google', 'facebook', 'apple'])],
        ];
    }
    public function messages()
    {
        return [
            'username.required' => 'The username field is required.',
            'username.min' => 'The username must be at least :min characters.',
            'username.max' => 'The username must not exceed :max characters.',
            'username.unique' => 'The username is already taken.',
            'first_name.required' => 'The first name field is required.',
            'first_name.min' => 'The first name be at least :min characters.',
            'first_name.max' => 'The first name must not exceed :max characters.',
            'last_name.required' => 'The last name field is required.',
            'last_name.min' => 'The last name be at least :min characters.',
            'last_name.max' => 'The last name must not exceed :max characters.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Invalid email format.',
            'email.max' => 'The email must not exceed :max characters.',
            'email.unique' => 'The email is already registered.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least :min characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'register_type.required' => 'The registration type field is required.',
            'register_type.in' => 'Invalid registration type.',
            'password.regex' => 'Enter strong password.',
        ];
    }
}
