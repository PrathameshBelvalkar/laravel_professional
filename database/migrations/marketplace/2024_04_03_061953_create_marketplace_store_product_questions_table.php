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
    Schema::create('marketplace_store_product_questions', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id');
      $table->unsignedBigInteger('product_id');
      $table->string('merchant_id')->nullable();
      $table->string('nick_name')->nullable();
      $table->string('question')->nullable();
      $table->string('answer')->nullable();
      $table->enum('is_answered', [0, 1])->default('0')->comment('0 => Not Answered, 1 => Answered');
      $table->enum('status', [1, 2, 3])->default('3')->comment('1 => Displayed, 2 => Pending answer, 3 => Block');
      $table->json('likes')->nullable();
      $table->json('dislikes')->nullable();
      $table->timestamps();
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->foreign('product_id')->references('id')->on('marketplace_products')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('marketplace_store_product_questions');
  }
};
