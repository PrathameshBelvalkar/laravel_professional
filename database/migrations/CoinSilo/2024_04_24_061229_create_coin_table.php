<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coin', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('coin_name');
            $table->string('coin_symbol');
            $table->string('coin_logo')->nullable();
            $table->longText('description')->nullable();
            $table->double("price")->comment("in USD")->nullable();
            $table->double("one_h")->comment("in %")->nullable();
            $table->double("twenty_four_h")->comment("in %")->nullable();
            $table->double("seven_d")->comment("in %")->nullable();
            $table->double("market_cap")->comment("in USD")->nullable();
            $table->double("volume")->comment("in USD")->nullable();
            $table->double("circulation_supply")->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin');
    }
};
