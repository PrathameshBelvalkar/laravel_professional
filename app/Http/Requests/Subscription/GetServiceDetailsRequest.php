<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class GetServiceDetailsRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "service_id" => ['required', "numeric", Rule::exists('services', 'id')],
        ];
    }
}
