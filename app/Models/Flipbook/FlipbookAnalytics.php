<?php

namespace App\Models\Flipbook;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class FlipbookAnalytics extends Model
{
    use HasFactory;
    protected $fillable=['flipbook_id','views','downloads','countries'];
}
