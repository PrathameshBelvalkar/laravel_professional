<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceSubCategory extends Model
{
    use HasFactory;

    protected $table = 'marketplace_sub_category';
    protected $fillable = [
        'sub_category_name',
        'category_id',
        'image_path',
        'image_ext',
        'type',
        'parent_category_id',
    ];
}
