<?php

namespace App\Http\Requests\Flipbook;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetFlipbookListRequest extends RequestWrapper
{
    public function rules(): array
    {
        $user = $this->attributes->get('user');
        return [
            "limit" => ['numeric', 'gte:1'],
            "page" => ['numeric', 'gte:1'],
            // "search_keyword" => ['string'],
            "order_by" => [Rule::in(["collection_name", 'created_at', 'updated_at'])],
            "order" => [Rule::in(['asc', 'desc'])],
            'collection_id' => [
                'nullable',
                'integer',
                Rule::exists("flipbook_collections", "id")->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id)->where("deleted_at", null);
                })
            ],
            'flipbook_id' => [
                'nullable',
                'integer',
                Rule::exists("flipbooks", "id")->where(function ($query) use ($user) {
                    return $query->where('user_id', $user->id)->where("deleted_at", null);
                })
            ]
        ];
    }
}
