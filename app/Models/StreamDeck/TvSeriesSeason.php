<?php

namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Model;
use App\Models\StreamDeck\TvSeasonEpisode;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TvSeriesSeason extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tv_seasons';
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'user_id',
        'series_id',
        'season_number',
        'title',
        'description',
        'release_date',
        'episode_count',
        'cover_image',
        'video_url'
    ];


    protected static function boot()
    {
        parent::boot();


        static::deleting(function ($episode) {
   
            if ($episode->imagePath && file_exists(storage_path('app/' . $episode->imagePath))) {
                unlink(storage_path('app/' . $episode->imagePath));
            }


            if ($episode->filePath && file_exists(storage_path('app/' . $episode->filePath))) {
                unlink(storage_path('app/' . $episode->filePath));
            }


            if ($episode->subtitlePath && file_exists(storage_path('app/' . $episode->subtitlePath))) {
                unlink(storage_path('app/' . $episode->subtitlePath));
            }
        });
    }

    public function episodes()
    {
        return $this->hasMany(TvSeasonEpisode::class, 'season_id');
    }
}
