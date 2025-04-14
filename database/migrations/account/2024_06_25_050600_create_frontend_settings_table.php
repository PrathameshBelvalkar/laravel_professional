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
        Schema::create('frontend_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum("theme", ['1', '2'])->default("1")->comment("1 => light mode 2 => dark mode");
            $table->json("apps")->nullable()->comment("1 => account 2 => qr 3 => support 4 => streaming 5 => marketplace 6 => calendar 7 => storage 8 => wallet 9 => coin 10 => game 11 => talk 12 => tv");
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frontend_settings');
    }
};
