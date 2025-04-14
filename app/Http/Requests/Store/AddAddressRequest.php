<?php

namespace App\Http\Requests\Store;

use App\Http\Requests\RequestWrapper;

class AddAddressRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "address_line_1" => ['required', 'max:500'],
            "city" => ['required', 'max:500'],
            "state" => ['required', 'max:500'],
            "country" => ['required', 'max:500'],
            "country_code" => ['required', "numeric"],
            "phone_number" => ['required', "numeric"],
            "location" => ['max:1000'],
            "zipcode" => ['required', 'numeric'],
        ];
    }
}
