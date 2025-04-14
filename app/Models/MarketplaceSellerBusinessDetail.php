<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceSellerBusinessDetail extends Model
{
    use HasFactory;

    protected $table = 'marketplace_seller_business_details';

    protected $fillable = [
        'user_id',
        'person_name',
        'phone_number',
        'company_name',
        'street_address',
        'city',
        'state_code',
        'postal_code',
        'country_code',
    ];

    // Define any relationships here, e.g., to the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
