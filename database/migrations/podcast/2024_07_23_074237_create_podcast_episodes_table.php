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
    Schema::create('podcast_episodes', function (Blueprint $table) {
      $table->id();
      $table->foreignId('podcast_id')->references('id')->on('podcasts');
      $table->string('title');
      $table->text('description');
      $table->string('audio_url');
      $table->integer('duration');
      $table->timestamp('published_at')->nullable();
      $table->enum('explicit', ['0', '1'])->default('0');
      $table->string('image_url')->nullable();
      $table->text('transcriptions')->nullable();
      $table->json('guest_speakers')->nullable();
      $table->integer('season_number')->nullable();
      $table->integer('episode_number')->nullable();
      $table->integer('listened')->nullable();
      $table->text('liked_user_id')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('podcast_episodes');
  }
};
