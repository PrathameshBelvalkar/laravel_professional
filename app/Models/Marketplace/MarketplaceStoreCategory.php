<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceStoreCategory extends Model
{
    use HasFactory;

    protected $table = 'marketplace_store_category';

    protected $fillable = [
        'name',
        'image_path',
        'image_ext',
    ];
}

