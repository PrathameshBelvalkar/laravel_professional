<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShareFiles extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'share';

    protected $fillable = ['file_id', 'shared_with_user_id', 'permission'];

    protected $dates = ['deleted_at'];

    public function file()
    {
        return $this->belongsTo(FileManager::class, 'file_id', 'id');
    }
     
}
