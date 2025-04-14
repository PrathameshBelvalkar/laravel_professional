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
        Schema::create('service_plans', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->unsignedBigInteger("service_id");
            $table->json("features")->nullable();
            $table->integer('monthly_price');
            $table->integer('quarterly_price');
            $table->integer('yearly_price');
            $table->enum('status', ['0', '1'])->comment('1 => Active, 0 => Deleted');
            $table->json('styles')->nullable();
            $table->string('logo', 255)->nullable();
            $table->string('icon', 255)->nullable();
            $table->string('thumbnail', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_plans');
    }
};
