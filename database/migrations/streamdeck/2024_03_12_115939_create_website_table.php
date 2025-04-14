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
        Schema::create('website', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('channels')->nullable();
            $table->string('title', 255);
            $table->string('domain')->nullable();
            $table->string('domain_id')->nullable();
            $table->string('custom_domain', 255)->nullable();
            $table->string('site_logo', 255)->nullable();
            $table->string('site_favicon', 255)->nullable();
            $table->enum('header', [0, 1])->default('1')->comment('0 => Hide header, 1 => Show header');
            $table->enum('page_layout', [0, 1])->default('1')->comment('0 => Full width, 1 => Fixed width');
            $table->string('background_color', 255)->nullable();
            $table->string('font_color', 255)->nullable();
            $table->string('highlight_color', 255)->nullable();
            $table->string('playback_options')->nullable();
            $table->string('display_options')->nullable();
            $table->string('footer_text')->nullable();
            $table->string('footer_description')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website');
    }
};
