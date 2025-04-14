<?php

namespace App\Http\Requests\Wallet;

use App\Http\Requests\RequestWrapper;

class PaypalSettingRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "paypal_production_client_id" => ["required", "max:255"],
            "paypal_sandbox_client_id" => ["required", "max:255"],
        ];
    }
}
