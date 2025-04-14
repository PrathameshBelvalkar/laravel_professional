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
        Schema::create('podcast_artist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('artist_name')->nullable();
            $table->string('artist_image')->nullable();
            $table->string('artist_cover_image')->nullable();
            $table->text('artist_bio')->nullable();
            $table->integer('total_podcasts')->default(0);
            $table->integer('followers_count')->default(0);
            $table->text('users_following')->nullable();
            $table->integer('total_plays')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('podcast_artist');
    }
};
