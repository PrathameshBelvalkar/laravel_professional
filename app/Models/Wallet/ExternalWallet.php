<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExternalWallet extends Model
{
    use HasFactory;
    use SoftDeletes;
    public function master()
    {
        return $this->belongsTo(ExternalWallet::class);
    }
}
