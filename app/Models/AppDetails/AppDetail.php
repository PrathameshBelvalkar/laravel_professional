<?php

namespace App\Models\AppDetails;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppDetail extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table='app_details';
    protected $fillable=[
        'about_app',
        'app_features',
        'app_screenshots',
        'app_logo',
    ];
}
