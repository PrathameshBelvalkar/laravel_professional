<?php

namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoritesSeries extends Model
{
    use HasFactory;

    protected $table="tv_favorites";

    protected $fillable=[
        'user_id',
        'series_id',
        'added_at'
    ];
}
