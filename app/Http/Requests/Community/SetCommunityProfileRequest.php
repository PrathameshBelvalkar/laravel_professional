<?php

namespace App\Http\Requests\Community;

use App\Http\Requests\RequestWrapper;

class SetCommunityProfileRequest extends RequestWrapper
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function rules(): array
  {
    return [
      'profile_image_path' => 'nullable|image|mimes:jpeg,jpg,png,bmp,gif,svg',
      'about_me' => ['nullable', 'max:255'],
      'gender' => ['nullable', 'in:male,female,other'],
    ];
  }
}
