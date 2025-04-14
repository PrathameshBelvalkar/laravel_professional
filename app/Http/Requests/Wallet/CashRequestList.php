<?php

namespace App\Http\Requests\Wallet;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class CashRequestList extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "limit" => ['numeric', "gte:1"],
            "page" => ['numeric', "gte:1"],
            "search_keyword" => ['nullable'],
            "from_date_time" => ["nullable", 'date_format:Y-m-d H:i:s'],
            "end_date_time" => ["nullable", 'date_format:Y-m-d H:i:s'],
            "user_id" => ["required", Rule::exists("users", "id")->whereNull("deleted_at")],
        ];
    }
}
