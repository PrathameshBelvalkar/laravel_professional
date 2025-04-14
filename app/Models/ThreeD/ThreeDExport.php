<?php

namespace App\Models\ThreeD;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThreeDExport extends Model
{
  use HasFactory;
  protected $fillable = ['format', 'scope', 'rotation'];
}
