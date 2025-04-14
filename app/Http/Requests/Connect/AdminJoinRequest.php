<?php

namespace App\Http\Requests\Connect;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class AdminJoinRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "is_admin_joined" => ['required', Rule::in(['0', '1'])],
            "admin_id" => ['required', "max:255"],
        ];
    }
}
