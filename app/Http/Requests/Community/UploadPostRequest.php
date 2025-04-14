<?php

namespace App\Http\Requests\Community;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class UploadPostRequest extends RequestWrapper
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
      'visibility' => ['required', Rule::in(['0', '1', '2'])],
      'media' => ['required', 'file', 'mimes:jpeg,png,jpg,gif,mp4,mov,avi,webm,mkv,flv,3gp', 'max:20480'],
      'tagged_users' => ['nullable', 'string'],
      'caption' => ['nullable', 'string'],
      'upload_time' => ['nullable', 'date_format:Y-m-d H:i:s'],
      'location' => 'nullable|string',
    ];
  }
}
