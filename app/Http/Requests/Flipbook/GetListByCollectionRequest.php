<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class GetListByCollectionRequest extends RequestWrapper
{
    public function rules(): array
    {
        $user = $this->attributes->get('user');
        return [
            "limit" => ['numeric', 'gte:1'],
            "page" => ['numeric', 'gte:1'],
            // "search_keyword" => ['string'],
            "type" => ['required', Rule::in(['single', "all", "default"])],
            "collection_id" => [
                'required_if:type,single',
                Rule::exists("flipbook_collections", "id")->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id)->where("deleted_at", null);
                })
            ]
        ];
    }
}
