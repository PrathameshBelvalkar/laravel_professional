<?php

namespace App\Http\Requests\Wallet;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class TokenRequestLog extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "limit" => ['numeric', "gt:0"],
            "page" => ['numeric', "gt:0"],
            "search_keyword" => ['nullable'],
            "type" => ['required', Rule::in(['cash', 'token', 'all'])],
        ];
    }
}
