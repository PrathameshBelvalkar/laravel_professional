<?php

namespace App\Http\Requests\Connection;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class DeleteConnectionRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "connection_id" => ['required_if:type,single', Rule::exists('users', 'id')],
            "type" => ["required", Rule::in(['single', 'all'])],
        ];
    }
}
