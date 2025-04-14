<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;
use Request;

class AddConnectionRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "user_id" => ['required', Rule::exists('users', 'id')],
        ];
    }
}
