<?php

namespace App\Http\Requests\Community;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class UpdatePostRequest extends RequestWrapper
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
      'post_id' => ['required', 'integer', 'exists:community_posts,id'],
      'caption' => ['nullable', 'string'],
      'tagged_users' => ['nullable', 'string'],
      'location' => ['nullable', 'string']
    ];
  }
}
