<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentDetail extends Model
{
    use HasFactory;

    // Specify the table associated with the model
    protected $table = 'shipment_details';

    // Specify the primary key for the model (default is 'id')
    protected $primaryKey = 'id';

    // Disable timestamps if not using 'created_at' and 'updated_at'
    public $timestamps = true;

    // Define the fillable attributes to allow mass assignment
    protected $fillable = [
        'order_id',
        'status',
        'location',
        'create_shipment',
        'product_pickup',
        'tracking_details',
    ];

    // Define the type casting for attributes (if needed)
    protected $casts = [
        'status' => 'string',
        'location' => 'string',
        'create_shipment' => 'string',
        'product_pickup' => 'string',
        'tracking_details' => 'string',
    ];
}
