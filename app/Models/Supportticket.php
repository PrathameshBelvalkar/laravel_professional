<?php

namespace App\Models;

use App\Models\Support\Reply;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'support_tickets';
    public function category()
    {
        return $this->hasOne(SupportCategory::class);
    }
    public function replies()
    {
        return $this->hasMany(Reply::class, "ticket_id")->latest('updated_at');
    }
}
