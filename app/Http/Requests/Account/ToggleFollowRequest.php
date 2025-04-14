<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class ToggleFollowRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "user_id" => ['required_if:type,single', Rule::exists('users', 'id')],
            "type" => ["required", Rule::in(['single', 'all'])],
            "status" => ["required", Rule::in(['1', '2'])],
        ];
    }
}
