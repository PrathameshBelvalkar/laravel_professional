<?php

namespace App\Models\FileManager;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileManager extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'file_manager';

  protected $dates = ['deleted_at'];
}
