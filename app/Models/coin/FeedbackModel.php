<?php

namespace App\Models\coin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\UserProfile;

class FeedbackModel extends Model
{
    use HasFactory;

    protected $table = 'coin_feedback';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userProfile()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'user_id'); 
    }
}
