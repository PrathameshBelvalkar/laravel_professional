<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class AddFlipbookUploadRequest extends RequestWrapper
{
  public function rules(): array
  {
    $user = $this->attributes->get('user');
    return [
      'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:204800'],
      "is_publish" => ["nullable", Rule::in(["1"])],
      "visibility" => ["required_if:is_publish,1", Rule::in(["1", '2'])],
      "thumbnail" => ["file", "image", "mimes:jpeg,png,jpg,webp", 'max:20480'],
      'collection_id' => [
        'nullable',
        'integer',
        Rule::exists("flipbook_collections", "id")->where(function ($query) use ($user) {
          return $query->where('user_id', $user->id)->where("deleted_at", null);
        })
      ]
    ];
  }
}
