<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunityStorySeen extends Model
{
  use HasFactory;
  protected $fillable = ['user_id', 'story_id', 'seen_by'];
}
