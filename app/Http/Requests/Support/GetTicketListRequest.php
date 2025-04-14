<?php

namespace App\Http\Requests\Support;

use App\Http\Requests\RequestWrapper;

class GetTicketListRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'limit' => ['numeric'],
            'offset' => ['numeric'],
            'search' => ['nullable', 'string'],
        ];
    }
}
