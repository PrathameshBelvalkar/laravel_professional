<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityStoryTag extends Model
{
  use HasFactory;
  protected $fillable = [
    'story_id',
    'user_id',
  ];

  public function taggedUsers()
  {
    return $this->hasMany(CommunityStoryTag::class, 'story_id');
  }
}
