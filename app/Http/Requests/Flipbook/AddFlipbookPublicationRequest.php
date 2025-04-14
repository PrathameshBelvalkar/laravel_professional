<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class AddFlipbookPublicationRequest extends RequestWrapper
{
  public function rules(): array
  {
    $user = $this->attributes->get('user');

    return [
      "flipbook_id" => [
        'required',
        Rule::exists('flipbooks', 'id')
          ->where(function ($query) use ($user) {
            return $query->where('user_id', $user->id)->where("deleted_at", null);
          })
      ],
      'title' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string', 'max:500'],
      'visibility' => ['required', Rule::in(['1', '2'])],
    ];
  }
}
