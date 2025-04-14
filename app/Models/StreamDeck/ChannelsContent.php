<?php

namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelsContent extends Model
{
    use HasFactory;

    protected $table = 'channels_content';

    protected $fillable = [
        'epg_id',
        'channel_id',
        'epg_name',
        'streaming_type',
        'streaming_link',
        'streaming_date',
        'start_time',
        'duration',
        'position',
        'thumbnail',
        'status',
        'transcode_status',
        'transcode_reason',
        'transcoded_url',
        'm3u8_url',
        'description',
        'social_link',
        'is_ad',
        'schedule_id',
        'copied_from',
        'transcode_status_id',
        'stream_phone1',
        'stream_phone2',
        'visitors_log',
        'datauserid',
        'likecount',
        'notification_status',
        'default_status',
        'default_schedule_status',
    ];

}
