<?php

namespace App\Models\Mail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailReply extends Model
{
  use HasFactory;
  protected $table = 'reply_mails';
  protected $guarded = [];
  
}
