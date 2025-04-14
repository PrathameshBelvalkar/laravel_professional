<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserServiceSubscription extends Model
{
    use HasFactory;
    use SoftDeletes;


    public function plan()
    {
        return $this->belongsTo(ServicePlan::class);
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
