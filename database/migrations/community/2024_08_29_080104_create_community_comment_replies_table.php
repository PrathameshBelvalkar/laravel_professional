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
    Schema::create('community_comment_replies', function (Blueprint $table) {
      $table->id();
      $table->foreignId('comment_id')->references('id')->on('community_post_comments')->onDelete('cascade');
      $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->string('reply')->nullable();
      $table->json('like_dislike')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('community_comment_replies');
  }
};
