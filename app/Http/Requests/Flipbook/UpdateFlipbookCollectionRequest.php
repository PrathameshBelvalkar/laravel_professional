<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFlipbookCollectionRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "collection_name" => ['max:255'],
            "thumbnail" => ["file", "image", "mimes:jpeg,png,jpg,webp"],
        ];
    }
}
