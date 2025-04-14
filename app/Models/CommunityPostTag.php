<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityPostTag extends Model
{
  use HasFactory;
  protected $fillable = ['post_id', 'user_id', 'hidden'];

  public function post()
  {
    return $this->belongsTo(CommunityPost::class, 'post_id');
  }
}
