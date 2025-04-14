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
        Schema::create('live_stream', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('stream_title', 255);
            $table->string('stream_key_id', 255);
            $table->string('stream_key', 255);
            $table->string('playback_url_key', 255);
            $table->string('destination_id')->nullable();
            $table->enum('stream_status', [0, 1])->default(0)->comment('0 => inactive, 1 => active')->nullable();
            $table->string('live_start_time')->nullable();
            $table->enum('destination_on_off', [0, 1])->default(0)->comment('0 => off, 1 => on');
            $table->text('stream_url_live')->nullable();
            $table->timestamps();
            //$table->softDeletes();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_stream');
    }
};
