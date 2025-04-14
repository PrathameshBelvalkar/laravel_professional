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
    Schema::table('marketplace_live', function (Blueprint $table) {
      $table->dateTime('schedule_date_time')->nullable()->after('broadcast_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('marketplace_live', function (Blueprint $table) {
      $table->dropColumn('schedule_date_time');
    });
  }
};
