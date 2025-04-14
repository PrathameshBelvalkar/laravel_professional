<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\RequestWrapper;

class ResendSMSOtpRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "username" => ['required'],
            "phone_number" => ['required'],
            "country" => ['required'],
        ];
    }
}
