<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\RequestWrapper;

class VerifyEmailOtpRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "email" => ['required'],
            "otp" => ['required', 'numeric'],
            "username" => ['required'],
        ];
    }
}
