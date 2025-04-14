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
        Schema::create('affiliate_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("affiliate_id");
            $table->unsignedBigInteger("affiliate_master_id");
            $table->unsignedBigInteger("affiliate_txn_id")->nullable();
            $table->unsignedBigInteger("refered_id");
            $table->unsignedBigInteger("refered_txn_id")->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('affiliate_id')->references('id')->on('users');
            $table->foreign('refered_id')->references('id')->on('users');
            $table->foreign('affiliate_master_id')->references('id')->on('affiliate_masters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_rewards');
    }
};
