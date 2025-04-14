<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityStoryHighlight extends Model
{
  use HasFactory;
  protected $fillable = ['user_id', 'title', 'story_ids', 'cover_img'];
  // public function user()
  // {
  //   return $this->belongsTo(User::class);
  // }

  // public function getStoryIdsAttribute($value)
  // {
  //   return json_decode($value, true);
  // }

  // public function setStoryIdsAttribute($value)
  // {
  //   $this->attributes['story_ids'] = json_encode($value);
  // }
}
