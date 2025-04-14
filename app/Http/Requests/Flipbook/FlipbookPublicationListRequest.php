<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class FlipbookPublicationListRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'limit' => ['numeric', 'gte:1'],
            'page' => ['numeric', 'gte:1'],
            // 'search_keyword' => ['string'],
            'order_by' => ['string', Rule::in(['currency', "status", "price", "collection_id", "description", "visibility", "title"])],
            'order' => ['string', Rule::in(["asc", "desc"])],
        ];
    }
}
