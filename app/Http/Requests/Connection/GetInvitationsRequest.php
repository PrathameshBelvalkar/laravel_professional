<?php

namespace App\Http\Requests\Connection;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class GetInvitationsRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "limit" => ["numeric"],
            "type" => ["required", Rule::in(['sent', "received"])],
            "page" => ["numeric"],
            "search" => ["string"],
        ];
    }
}
