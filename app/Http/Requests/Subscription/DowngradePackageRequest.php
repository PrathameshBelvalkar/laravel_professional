<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class DowngradePackageRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "package_id" => ['required', Rule::exists('packages', 'id')],
            "validity" => ['required', Rule::in([1, 3, 12])],
        ];
    }
}
