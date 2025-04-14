<?php

namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelsSchedule extends Model
{
    use HasFactory;
    protected $table = 'channels_schedule';

    protected $fillable = [
        'stream_name',
        'stream_start_time',
        'stream_end_time',
        'channel_id',
        'epg_id',
        'schedule_status',
        'updated_by',
        'deleted_at',
        'proposed_start_time',
        'proposed_end_time',
        'default_status',
    ];
}
