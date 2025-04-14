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
        Schema::table('user_service_subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('last_subscriptions_log_id')->nullable();
            $table->unsignedBigInteger('current_subscriptions_log_id')->nullable();
            $table->enum("status", ["0", "1"])->default('0')->comment("0 inactive or expired 1 => active");
            $table->foreign('last_subscriptions_log_id')->references('id')->on('user_service_subscription_logs');
            $table->foreign('current_subscriptions_log_id')->references('id')->on('user_service_subscription_logs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_service_subscriptions', function (Blueprint $table) {
            //
        });
    }
};
