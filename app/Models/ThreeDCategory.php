<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ThreeDCategory extends Model
{
  use HasFactory;

  use SoftDeletes;
  protected $table = "three_d_categories";
}
