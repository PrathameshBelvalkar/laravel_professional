<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceSubCategoryTag extends Model
{
    use HasFactory;

    protected $table = 'marketplace_sub_category_tags';

    protected $fillable = [
        'category_id',
        'sub_category_id',
        'tag',
        'date_time',
    ];

    // Optionally, you can define relationships if needed
    // public function subCategory()
    // {
    //     return $this->belongsTo(MarketplaceSubCategory::class, 'sub_category_id');
    // }
}
