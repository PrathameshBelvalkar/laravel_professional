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
        Schema::create('token_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('to_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->double("txn_tokens");
            $table->double("token_value");
            $table->double("paid_amount")->nullable()->comment("in case of cash requests");
            $table->string("transaction_id")->nullable();
            $table->timestamp("approved_at")->nullable();
            $table->enum("status", ["0", "1"])->default("0")->comment("0=>pending 1=>approved");
            $table->enum("type", ["1", "2"])->nullable()->comment("1=>token request 2=>cash request");
            $table->enum("payment_gateway", ["1", "2", "3"])->nullable()->comment("1=>paypal 2=>stripe 3=>sbc");
            $table->string("comment", 500)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_requests');
    }
};
