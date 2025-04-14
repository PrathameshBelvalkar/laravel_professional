<?php

namespace App\Models\StreamDeck;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeriesReview extends Model
{
    use HasFactory;

    protected $table ="tv_reviews";

    protected $fillable=[
        'user_id',
       'series_id',	
       'rating',	
       'comment'
    ];
}
