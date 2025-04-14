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
    Schema::create('community_posts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->enum('visibility', ['0', '1', '2'])->default('0')->comment('0 => Public, 1 => Only For, 2 => Private');
      $table->string('caption')->nullable();
      $table->json('media')->nullable();
      $table->json('likes')->nullable();
      $table->json('tagged_users')->nullable();
      $table->string('location')->nullable();
      $table->timestamp('upload_time')->nullable();
      $table->string('unique_link')->nullable();
      $table->json('report_reason')->nullable();
      $table->boolean('is_archived')->default(false);
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('community_posts');
  }
};
