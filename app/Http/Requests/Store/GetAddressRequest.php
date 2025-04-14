<?php

namespace App\Http\Requests\Store;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class GetAddressRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "limit" => ['numeric'],
            "page" => ['numeric'],
            "order_by" => [Rule::in(['zipcode', 'state', 'country', 'city', 'address_line_2', 'address_line_1', 'updated_at'])],
            "order" => [Rule::in(['asc', 'desc'])],
        ];
    }
}
