<?php

namespace App\Models\Public;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteSetting extends Model
{
  use HasFactory;
  protected $fillable = [
    'field_name',
    'field_key',
    'field_value',
  ];
  protected $table = 'site_settings';
  use SoftDeletes;
}
