<?php

namespace App\Models\Podcast;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Podcategory extends Model
{
    use HasFactory;
    protected $table = 'podcast_categories';
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'gradient',
        'category_image'
    ];
}
