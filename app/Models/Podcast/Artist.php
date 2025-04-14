<?php

namespace App\Models\Podcast;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    use HasFactory;
    protected $table = 'podcast_artist';
    protected $fillable = ['user_id', 'artist_name', 'artist_image', 'artist_cover_image', 'artist_bio', 'total_podcasts', 'followers_count', 'total_plays'];
}
