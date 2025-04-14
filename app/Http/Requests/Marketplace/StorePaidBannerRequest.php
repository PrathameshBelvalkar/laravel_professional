<?php

namespace App\Http\Requests\Marketplace;

use App\Http\Requests\RequestWrapper;

class StorePaidBannerRequest extends RequestWrapper
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_store_id' => 'nullable|integer',
            'banner_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'title' => 'required|string|max:500',
            'subtitle' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:1,2,3',
            'type' => 'required|in:1,2',
            'price' => 'nullable|numeric',
            'payment_id' => 'nullable|string',
            'ad_show_date' => 'nullable|date',
        ];
    }
}
