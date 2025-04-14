<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class UpdatePersonalInformationRequest extends RequestWrapper
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
      // 'blog_id' => 'required|integer|exists:blogs,id',
      'first_name' => 'required|string|max:60',
      'last_name' => 'required|string|max:60',
      'city' => 'required|string|max:60',
      'zip_code' => 'required|string|max:16',
      'about_me' => 'required|string|max:255',
    ];
  }
  public function messages()
  {
    return [
      'first_name.required' => 'The first name is required',
      'last_name.required' => 'The last name is required',
      'city.required' => 'The city is required',
      'zip_code.required' => 'The zip code is required',
      'about_me.required' => 'The about me is required',
      'first_name.max' => 'The first name must be 60 characters',
      'last_name.max' => 'The last name must be 60 characters',
      'city.max' => 'The city must be 60 characters',
      'zip_code.max' => 'The zip code must be 16 characters',
      'about_me.max' => 'The about me must be 255 characters',

    ];
  }
}
