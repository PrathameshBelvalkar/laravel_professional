<?php

namespace App\Http\Requests\Wallet\External;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class AddExternalWalletRequest extends RequestWrapper
{


    public function rules(): array
    {
        return [
            "key" => ["required", "max:255"],
            "type" => ["required", Rule::in(["funding_wallet_key", "trading_wallet_key"])],
            "external_wallet_masters_id" => ["required", Rule::exists("external_wallet_masters", 'id')->where("status", "1")],
        ];
    }
}
