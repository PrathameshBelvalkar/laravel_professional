<?php

namespace App\Http\Requests\Wallet\External;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class RemoveExternalWalletRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "type" => ["required", Rule::in(["funding_wallet_key", "trading_wallet_key"])],
        ];
    }
}
