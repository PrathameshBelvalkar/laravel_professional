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
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_1_id")->comment("will sent request");
            $table->unsignedBigInteger("user_2_id")->comment("will accept request");
            $table->enum("status", ["0", "1", "2", "3", "4", "5", "6"])->comment("0 => pending, 1=> approved, 2 => rejected, 3 => deleted after approved by user_1, 4 => deleted after approved by user_2, 5 => blocked by user_1, 6 => blocked by user_2")->default("0");
            $table->softDeletes();
            $table->json("actions")->nullable();
            $table->enum("ignored", ["0", "1"])->default("0")->comment("1 => will ignore request");
            $table->timestamps();
            $table->foreign('user_1_id')->references('id')->on('users');
            $table->foreign('user_2_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
