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
    Schema::create('silosecure_consultation', function (Blueprint $table) {
      $table->id();
      $table->date('date')->nullable();
      $table->time('time')->nullable();
      $table->string('full_name')->nullable();
      $table->string('email')->nullable();
      $table->BigInteger('phone')->nullable();
      $table->text('message')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('silosecure_consultation');
  }
};
