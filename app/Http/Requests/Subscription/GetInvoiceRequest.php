<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class GetInvoiceRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "invoice_id" => ['required', 'numeric', Rule::exists('user_package_subscription_logs', 'id')],
        ];
    }
}
