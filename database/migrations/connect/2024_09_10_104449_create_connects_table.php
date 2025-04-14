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
        Schema::create('connects', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->references("id")->on("users")->onDelete("cascade");
            $table->string('room_name')->unique();
            $table->enum("visibility", ['0', '1', "2", "3"])->default('0')->comment('0 => none 1 => followers 2 => Connection 3 => Public');
            $table->enum("status", ['0', '1', "2"])->nullable()->comment('0 => initiated 1 => ongoing 2 => closed');
            $table->text("invited_usernames")->nullable();
            $table->text("invited_emails")->nullable();
            $table->string("title")->nullable();
            $table->string("description", 500)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connects');
    }
};
