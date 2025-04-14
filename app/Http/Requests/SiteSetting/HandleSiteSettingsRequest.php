<?php

namespace App\Http\Requests\SiteSetting;

use App\Http\Requests\RequestWrapper;

class HandleSiteSettingsRequest extends RequestWrapper
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

  public function rules()

  {
    return [
      'sidebar_logo' => 'nullable|image|mimes:jpg,png,jpeg,gif',
      'favicon_logo' => 'nullable|image|mimes:jpg,png,jpeg,gif',
      'public_page_logo' => 'nullable|image|mimes:jpg,png,jpeg,gif',
      'client_home_page_logo' => 'nullable|image|mimes:jpg,png,jpeg,gif',
      'auth_logo' => 'nullable|image|mimes:jpg,png,jpeg,gif',
      'blog_header_image' => 'nullable|image|mimes:jpg,png,jpeg',
      'meta_image' => 'nullable|image|mimes:jpg,png,jpeg',
      'meta_title' => 'nullable|string',
      'meta_description' => 'nullable|string',
      'meta_url' => 'nullable|string',
    ];
  }

  public function messages()
  {
    return [
      'sidebar_logo.image' => 'The :attribute must be an image.',
      'sidebar_logo.mimes' => 'The :attribute must be a file of type: jpg, png, jpeg, gif.',
      'favicon_logo.image' => 'The :attribute must be an image.',
      'favicon_logo.mimes' => 'The :attribute must be a file of type: jpg, png, jpeg, gif.',
      'public_page_logo.image' => 'The :attribute must be an image.',
      'public_page_logo.mimes' => 'The :attribute must be a file of type: jpg, png, jpeg, gif.',
      'client_home_page_logo.image' => 'The :attribute must be an image.',
      'client_home_page_logo.mimes' => 'The :attribute must be a file of type: jpg, png, jpeg, gif.',
      'auth_logo.image' => 'The :attribute must be an image.',
      'auth_logo.mimes' => 'The :attribute must be a file of type: jpg, png, jpeg, gif.',
      'blog_header_image.image' => 'The :attribute must be an image.',
      'blog_header_image.mimes' => 'The :attribute must be a file of type: jpg, png, jpeg, gif.',
    ];
  }
}
