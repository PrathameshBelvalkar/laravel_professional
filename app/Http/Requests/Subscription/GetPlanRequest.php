<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class GetPlanRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "plan_id" => ['required', "numeric", Rule::exists('service_plans', 'id')],
            "service_id" => ['required', "numeric", Rule::exists('services', 'id')],
        ];
    }
}
