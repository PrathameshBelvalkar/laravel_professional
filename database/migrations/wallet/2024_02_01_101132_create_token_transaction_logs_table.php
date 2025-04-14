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
        Schema::create('token_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->string('txn_id', 255);
            $table->text('hash_key');
            $table->string('perticulars', 255)->nullable();
            $table->double('token_value');
            $table->unsignedBigInteger('sender_id');
            $table->unsignedBigInteger('receiver_id');
            $table->double('txn_tokens');
            $table->enum('txn_status', ['0', '1', '2', '3'])->comment('0 initiated/pending 1 success 2 failed');
            $table->enum('txn_type', ['1', '2', '3', '4', '5', '6', "7"])->comment('1 buy 2 transfer 3 withdraw 4 consumed 5 auger fee 6 reward');
            $table->enum('txn_for', ['1', '2', '3', "4", "5"])->comment('1 storage 2 package 3 service 4 marketplace 5 cash conversion')->nullable();
            $table->unsignedBigInteger('parent_txn_id')->comment('if transaction is parent type is auger fee(primary_key)')->nullable();
            $table->unsignedBigInteger('previous_txn_id')->comment('last transaction id(txn_id)')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_transaction_logs');
    }
};
