<?php

namespace App\Models\Mail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLabel extends Model
{
    use HasFactory;
    protected $table = 'email_labels';
    protected $guarded = [];
}
