<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    protected $table = 'coupons'; 
    protected $fillable = [
        'object_type',
        'vendor_id',
        'code',
        'type',
        'amount',
        'minimum_spend',
        'maximum_spend',
        'start_date',
        'end_date',
        'use_limit',
        'same_ip_limit',
        'use_limit_per_user',
        'use_device',
        'multiple_use',
        'total_use',
        'status',
    ];

    public function couponHistories()
    {
        return $this->hasMany(CouponHistory::class);
    }
}
