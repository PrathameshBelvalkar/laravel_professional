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
    Schema::create('community_live_comments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('stream_id')->references('id')->on('community_lives')->onDelete('cascade');
      $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->string('comment')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('community_live_comments');
  }
};
