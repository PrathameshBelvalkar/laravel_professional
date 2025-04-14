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
        Schema::create('app_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->references('id')->on('silo_apps')->onDelete('cascade');
            $table->string('app_name')->nullable();
            $table->string('app_section')->nullable();
            $table->text('about_app')->nullable();
            $table->json('app_features')->nullable();
            $table->string('app_logo')->nullable();
            $table->json('app_screenshots')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_details');
    }
};
