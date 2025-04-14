<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class UpdateContactInformationRequest extends RequestWrapper
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
      'email' => 'required|string|email',
      'phone_number' => 'required|string|min:9|max:13',
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
    ];
  }
}
