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
        Schema::create('player_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sport_id');
            $table->string('position')->nullable();
            $table->string('key');
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('sport_id')->references('id')->on('sports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_positions');
    }
};
