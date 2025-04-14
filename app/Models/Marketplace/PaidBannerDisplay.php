<?php

namespace  App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaidBannerDisplay extends Model
{
    use HasFactory;

    protected $table = 'tbl_paid_banner_display';

    protected $fillable = [
        'user_id',
        'product_store_id',
        'banner_image',
        'title',
        'subtitle',
        'link',
        'description',
        'status',
        'type',
        'price',
        'payment_id',
        'ad_show_date',
    ];
}

