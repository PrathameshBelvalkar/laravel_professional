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
        Schema::create('tv_livestreams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('channel_id');
            $table->string('date')->nullable();
            $table->text('output_file')->nullable();
            $table->text('output_blob')->nullable();
            $table->text('playlistpathLink')->nullable();
            $table->enum('status', ['0', '1'])->default('1')->nullable();
            $table->string('earliest_since')->nullable();
            $table->string('latest_till')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tv_livestreams');
    }
};
