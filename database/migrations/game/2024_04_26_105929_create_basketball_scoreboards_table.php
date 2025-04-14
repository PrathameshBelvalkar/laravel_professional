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
        Schema::create('basketball_scoreboards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organizer_id');
            $table->unsignedBigInteger('tournament_id');
            $table->unsignedBigInteger('team_one_id');
            $table->unsignedBigInteger('team_two_id');
            $table->unsignedBigInteger('team_one_score');
            $table->unsignedBigInteger('team_two_score');
            $table->json('team_one_log');
            $table->json('team_two_log');
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('organizer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('tournament_id')->references('id')->on('tournaments')->onDelete('cascade');
            $table->foreign('team_one_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('team_two_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('basketball_scoreboards');
    }
};
