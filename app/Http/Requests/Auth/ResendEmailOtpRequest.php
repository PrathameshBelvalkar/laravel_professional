<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\RequestWrapper;

class ResendEmailOtpRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "username" => ['required'],
            "email" => ['required'],
        ];
    }
}
