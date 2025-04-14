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
        Schema::create('tv_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('tv_series');
            $table->integer('season_number');
            $table->string('title');
            $table->foreignId('user_id')->constrained('users')->after('id')->onDelete('cascade');
            $table->text('description');
            $table->date('release_date');
            $table->integer('episode_count')->nullable();
            $table->text('video_url')->nullable();
            $table->text('cover_image')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tv_seasons');

    }
};
