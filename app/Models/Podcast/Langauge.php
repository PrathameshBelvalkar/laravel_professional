<?php

namespace App\Models\Podcast;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Langauge extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'languages';
}
