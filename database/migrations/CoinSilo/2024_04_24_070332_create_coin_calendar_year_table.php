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
        Schema::create('coin_calendar_year', function (Blueprint $table) {
            $table->id();
            $table->year('start_year');
            $table->integer('start_month');
            $table->year('end_year');
            $table->integer('end_month');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_calendar_year');
    }
};
