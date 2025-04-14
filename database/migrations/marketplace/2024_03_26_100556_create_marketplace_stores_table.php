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
        Schema::create('marketplace_stores', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('store')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('logo')->nullable();
            $table->string('description')->nullable();
            $table->string('website_url')->nullable();
            $table->string('banner')->nullable();
            $table->string('categories', 1000)->nullable();
            $table->enum('status', ['0', '1', '2'])->comment('0 => pending, 1 => approved, 2 => inactive');
            $table->unsignedBigInteger('ratings')->default(0);
            $table->unsignedBigInteger('selling_count')->default(0);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('product_type')->nullable();
            $table->binary('qr_code_image')->nullable();
            $table->string('qr_code_image_ext', 5)->nullable();
            $table->integer('theme')->nullable();
            $table->string('thumbnail_path', 500)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->string('banner_path', 500)->nullable();
            $table->enum('is_disabled', ['N', 'Y'])->nullable();
            $table->string('category_id', 100)->nullable();
            $table->string('product_limit', 50)->default('25');
            $table->timestamp('created_datetime')->useCurrent();
            $table->string('store_logo')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_stores');
    }
};
