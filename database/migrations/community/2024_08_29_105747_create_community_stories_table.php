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
    Schema::create('community_stories', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->string('media_type'); // 'image', 'video', etc.
      $table->string('media_path', 255);
      $table->json('tagged_users')->nullable();
      $table->json('like_dislike')->nullable();
      $table->enum('visibility', ['0', '1', '2'])->default('0')->comment('0 => Public, 1 => Only For Followers, 2 => Shared With Specific Users');
      $table->json('shared_with')->nullable()->comment('Array of user IDs with whom the story is shared');
      $table->string('location')->nullable();
      $table->json('report_reason')->nullable();
      $table->timestamp('expires_at'); // Timestamp for when the story expires
      $table->boolean('is_expired')->default(false);
      $table->boolean('is_archived')->default(false);
      //$table->boolean('is_deleted_from_archive')->default(false);
      $table->enum('is_deleted_from_archive', ['0', '1'])->default(0);
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('community_stories');
  }
};
