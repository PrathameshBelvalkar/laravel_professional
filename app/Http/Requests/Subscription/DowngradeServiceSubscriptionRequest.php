<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\RequestWrapper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DowngradeServiceSubscriptionRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "service_id" => ['required', Rule::exists('services', 'id')],
            "plan_id" => ['required', Rule::exists('service_plans', 'id')],
            "validity" => ['required', Rule::in([1, 3, 12])],
        ];
    }
}
