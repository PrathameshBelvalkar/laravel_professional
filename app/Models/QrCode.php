<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QrCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'qr_codes';

    protected $fillable = ['id', 'qr_name', 'qrcode_id','qrscan_type' ,'scans','qrcode_type', 'file_path'];

    protected $dates = ['deleted_at'];
}
