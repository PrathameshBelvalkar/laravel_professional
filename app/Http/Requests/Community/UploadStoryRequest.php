<?php

namespace App\Http\Requests\Community;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class UploadStoryRequest extends RequestWrapper
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
      'media_type' => 'required|string|in:image,video',
      'media.*' => 'required|file|mimes:jpeg,png,jpg,mp4,mov,avi,webm,mkv,flv,3gp|max:20480',
      'tagged_users' => 'nullable|string',
      'visibility' => 'required|in:0,1,2',
      'shared_with' => 'nullable|array',
      'shared_with.*' => 'integer|exists:users,id',
      'location' => 'nullable|string'
    ];
  }
}
