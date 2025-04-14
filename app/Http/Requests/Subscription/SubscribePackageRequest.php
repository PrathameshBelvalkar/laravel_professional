<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class SubscribePackageRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "package_id" => ['required', 'numeric'],
            "validity" => ['required', Rule::in([1, 3, 12])],
            'mode' => [Rule::in(['1', '2', '3', '4'])],
            'package_start_date' => 'date|date_format:Y-m-d|after_or_equal:today',
        ];
    }
    public function messages()
    {
        return [
            'validity.in' => 'Invalid validity type.',
        ];
    }
}
