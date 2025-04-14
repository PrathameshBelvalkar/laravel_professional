<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;

class ConnectionInvitationsListRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "limit" => ["numeric"],
            "page" => ["numeric"],
            "search" => ["string"],
        ];
    }
}
