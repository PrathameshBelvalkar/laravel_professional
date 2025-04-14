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
        Schema::create('user_service_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id');
            $table->double('price')->nullable()->comment("0 => trial, null => free service n => paid service price");
            $table->json('service_plan_data')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('txn_id');
            $table->string('auger_txn_id')->nullable();
            $table->string('reward_txn_id')->nullable();
            $table->enum('payment_mode', ['1', '2', '3', '4'])->comment('1 stripe 2 authorize 3 paypal 4 token');
            $table->enum('validity', ['1', '12', '3'])->comment('1 month 2 quarter 3 year');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('service_id')->references('id')->on('services');
            $table->foreign('package_id')->references('id')->on('packages');
            $table->foreign('plan_id')->references('id')->on('service_plans');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_service_subscriptions');
    }
};
