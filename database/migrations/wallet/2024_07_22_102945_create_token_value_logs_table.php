<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('token_value_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('new_user_life_value');
            $table->unsignedBigInteger('new_user_registration');
            $table->double('token_value');
            $table->double('aggregation_cost');
            $table->double('total_token_supply');
            $table->double('total_coin_supply');
            $table->double('coin_price');
            $table->double('market_cap');
            $table->double('coins_in_circulation');
            $table->double('reflextions_variable');
            $table->double('coin_value');
            $table->double('user_login');
            $table->double('login_rewards');
            $table->double('subsription_totals');
            $table->double('staking_account');
            $table->double('rev_share');
            $table->double('network_fees');
            $table->double('market_value_assumptions');
            $table->double('liquidity_pool');
            $table->double('y_ebita');
            $table->double('marketing_budget');
            $table->double('platform_earnings_projections');
            $table->double('input_100');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_value_logs');
    }
};
