<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MarketplaceProductPurchaseDetail extends Model
{
  protected $table = 'marketplace_product_purchase_details';
  protected $primaryKey = 'id';
  public $timestamps = false;
  protected $casts = [
    'created_date_time' => 'datetime',
  ];

  protected $fillable = [
    'order_id',
    'user_id',
    'store_id',
    'product_id',
    'type',
    'product_name',
    'product_type',
    'model_no',
    'quantity',
    'price',
    'discount_percent',
    'total_amount_with_discount',
    'coupon_code',
    'payment_id',
    'auger_fee_payment_id',
    'order_status',
    'order_otp',
    'cancel_order_otp',
    'payment_status',
    'admin_payment_id',
    'payment_type',
    'shipping_address',
    'shipping_city',
    'shipping_postal_code',
    'shipping_state',
    'shipping_country',
    'shipping_phone_number',
    'shipping_email_id',
    'delivery_person_id',
    'delivery_charge',
    'delivery_type',
    'created_date_time',
    'refund_expire_date',
    'order_delivery_date',
    'order_canceling_date',
    'return_paid_status',
    'token_value',
    'shipping_service',
    'influencer_id'
  ];

  public function store()
  {
    return $this->belongsTo('App\Models\Marketplace\MarketplaceStore', 'store_id');
  }

  // Define the 'product' and 'user' relationships similarly
  public function product()
  {
    return $this->belongsTo('App\Models\Marketplace\MarketplaceStore', 'product_id');
  }



  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
