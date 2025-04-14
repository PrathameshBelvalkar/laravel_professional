<?php

namespace App\Http\Requests\Marketplace;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class ManageCartRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            "action" => ['required', Rule::in(['manage', 'clear', 'delete', 'add', 'update', 'buy', 'buy-product'])],
            "cart_data" => ['required_if:action,manage,add'],
            'product_id' => ['required_if:action,delete,update', Rule::exists('marketplace_products', 'id')],
            'quantity' => ['required_if:action,update', 'numeric', 'gt:0'],
        ];
    }
}
