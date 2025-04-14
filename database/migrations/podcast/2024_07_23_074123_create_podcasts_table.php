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
        Schema::create('podcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('image_url')->nullable();
            $table->string('publisher')->nullable();
            $table->string('language')->defaultValue('en');
            $table->enum('explicit', ['0', '1'])->default('0');
            $table->string('category_id')->nullable();
            $table->string('tags_id')->nullable();
            // $table->foreignId('category_id')->references('id')->on('podcast_categories');
            $table->timestamp('release_date')->nullable();
            $table->string('favourite')->nullable();
            $table->string('website')->nullable();
            $table->integer('number_of_episodes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('podcasts');
    }
};
