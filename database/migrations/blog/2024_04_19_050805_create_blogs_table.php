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
    Schema::create('blogs', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id');
      $table->string('title')->nullable();
      $table->enum('for_whom', ['0', '1', '2'])->default('0')->comment('0 => default option , 1 => silocloud, 2 => blockchain')->nullable();
      $table->json('tags')->nullable();
      $table->text('description')->nullable();
      $table->enum('type', ['0', '1', '2'])->default('0')->comment('0 => default option , 1 => blog, 2 => news')->nullable();
      $table->text('categories')->nullable();
      $table->string('blog_image')->nullable();
      $table->string('blog_video')->nullable();
      $table->string('blog_detail_image')->nullable();
      $table->string('author')->nullable();
      $table->timestamps();
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('blogs');
  }
};
