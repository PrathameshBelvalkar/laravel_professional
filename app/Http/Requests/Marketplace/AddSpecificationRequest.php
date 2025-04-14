<?php

namespace App\Http\Requests\Marketplace;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class AddSpecificationRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "specifications" => ['required'],
            "product_id" => ['required', Rule::exists("marketplace_products", "id")]
        ];
    }
}
