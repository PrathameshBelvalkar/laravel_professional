<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = "calendar_events";
    protected $fillable = [
        'user_id',
        'event_title',
        'start_date_time',
        'end_date_time',
        'event_description',
        'status',
        'reminder',
        'visibility',
        'category',
        'event_attachment',
        'link',
        'meeting_id',
        'meetingLink',
        'subCategory',
        'organizer_user_id',
        'invited_by_email',
        'invited_by_username',
        'parent_id',
    ];
    protected $dates = ['deleted_at'];
    public function organizer()
    {
        return $this->hasOne(User::class);
    }
}
