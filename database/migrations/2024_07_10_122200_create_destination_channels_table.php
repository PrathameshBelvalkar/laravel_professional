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
        Schema::create('destination_channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('channel_id');
            $table->text('stream_key');
            $table->enum('playback_status', ['0', '1']);
            $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destination_channels');
    }
};
