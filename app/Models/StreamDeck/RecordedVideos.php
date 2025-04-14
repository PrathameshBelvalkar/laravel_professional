<?php

namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecordedVideos extends Model
{
    use HasFactory;
    protected $table = "live_recorded_videos";
}
