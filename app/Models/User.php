<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// use App\Models\Coin\FeedbackModel;
use Illuminate\Notifications\Notifiable;

class User extends Model
{
  use HasFactory;
  use SoftDeletes;
  use Notifiable;

  protected $fillable = ['username', 'first_name', 'last_name', 'email', 'password', 'storage', "sso_user_id", "is_influencer"];
  public function role()
  {
    return $this->belongsTo(Role::class);
  }
  public function profile()
  {
    return $this->hasOne(UserProfile::class);
  }
}
