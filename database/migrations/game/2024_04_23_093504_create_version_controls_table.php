<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('version_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('group_key', ['0', '1', '2', '3'])->comment('0 =>silo constructor tool , 1 =>siloopenworld , 2 => silo durby race, 3=> silo assembler tool');
            $table->string('title')->nullable();
            $table->string('file')->nullable();
            $table->string('version')->nullable();
            $table->string('description')->nullable();
            $table->enum('status', ['0', '1', '2'])->default('0')->comment('0 => active, 1 => deleted, 2 => Deprecated');
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
        Schema::dropIfExists('version_controls');
    }
};
