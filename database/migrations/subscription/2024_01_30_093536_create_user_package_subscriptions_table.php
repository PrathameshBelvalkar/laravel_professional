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
        Schema::create('user_package_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('user_id');
            $table->double('price')->comment("0 => trial, null => free service n => paid service price");
            $table->double('auger_price')->comment("0 => trial, null => free service n => paid service price");
            $table->json('package_data')->nullable();
            $table->string('promo_code')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('txn_id');
            $table->string('token_value');
            $table->string('auger_txn_id')->nullable();
            $table->string('reward_txn_id')->nullable();
            $table->json('downgrade')->nullable();
            $table->enum('payment_mode', ['1', '2', '3', '4'])->comment('1 stripe 2 authorize 3 paypal 4 token');
            $table->enum('validity', ['1', '12', '3'])->comment('1 month 2 quarter 3 year');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('package_id')->references('id')->on('packages');
            $table->foreign('user_id')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_package_subscriptions');
    }
};
