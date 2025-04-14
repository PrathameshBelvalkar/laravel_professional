<?php

namespace App\Http\Requests\UserManagement;

use App\Http\Requests\RequestWrapper;

class AddUserRequest extends RequestWrapper
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
      'username' => 'required|string|min:3|max:255|unique:users',
      'first_name' => 'nullable|string|min:3|max:255',
      'last_name' => 'nullable|string|min:3|max:255',
      'email' => 'required|string|email|max:255|unique:users',
      'phone_number' => 'required|string|min:9|max:13',
      'password' => 'required|string|min:8|confirmed',
      'country' => 'nullable|string|exists:countries,phonecode',
      'profile_image_path' => 'nullable|image|max:10240',
    ];
  }
  public function messages()
  {
    return [
      'email.required' => 'Email is required',
      'phone_number.required' => 'Phone number is required',
      'email.email' => 'Enter valid email address',
      'phone_number.min' => 'Phone number must be minimum 9 digit',
      'phone_number.max' => 'Phone number must be maximum 13 digit',
      'password.confirmed' => 'Password confirmation field not match',
    ];
  }
}
