<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceOrderReturnReplaceRequest extends Model
{
    use HasFactory;

    // Table associated with the model
    protected $table = 'marketplace_order_return_replace_request';

    // Primary key of the table
    protected $primaryKey = 'id';

    // Indicate if the model should be timestamped
    public $timestamps = true;

    // Fillable properties to allow mass assignment
    protected $fillable = [
        'order_id',
        'quantity',
        'order_type',
        'request_type',
        'reshipment_order_otp',
        'product_collect_status',
        'reason',
        'media',
        'request_status',
        'carrier_person',
        'request_count',
        'product_collect_date',
        'product_approve',
        'product_approve_date',
        'closed_request',
    ];

    // Cast attributes to a specific type
    protected $casts = [
        'product_collect_date' => 'datetime',
        'product_approve_date' => 'datetime',
    ];
}
