<?php

namespace App\Models\Podcast;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Podtag extends Model
{
    use HasFactory;
    protected $table = "podcast_tags";

    protected $fillable = [
        'name',
        'slug',
        'category_id'
    ];
}
