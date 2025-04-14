<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('key')->nullable();
            $table->string('sub_title')->nullable();
            $table->double('rating')->nullable();
            $table->string('url')->nullable();
            $table->string('category')->nullable();
            $table->enum('free', ["0", "1"])->comment('0 => paid, 1 => free');
            $table->enum('tabs_category', ["1", "2", "3", "4", "5", "6"])->comment('1 => All 2 => Social 3 => Streaming 4 => Financial 5 => Tool 6 => AI');
            $table->string('image')->nullable();
            $table->enum('is_frontend_banner', ["0", "1"])->comment('0 => not frontend banner, 1 => frontend banner');
            $table->enum('is_frontend_app', ["0", "1"])->comment('0 => not frontend app, 1 => frontend app');
            $table->longText('logo_light')->nullable();
            $table->longText('logo_dark')->nullable();
            $table->string('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
};
