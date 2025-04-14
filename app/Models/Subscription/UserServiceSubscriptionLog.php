<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserServiceSubscriptionLog extends Model
{
    use HasFactory;
    use SoftDeletes;
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
