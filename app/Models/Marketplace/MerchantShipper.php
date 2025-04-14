<?php
namespace App\Models\Marketplace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MerchantShipper extends Model
{
    use HasFactory;

    protected $table = 'tbl_merchant_shippers';

    protected $fillable = [
        'merchant_user_id',
        'shipper_user_id',
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'shipper_user_id');
    }

}
