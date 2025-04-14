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
        Schema::create('user_storage_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum("status", ["0", "1", "2"])->comment("0 pending 1 successful 2 failed");
            $table->unsignedBigInteger("txn_id")->comment("id from token transaction log table");
            $table->unsignedBigInteger("plan_id")->comment("id from storage_plans table");
            $table->json("storage_details")->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('txn_id')->references('id')->on('token_transaction_logs');
            $table->foreign('plan_id')->references('id')->on('storage_plans');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_storage_plans');
    }
};
