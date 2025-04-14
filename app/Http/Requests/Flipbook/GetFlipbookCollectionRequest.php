<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class GetFlipbookCollectionRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "limit" => ['numeric', 'gte:1'],
            "page" => ['numeric', 'gte:1'],
            "order_by" => [Rule::in(['collection_name', 'thumbnail', 'slug', 'updated_at', "created_at"])],
            "order" => [Rule::in(['asc', 'desc'])],
            // "search_keyword" => ["string"],
        ];
    }
}
