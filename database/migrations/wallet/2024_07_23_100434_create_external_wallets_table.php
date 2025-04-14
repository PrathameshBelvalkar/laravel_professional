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
        Schema::create('external_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId("external_wallet_masters_id")->references("id")->on("external_wallet_masters")->onDelete("cascade");
            $table->foreignId("user_id")->references("id")->on("users")->onDelete("cascade");
            $table->string("funding_wallet_key")->nullable();
            $table->string("trading_wallet_key")->nullable();
            $table->enum('status', ["1", "2"])->default("1")->comment("1=>active 2=>inactive");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_wallets');
    }
};
