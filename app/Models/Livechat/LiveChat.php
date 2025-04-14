<?php

namespace App\Models\Livechat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveChat extends Model
{
    use HasFactory;
    protected $table = "live_chat";
    protected $fillable = ['broadcast_id', 'message_data'];
}
