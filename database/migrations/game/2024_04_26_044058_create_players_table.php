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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organizer_id');
            $table->unsignedBigInteger('team_id');
            $table->string('player_name')->nullable();
            $table->string('player_image')->nullable();
            $table->unsignedBigInteger('player_position')->nullable();
            $table->unsignedBigInteger('display_number')->nullable();
            $table->timestamps();
            $table->foreign('organizer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('player_position')->references('id')->on('player_positions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
