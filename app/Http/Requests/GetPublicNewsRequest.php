<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class GetPublicNewsRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "limit" => ['numeric', 'gte:1'],
            "category" => ['nullable', Rule::in(["politics", "sports", "entertainment"])],
            "last_days" => ['numeric', 'gte:1'],
            "page" => ['numeric', 'gte:1'],
            "search_keyword" => ['string'],
            "order_by" => [Rule::in(['title', 'news_text', 'api_id', 'summary', "url", "author", "publish_date", "video", "image", "source_country", "language", "catgory", "updated_at", "created_at", "read_count"])],
            "order" => [Rule::in(['asc', 'desc'])],
            'api_id' => [
                'nullable',
                'integer',
                Rule::exists("public_news", "api_id")->where(function ($query) {
                    return $query->where("deleted_at", null);
                })
            ],
        ];
    }
}
