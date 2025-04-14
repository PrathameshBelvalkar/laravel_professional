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
        Schema::dropIfExists('coin_reports');
        Schema::create('coin_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coin_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('sub_category_id');
            $table->string('report_file')->nullable();
            $table->foreign('coin_id')->references('id')->on('coin')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('coin_category')->onDelete('cascade');
            $table->foreign('sub_category_id')->references('id')->on('coin_sub_category')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_reports');
    }
};
