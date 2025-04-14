<?php

namespace App\Http\Requests\Wallet;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class ApproveCashRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "request_ids" => ["required"],
            "user_id" => ["required", Rule::exists("users", "id")->whereNull("deleted_at")],
            "paypal_response" => ["required"],
            "comment" => ["nullable", "max:500"]
        ];
    }
}
