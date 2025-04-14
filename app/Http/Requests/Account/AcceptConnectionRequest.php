<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class AcceptConnectionRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "connection_id" => ['required', Rule::exists('users', 'id')],
        ];
    }
}
