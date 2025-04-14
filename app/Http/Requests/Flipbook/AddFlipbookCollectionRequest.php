<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class AddFlipbookCollectionRequest extends RequestWrapper
{
  public function rules(): array
  {
    $user = $this->attributes->get('user');

    return [
      "collection_name" => [
        'required',
        'max:255',
        Rule::unique('flipbook_collections', 'collection_name')
          ->where(function ($query) use ($user) {
            return $query->where('user_id', $user->id)->where("deleted_at", null);
          })
      ],
      "thumbnail" => ["file", "image", "mimes:jpeg,png,jpg,webp"],
    ];
  }

  public function messages()
  {
    return [
      'collection_name.unique' => 'The collection name has already been taken.',
    ];
  }
}
