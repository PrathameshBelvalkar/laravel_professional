<?php

namespace App\Http\Requests\Marketplace;


use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;


class StoreMarketplaceSellerBusinessDetailRequest extends RequestWrapper
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'person_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'company_name' => 'required|string|max:255',
            'street_address' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state_code' => 'required|string|max:255',
            'postal_code' => 'required|string|max:255',
            'country_code' => 'required|string|max:255',
        ];
    }
}
