<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityStory extends Model
{
  use HasFactory;
  use SoftDeletes;
  protected $fillable = [
    'user_id',
    'media_type',
    'media_path',
    'visibility',
    'shared_with',
    'expires_at',
    'tagged_users'
  ];
  protected $dates = ['deleted_at'];
  public $timestamps = true;
  // protected $casts = [
  //   'shared_with' => 'array',
  //   'likes' => 'array'
  //'media_path' => 'array',
  // ];

  // protected $dates = [
  //     'expires_at'
  // ];
}
