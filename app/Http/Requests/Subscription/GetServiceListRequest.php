<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\RequestWrapper;

class GetServiceListRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "limit" => ['numeric'],
            "page" => ['numeric'],
        ];
    }
}
