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
        Schema::create('coin_news', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coin_id');
            $table->text('title');
            $table->text('description');
            $table->text('author');
            $table->string('news_img')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('coin_id')->references('id')->on('coin')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_news');
    }
};
