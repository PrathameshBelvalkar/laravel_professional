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
        Schema::create('team_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organizer_id');
            $table->unsignedBigInteger('sport_id');
            $table->unsignedBigInteger('tournament_id');
            $table->unsignedBigInteger('team_one_id');
            $table->unsignedBigInteger('team_two_id');
            $table->string('location')->nullable()->comment('Location of the match');
            $table->date('date')->nullable()->comment('Date of the match');
            $table->time('time')->nullable()->comment('Time of the match');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organizer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sport_id')->references('id')->on('sports')->onDelete('cascade');
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
        Schema::dropIfExists('team_matches');
    }
};
