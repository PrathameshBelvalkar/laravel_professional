<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceSiteSubscription extends Model
{
    use HasFactory;

    protected $table = 'marketplace_site_subscription';

    protected $fillable = [
        'email_address',
        'marketplace',
        'silocloud',
        'interest_category',
        'notification_period',
        'product_visited_count',
        'notification_cnt',
        'user_id',
        'is_deleted',
        'product_visited_log',
        'latitude',
        'longitude',
        'country',
    ];
}