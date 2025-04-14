<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class ExternalServiceSubscriptionRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "service_id" => ['required', Rule::exists("services", "id")->where("is_external_service", "1")],
            "price" => ['required', "numeric", "gt:0"],
            "validity" => ['required', "numeric", "gt:0"]
        ];
    }
}
