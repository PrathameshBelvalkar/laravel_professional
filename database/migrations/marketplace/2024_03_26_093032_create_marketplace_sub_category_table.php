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
        Schema::create('marketplace_sub_category', function (Blueprint $table) {
            $table->id();
            $table->string('sub_category_name');
            $table->unsignedBigInteger('parent_category_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('image_ext', 10)->nullable();
            $table->integer('type')->default(1)->comment('1 => "subcategory", 2 => "tags"');
            $table->dateTime('date_time')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->foreign('category_id')->references('id')->on('marketplace_category');
            $table->timestamps();
            $table->foreign('parent_category_id')->references('id')->on('marketplace_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_sub_category');
    }
};
