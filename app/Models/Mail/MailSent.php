<?php

namespace App\Models\Mail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailSent extends Model
{
  use HasFactory;
  protected $table = 'sent_mails';
  protected $guarded = [];
  public function getMetaAttribute($value)
  {
      return json_decode($value, true);
  }
}
