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
    Schema::create('community_lives', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->string('stream_title', 255);
      $table->string('stream_key_id', 255);
      $table->text('stream_key');
      $table->text('playback_url');
      $table->enum('stream_status', [0, 1])->default(0)->comment('0 => inactive, 1 => active')->nullable();
      $table->text('stream_url_live')->nullable();
      $table->uuid('community_id')->nullable();
      $table->integer('views')->nullable();
      $table->enum('audience', [0, 1])->default(0)->comment('0 => public, 1 => only for')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('community_lives');
  }
};
