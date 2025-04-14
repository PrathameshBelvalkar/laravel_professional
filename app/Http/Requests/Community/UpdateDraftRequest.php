<?php

namespace App\Http\Requests\Community;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class UpdateDraftRequest extends RequestWrapper
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
      'id' => 'required|exists:community_draft_stories,id',
      'media_type' => 'required|string|in:image,video',
      'media_path.*' => 'nullable|file|mimes:jpeg,png,jpg,mp4,mov,avi|max:20480',
    ];
  }
}
