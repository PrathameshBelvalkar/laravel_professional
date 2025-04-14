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
    Schema::create('dashboard_image_selections', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->foreignId('dashboard_image_id')->nullable()->references('id')->on('dashboard_images')->onDelete('cascade');
      $table->string('custom_image_path')->nullable();
      $table->string('color')->nullable();
      $table->enum('is_logo', [0, 1])->default(0)->nullable();
      $table->string('alignment')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('dashboard_image_selections');
  }
};
