<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceCategory extends Model
{
    use HasFactory;

    protected $table = 'marketplace_category';

    protected $fillable = [
        'category_name',
        'image_path',
        'image_ext',
    ];
}

