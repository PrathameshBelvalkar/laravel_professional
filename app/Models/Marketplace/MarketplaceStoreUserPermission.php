<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceStoreUserPermission extends Model
{
    use HasFactory;

    protected $table = 'marketplace_store_user_permission';

    protected $fillable = [
        'store_id',
        'allowed_permissions',
    ];
}
