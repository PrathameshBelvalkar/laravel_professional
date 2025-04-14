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
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('promo_code')->unique();
            $table->string('banner')->nullable();
            $table->string('description', 1000)->nullable();
            $table->integer('max_users')->default(10);
            $table->integer('count')->default(0);
            $table->date('end_date');
            $table->enum("status", ["1", "2"])->default("1")->comment('1 => active 2 => deactivated');
            $table->enum("type", ["1", "2"])->default("1")->comment("1 => percentage 2 => fixed");
            $table->integer("value")->default(10);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
