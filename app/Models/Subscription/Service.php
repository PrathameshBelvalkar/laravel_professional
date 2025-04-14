<?php

namespace App\Models\Subscription;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory;
    use SoftDeletes;
    public function servicePlans()
    {
        return $this->hasMany(ServicePlan::class)->select(['id', 'name', 'service_id', 'features', 'monthly_price', 'quarterly_price', 'yearly_price', 'status', 'styles', 'logo', 'icon']);
    }
}
