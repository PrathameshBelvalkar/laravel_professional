<?php

namespace App\Models\Flipbook;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlipbookReviews extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'flipbook_reviews';
    protected $fillable = ['publication_id', 'average', 'data'];
}
