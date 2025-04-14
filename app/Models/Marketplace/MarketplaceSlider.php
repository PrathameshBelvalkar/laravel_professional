<?php
namespace App\Models\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceSlider extends Model
{
    use HasFactory;

    protected $table = 'marketplace_slider';

    protected $fillable = [
        'slider1',
        'slider2',
        'slider3',
        'image_text1',
        'image_text2',
        'image_text3',
    ];
}
