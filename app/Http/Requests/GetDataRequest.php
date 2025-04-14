<?php

namespace App\Http\Requests;

class GetDataRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "limit" => ['numeric', "gt:0"],
            "page" => ['numeric', "gt:0"],
            "search_keyword" => ['nullable'],
        ];
    }
}
