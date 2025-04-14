<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityCommentReply extends Model
{
  use HasFactory;
  protected $fillable = ['comment_id', 'user_id', 'reply', 'like_dislike'];

  public function comment()
  {
    return $this->belongsTo(CommunityPostComment::class, 'comment_id');
  }
}
