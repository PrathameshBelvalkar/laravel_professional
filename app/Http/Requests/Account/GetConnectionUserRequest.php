<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class GetConnectionUserRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "limit" => ["numeric"],
            "page" => ["numeric"],
            "search" => ["string"],
            "user_id" => ['numeric', Rule::exists('users', 'id')],
            "type" => [Rule::in(['connected', 'not_connected', 'followed', 'not_followed', 'followers', 'not_followers'])],
        ];
    }
}
