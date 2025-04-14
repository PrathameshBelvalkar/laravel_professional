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
    Schema::table('user_profiles', function (Blueprint $table) {
      $table->json("c_following")->nullable()->after("followers");
      $table->json("c_followers")->nullable()->after("followers");
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('user_profiles', function (Blueprint $table) {
      $table->dropColumn('c_following');
      $table->dropColumn('c_followers');
    });
  }
};
