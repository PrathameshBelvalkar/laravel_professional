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
        Schema::create('podacst_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commentator')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('podacst_id')->references('id')->on('podcasts')->onDelete('cascade');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('podacst_comments');
    }
};
