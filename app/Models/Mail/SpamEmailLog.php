<?php

namespace App\Models\Mail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpamEmailLog extends Model
{
    use HasFactory;
    protected $table = 'spam_email_logs';
    protected $guarded = [];
}
