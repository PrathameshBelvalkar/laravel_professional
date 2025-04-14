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
        Schema::create('app_rating_review', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->references('id')->on('silo_apps')->onDelete('cascade');
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('app_name');
            $table->string('reviews')->nullable();
            $table->enum('ratings',['1','2','3','4','5'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_rating_review');
    }
};
