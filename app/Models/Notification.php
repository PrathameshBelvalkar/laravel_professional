<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'to_user_id',
        'from_user_id',
        'title',
        'description',
        'reference_id',
        'module',
        'link',
        'is_admin',
        'updated_at',
        'deleted_at',
        'created_at'
    ];
}
