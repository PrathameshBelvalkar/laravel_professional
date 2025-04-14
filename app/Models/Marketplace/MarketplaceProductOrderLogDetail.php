<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class MarketplaceProductOrderLogDetail extends Model
{
    // Define the table associated with the model
    protected $table = 'marketplace_product_order_log_details';

    // Define the fillable attributes
    protected $fillable = [
        'order_id',
        'description',
        'log_type',
        'created_date',
        'user_id',
    ];

    // If you want to specify custom date formats
    protected $dates = [
        'created_date',
    ];
}
