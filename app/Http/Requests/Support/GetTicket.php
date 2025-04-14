<?php

namespace App\Http\Requests\Support;

use App\Http\Requests\RequestWrapper;

class GetTicket extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'ticket_id' => ['required'],
        ];
    }
}
