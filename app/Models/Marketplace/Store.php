<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $table = 'marketplace_stores';

    protected $fillable = [
        'user_id',
        'name',
        'product_type',
        'description',
        'qr_code_image',
        'qr_code_image_ext',
        'theme',
        'thumbnail_path',
        'image_path',
        'banner_path',
        'is_disabled',
        'category_id',
        'product_limit',
        'created_datetime',
    ];


}

