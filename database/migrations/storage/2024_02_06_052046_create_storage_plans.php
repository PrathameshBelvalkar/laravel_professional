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
        Schema::create('storage_plans', function (Blueprint $table) {
            $table->id();
            $table->string("name", 255);
            $table->string("key", 255)->nullable();
            $table->double("price")->comment("in USD");
            $table->double("storage_value");
            $table->enum("storage_unit", ['MB', 'GB', 'TB']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_plans');
    }
};
