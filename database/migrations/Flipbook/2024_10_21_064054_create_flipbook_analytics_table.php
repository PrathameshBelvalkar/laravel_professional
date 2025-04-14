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
        Schema::create('flipbook_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('flipbook_id')->references('id')->on('flipbooks')->onDelete('cascade');
            $table->integer('views')->default(0)->nullable();
            $table->integer('downloads')->default(0)->nullable();
            $table->json('countries')->nullable();
            $table->integer('clicks')->nullable();
            $table->json('device_names')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flipbook_analytics');
    }
};
