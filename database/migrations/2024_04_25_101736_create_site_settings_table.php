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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string("field_key", 255);
            $table->string("field_name", 255);
            $table->string("description", 1000)->nullable();
            $table->string("field_value", 1000);
            $table->enum('type', ['1', '2'])->nullable()->comment('1 => File path, 2 => value eg email');
            $table->enum("module", ["0", '1', '2', '3', '4', '5', '6', '7', '8'])->comment("1 => account 2 => qr 3 => support 4 => streaming 5 => marketplace 6 => calendar 7 => storage 8 => wallet 0 => all");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
