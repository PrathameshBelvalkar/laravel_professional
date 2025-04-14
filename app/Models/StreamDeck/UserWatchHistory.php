<?php

namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWatchHistory extends Model
{
    use HasFactory;

    protected $table="user_watch_history";

    protected $fillable=[
        'user_id',
        'episode_id',	
        'progress_percent'
    ];
}
