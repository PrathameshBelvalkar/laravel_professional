<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class GetPublicationListRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "limit" => ['numeric', 'gte:1'],
            "page" => ['numeric', 'gte:1'],
            // "search_keyword" => ['string'],
            "order" => ["string", Rule::in(['asc', "desc"])],
            "order_by" => ["string", Rule::in(['currency', "status", "price", "collection_id", "description", "visibility", "visibility", "title"])],
            "status" => ["numeric", Rule::in(['0', "1", "2"])],
        ];
    }
}
