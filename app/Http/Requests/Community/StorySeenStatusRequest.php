<?php

namespace App\Http\Requests\Community;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class StorySeenStatusRequest extends RequestWrapper
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
      'story_id' => 'required|exists:community_stories,id',
      'seen_by' => 'required|boolean',
    ];
  }
}
