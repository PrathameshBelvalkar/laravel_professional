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
        Schema::create('affiliate_masters', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("description");
            $table->integer("affiliate_value");
            $table->integer("refered_value");
            $table->string('banner')->nullable();
            $table->enum("status", ["1", "2"])->default("1")->comment('1 => active 2 => deactivated');
            $table->enum("type", ["1", "2"])->default("1")->comment("1 => percentage 2 => fixed");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_masters');
    }
};
