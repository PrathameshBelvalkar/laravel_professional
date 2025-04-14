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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('purpose', ['1', '2', '3', '4'])->comment('1 token purchase 2 package purchase 3 service purchase 4 storage purchase');
            $table->enum('mode', ['1', '2', '3', '4'])->comment('1 stripe 2 authorize 3 paypal 4 other');
            $table->enum('status', ['1', '2', '3', '4'])->comment('1 initiated 2 pending 3 success 4 failed');
            $table->enum('approved', ['0', '1', '2'])->comment('0 pending 1 approved 2 declined')->default('0');
            $table->double('token_value')->nullable();
            $table->string('payment_txn_id');
            $table->unsignedBigInteger('payer_id');
            $table->double('amount');
            $table->timestamps();
            $table->text('note')->nullable();
            $table->text('payment_response')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->softDeletes();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('payer_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
