<?php

namespace App\Http\Requests\Connection;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class RejectInvitationsRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "connection_id" => ['required', Rule::exists('users', 'id')],
            "type" => ['required', Rule::in(['reject', 'remove'])],
        ];
    }
}
