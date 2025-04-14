<?php

namespace App\Models\Podcast;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Episode extends Model
{
  use HasFactory, SoftDeletes;
  protected $table = "podcast_episodes";
  protected $dates = ['deleted_at'];
  protected $fillable = [
    'podcast_id',
    'title',
    'description',
    'audio_url',
    'duration',
    'published_at',
    'explicit',
    'image_url',
    'transcriptions',
    'guest_speakers',
    'season_number',
    'episode_number',
    'listened'
  ];
  public function podcast()
  {
    return $this->belongsTo(Podcasts::class);
  }
}
