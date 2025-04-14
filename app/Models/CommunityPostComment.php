<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityPostComment extends Model
{
  use HasFactory;
  protected $fillable = ['post_id', 'user_id', 'comments', 'image', 'like_dislike'];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function userProfile()
  {
    return $this->belongsTo(UserProfile::class);
  }
  public function replies()
  {
    return $this->hasMany(CommunityCommentReply::class, 'comment_id');
  }
}
