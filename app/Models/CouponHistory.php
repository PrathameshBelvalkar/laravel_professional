<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coupon_id',
        'order_id',
        'object_type',
        'discount_amount',
        'user_ip',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}

