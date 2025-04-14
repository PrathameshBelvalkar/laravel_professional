<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CommunityPost extends Model
{
  use HasFactory, SoftDeletes;

  protected $fillable = ['user_id', 'visibility', 'caption', 'media', 'likes', 'upload_time', 'tagged_users', 'report_reason'];

  public function taggedUsers()
  {
    return $this->hasMany(CommunityPostTag::class, 'post_id');
  }
  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
