<?php

namespace App\Http\Requests\Wallet;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class MakeTokenRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "type" => ['required', Rule::in(['1', '2',])],
            "to_user_id" => ['required_if:type,1', Rule::exists('users', 'id')],
            "txn_tokens" => ['required', 'numeric', 'gt:99'],

        ];
    }
}
