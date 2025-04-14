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
    Schema::create('collection_shares', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id');
      $table->unsignedBigInteger('collection_id');
      $table->json('shared_permission')->nullable()->comment('1 => view, 2 => edit, 3 => delete');
      $table->enum('visibility', ['1', '2'])->default('1')->comment('1 => private, 2 => public');
      $table->enum('is_shared', ['0', '1'])->default('0')->comment('0 => not shared, 1 => file is shared');
      $table->enum('is_deleted', ['0', '1'])->default('0')->comment('0 => not deleted, 1 => deleted');
      $table->json('shared_with')->nullable();
      $table->string('share_message')->nullable();
      $table->timestamps();
      $table->softDeletes();
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->foreign('collection_id')->references('id')->on('flipbook_collections')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('collection_shares');
  }
};
