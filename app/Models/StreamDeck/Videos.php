<?php

namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Videos extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'video_url',
        'file_path',
        'tags',
        'is_featured',
        'views',
        'is_private',
    ];
      protected $casts = [
        'is_featured' => 'boolean', 
    ];
}
