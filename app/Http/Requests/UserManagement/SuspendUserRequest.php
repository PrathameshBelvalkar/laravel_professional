<?php

namespace App\Http\Requests\UserManagement;

use App\Http\Requests\RequestWrapper;

class SuspendUserRequest extends RequestWrapper
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
      'user_id.required' => 'User ID is required.',
      'user_id.exists' => 'Invalid user ID.',
      'is_suspended.required' => 'Suspended status is required.',
      'is_suspended.in' => 'Suspended status must be either 0 or 1.',
    ];
  }
}
