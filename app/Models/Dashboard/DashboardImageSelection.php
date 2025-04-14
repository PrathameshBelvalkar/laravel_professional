<?php

namespace App\Models\Dashboard;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardImageSelection extends Model
{
  use HasFactory;
  protected $fillable = ['user_id', 'dashboard_image_id', 'custom_image_path', 'color', 'is_logo', 'alignment'];
}
