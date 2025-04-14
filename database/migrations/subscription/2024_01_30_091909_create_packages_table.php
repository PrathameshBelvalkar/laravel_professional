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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('key', 255);
            $table->text('description')->nullable();
            $table->json('services');
            $table->string('logo', 255)->nullable();
            $table->string('icon', 255)->nullable();
            $table->string('thumbnail', 255)->nullable();
            $table->integer('monthly_price');
            $table->integer('quarterly_price');
            $table->integer('yearly_price');
            $table->enum('type', ['1', '2', '3'])->comment('1 => blank/empty package 2 trial 3 paid packages');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
