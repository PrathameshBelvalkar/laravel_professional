<?php

namespace App\Models\Wallet;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TokenValueLog extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'new_user_life_value',
        'new_user_registration',
        'token_value',
        'aggregation_cost',
        'total_token_supply',
        'total_coin_supply',
        'coin_price',
        'market_cap',
        'coins_in_circulation',
        'reflextions_variable',
        'coin_value',
        'user_login',
        'login_rewards',
        'subsription_totals',
        'staking_account',
        'rev_share',
        'network_fees',
        'market_value_assumptions',
        'liquidity_pool',
        'y_ebita',
        'marketing_budget',
        'platform_earnings_projections',
        'input_100',
    ];
}
