<?php

namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\MarketplaceProducts;

class MarketplaceStore extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'marketplace_stores'; 
    protected $fillable = [
        'name', 
        'store',  
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
        'store_logo' 
    ];


    public function products()
    {
        return $this->hasMany(MarketplaceProducts::class, 'store_id', 'id');
    }

}
