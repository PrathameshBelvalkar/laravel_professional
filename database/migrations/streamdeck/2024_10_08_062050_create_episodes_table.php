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
        Schema::create('season_episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->after('id')->onDelete('cascade');
            $table->foreignId('series_id')->constrained('tv_series');
            $table->foreignId('season_id')->constrained('tv_seasons');
            $table->integer('episode_number')->nullable();
            $table->string('title');
            $table->text('description');
            $table->text('video_url')->nullable();
            $table->date('release_date');
            $table->text('thumbnail')->nullable();
            $table->integer('rating')->nullable();
            $table->integer('views')->default(0)->nullable();
            $table->text('subtitles')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('season_episodes');
    }
};
