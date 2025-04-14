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
        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("product_id");
            $table->string("title", 255);
            $table->string("description", 1000);
            $table->enum("status", ['1', '2'])->default('1')->comment("1 => active, 2 => deactived");
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('marketplace_products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specifications');
    }
};
