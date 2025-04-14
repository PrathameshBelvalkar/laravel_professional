<?php

namespace App\Models\StreamDeck;



use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TvSeries extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "tv_series";
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'title',
        'user_id',
        'description',
        'genre',
        'release_date',
        'cover_image',
        'content_rating',
        'status',
        'cast',
        'directors',
        'channel_id'
    ];

}
