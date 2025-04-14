<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\RequestWrapper;

class VerifySmsOtpRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "otp" => ['required', 'numeric'],
            "username" => ['required'],
        ];
    }
}
