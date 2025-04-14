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
    Schema::create('public_news', function (Blueprint $table) {
      $table->id();
      $table->enum('api_source', ['world_news', 'news_api'])->nullable();
      $table->string('api_id')->nullable();
      $table->string('title')->nullable();
      $table->text('news_text')->nullable();
      $table->text('summary')->nullable();
      $table->string('url', 500)->nullable();
      $table->string('catgory', 500)->nullable();
      $table->string('image', 500)->nullable();
      $table->string('video', 500)->nullable();
      $table->dateTime('publish_date')->nullable();
      $table->string('author', 1000)->nullable();
      $table->string('language')->nullable();
      $table->string('source_country')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('public_news');
  }
};
