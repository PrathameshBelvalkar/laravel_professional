<?php

namespace App\Http\Requests\Marketplace;

use App\Http\Requests\RequestWrapper;

class ProductPurchaseDetailRequest extends RequestWrapper
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'order_id' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id',
            'store_id' => 'nullable|integer',
            'product_id' => 'nullable|integer',
            'type' => 'required|in:1,2,3,4,5',
            'product_name' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:50',
            'model_no' => 'nullable|string|max:20',
            'quantity' => 'nullable|integer',
            'price' => 'nullable|numeric',
            'discount_percent' => 'nullable|string|max:50',
            'total_amount_with_discount' => 'nullable|string|max:50',
            'coupon_code' => 'nullable|string|max:50',
            'payment_id' => 'nullable|string',
            'auger_fee_payment_id' => 'nullable|string|max:100',
            'order_status' => 'required|in:0,1,2,3,4,5,6,7',
            'order_otp' => 'nullable|string|max:4',
            'cancel_order_otp' => 'nullable|string|max:4',
            'payment_status' => 'required|in:0,1',
            'admin_payment_id' => 'nullable|string|max:10',
            'payment_type' => 'required|in:1,2',
            'shipping_address' => 'nullable|string|max:500',
            'shipping_city' => 'nullable|string|max:20',
            'shipping_postal_code' => 'nullable|string|max:10',
            'shipping_state' => 'nullable|string|max:50',
            'shipping_country' => 'nullable|string|max:50',
            'shipping_phone_number' => 'nullable|string|max:15',
            'shipping_email_id' => 'nullable|string|max:50',
            'delivery_person_id' => 'nullable|integer',
            'delivery_charge' => 'required|string|max:20',
            'delivery_type' => 'required|in:0,1,2',
            'created_date_time' => 'nullable|date',
            'refund_expire_date' => 'nullable|date',
            'order_delivery_date' => 'nullable|date',
            'order_canceling_date' => 'nullable|date',
            'return_paid_status' => 'required|in:0,1',
            'token_value' => 'required|numeric',
            'shipping_service' => 'nullable|string|max:255',
        ];
    }

  
}
