<?php

namespace App\Models\QR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QRScan extends Model
{
    use HasFactory;
    protected $table = "qr_scans";
    protected $fillable = ['user_id', 'scanned_data'];
}
