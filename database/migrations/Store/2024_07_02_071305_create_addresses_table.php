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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->string("address_line_1", 500);
            $table->string("address_line_2", 500)->nullable();
            $table->string("city", 500);
            $table->string("state", 500);
            $table->string("country", 500);
            $table->unsignedBigInteger("zipcode");
            $table->unsignedBigInteger("phone_number");
            $table->unsignedBigInteger("country_code");
            $table->string("location", 1000)->nullable()->comment("address by map");
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
