<?php

namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEpisodeWatchlist extends Model
{
    use HasFactory;

    protected $table="user_watchlists";

    protected $fillable=[
        'user_id',
        'episode_id',
    ];
}
