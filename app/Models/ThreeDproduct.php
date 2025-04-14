<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThreeDproduct extends Model
{
  use HasFactory;

  protected $table = 'three_dproducts';

  protected $dates = ['deleted_at'];
}
