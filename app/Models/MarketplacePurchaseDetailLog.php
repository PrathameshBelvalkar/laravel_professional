<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplacePurchaseDetailLog extends Model
{
    protected $table = 'marketplace_purchase_details_log';
    protected $primaryKey = 'id';
    protected $fillable = [
        'order_id', 'order_status', 'return_days', 'order_type', 'shipped_date', 'delivery_date', 
        'order_closed_date', 'order_canceling_date', 'request_add_date', 'refund_date', 
        'created_at', 'updated_at'
    ];
}