<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class ConnectionFollowListRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "limit" => ["numeric"],
            "page" => ["numeric"],
            "search" => ["string"],
            "type" => ["required", Rule::in(['followers', 'following'])],
        ];
    }
}
