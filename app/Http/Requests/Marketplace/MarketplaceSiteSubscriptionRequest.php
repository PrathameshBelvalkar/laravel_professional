<?php

namespace App\Http\Requests\Marketplace;

use App\Http\Requests\RequestWrapper;

class MarketplaceSiteSubscriptionRequest extends RequestWrapper
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email_address' => 'nullable|email|max:255',
            'marketplace' => 'nullable|integer',
            'silocloud' => 'nullable|integer',
            'interest_category' => 'nullable|string',
            'notification_period' => 'nullable|date',
            'product_visited_count' => 'nullable|string',
            'notification_cnt' => 'nullable|integer',
            'user_id' => 'nullable|exists:users,id',
            'is_deleted' => 'nullable|boolean',
            'product_visited_log' => 'nullable|date',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'country' => 'nullable|string|max:50',
        ];
    }
}
