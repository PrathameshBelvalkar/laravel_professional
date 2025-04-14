<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class PromoCodeValidateRequest extends RequestWrapper
{

    public function rules(): array
    {
        return [
            "promocode" => ['required', Rule::exists('promo_codes', 'promo_code')],
        ];
    }
    public function messages()
    {
        return [
            'promocode.exists' => 'The selected promo code is invalid.',
        ];
    }
}
