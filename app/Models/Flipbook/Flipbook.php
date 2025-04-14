<?php

namespace App\Models\Flipbook;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

//use Illuminate\Database\Eloquent\SoftDeletes;

class Flipbook extends Model
{
  use HasFactory, SoftDeletes;
  public function publications()
  {
    return $this->hasMany(FlipbookPublication::class, 'flipbook_id');
  }
}
