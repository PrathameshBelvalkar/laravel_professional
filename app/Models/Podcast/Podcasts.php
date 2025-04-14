<?php

namespace App\Models\Podcast;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Podcasts extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = "podcasts";
  protected $dates = ['deleted_at'];
  protected $fillable = [
    'user_id',
    'title',
    'description',
    'image_url',
    'publisher',
    'language',
    'explicit',
    'category_id',
    'tags_id',
    'release_date',
    'favourite',
    'website',
    'number_of_episodes',
  ];
  public function getCreatedAtAttribute($value)
  {
    return \Carbon\Carbon::parse($value)->toDateString();  // Format the date to YYYY-MM-DD
  }
  public function episodes()
  {
    return $this->hasMany(Episode::class);
  }
}
