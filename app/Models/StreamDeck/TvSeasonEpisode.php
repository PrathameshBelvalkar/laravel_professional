<?php

namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Model;
use App\Models\StreamDeck\TvSeriesSeason;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TvSeasonEpisode extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'season_episodes';
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'user_id',
        'series_id',
        'season_id',
        'episode_number',
        'title',
        'description',
        'video_url',
        'release_date',
        'thumbnail',
        'views',
        'rating',
        'subtitles',
        'duration'
    ];

    public function season()
    {
        return $this->belongsTo(TvSeriesSeason::class);
    }
}
