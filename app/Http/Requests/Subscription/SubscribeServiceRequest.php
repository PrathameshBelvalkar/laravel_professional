<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class SubscribeServiceRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "service_id" => ['required', "numeric", Rule::exists('services', 'id')],
            "plan_id" => ['required', "numeric", Rule::exists('service_plans', 'id')->where('service_id', request()->service_id)],
            "validity" => ['required', Rule::in([1, 3, 12])],
            'service_start_date' => 'date|date_format:Y-m-d|after_or_equal:today',
        ];
    }
}
