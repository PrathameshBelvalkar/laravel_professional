<?php

namespace App\Models\Flipbook;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlipbookPublication extends Model
{
  use HasFactory, SoftDeletes;
  public function flipbook()
  {
    return $this->belongsTo(Flipbook::class, 'flipbook_id');
  }
}
