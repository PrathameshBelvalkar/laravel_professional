<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\RequestWrapper;

class VerifyLogin2FAOtpRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'otp' => ['required', 'numeric'],
        ];
    }
}
