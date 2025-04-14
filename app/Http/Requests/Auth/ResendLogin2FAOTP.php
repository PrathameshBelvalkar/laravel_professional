<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\RequestWrapper;

class ResendLogin2FAOTP extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
        ];
    }
}
