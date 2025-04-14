<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('marketplace_category', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->string('category_name', 500)->nullable()->change();
            $table->string('image_path', 500)->nullable()->default(NULL);
            $table->string('image_ext', 10)->nullable()->default(NULL);
            $table->dateTime('date_time')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_category');
    }
};
