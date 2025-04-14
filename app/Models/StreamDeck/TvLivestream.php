<?php

namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TvLivestream extends Model
{
    use HasFactory;
    protected $table = 'tv_livestreams';

    protected $fillable = [
        'user_id',
        'channel_id',
        'date',
        'output_file',
        'output_blob',
        'playlistpathLink',
        'earliest_since',
        'latest_till',
        'status'
    ];
}
