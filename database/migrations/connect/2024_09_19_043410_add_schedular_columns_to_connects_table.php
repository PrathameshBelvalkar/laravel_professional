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
        Schema::table('connects', function (Blueprint $table) {
            $table->dateTime("meeting_start_time")->nullable()->comment("this time will be GMT time");
            $table->dateTime("meeting_end_time")->nullable()->comment("this time will be GMT time");
            $table->string("meeting_time_zone")->nullable();
            $table->enum("is_admin_joined", ["0", "1"])->nullable()->comment("0 => admin not joined yet 1 => admin joined");
            $table->string("admin_id", )->nullable()->comment("jitsi admin id");
            $table->string("password")->nullable();
            $table->json("phone_number")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('connects', function (Blueprint $table) {

        });
    }
};
