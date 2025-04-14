<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class UpdateAccountPasswordRequest extends RequestWrapper
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
  * @return array<string, mixed>
  */
  public function rules(): array
  {
    return [
      'password' => 'required|string|min:8|confirmed',
    ];
  }
  public function messages()
  {
    return [
      'password.required' => 'The password field is required',
      'password.min' => 'The password must be at least :min characters',
      'password.regex' => 'Enter strong password',
      'password.confirmed' => 'The password confirmation does not match',
    ];
  }
}
