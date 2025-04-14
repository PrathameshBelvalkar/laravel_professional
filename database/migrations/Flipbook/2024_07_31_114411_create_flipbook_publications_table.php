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
    Schema::create('flipbook_publications', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->foreignId('flipbook_id')->references('id')->on('flipbooks')->onDelete('cascade');
      $table->string('title');
      $table->enum('visibility', ["1", "2"])->default("2")->comment("1 => public 2 => private");
      $table->string('description', 500)->nullable();
      $table->string('free_access_code', 20)->nullable();
      $table->string('preview_link', 255)->nullable();
      $table->foreignId('collection_id')->nullable()->references('id')->on('flipbook_collections')->onDelete('cascade');
      $table->unsignedBigInteger('user_quantity')->default(0);
      $table->unsignedBigInteger('user_applied')->default(0);
      $table->enum('safe_mode', ["0", "1"])->default(0)->comment("0 => safe, 1 => not safe")->nullable();
      $table->text('categories', 255)->nullable();
      $table->double('price')->nullable();
      $table->string('currency')->default('USD')->nullable();
      $table->enum("status", ["0", "1", "2"])->default("1")->comment("0 => inActive, 1 => published 2 => sell");
      $table->json("pages")->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('publication_flipbook');
  }
};
