<?php

namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleChannles extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table ='schedule_channles';
    protected $fillable = [
        'channel_id',
        'channelUuid',
        'epg_data'
    ];
}
