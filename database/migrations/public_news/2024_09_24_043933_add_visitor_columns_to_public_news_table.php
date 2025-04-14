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
        Schema::table('public_news', function (Blueprint $table) {
            $table->json("visitors")->comment("array of visitor's ip addresses(unique)")->nullable();
            $table->unsignedBigInteger("read_count")->comment("read Count")->default("0");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_news', function (Blueprint $table) {
            //
        });
    }
};
