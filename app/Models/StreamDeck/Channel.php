<?php

namespace App\Models\StreamDeck;

use App\Models\LiveStream;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Channel extends Model
{
    use HasFactory;
    protected $table = 'channels';
    protected $fillable = [
        'views', 
    ];
}
