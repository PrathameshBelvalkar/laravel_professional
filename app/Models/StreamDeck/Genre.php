<?php

namespace App\Models\StreamDeck;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Genre extends Model
{
    use HasFactory;
    protected $table = "genres";

    protected $fillable = [
        'name',
        'slug',
        'user_id'
    ];


}
