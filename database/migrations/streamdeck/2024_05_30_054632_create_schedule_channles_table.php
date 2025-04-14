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
        Schema::create('schedule_channles', function (Blueprint $table) {
            $table->id();
            $table->string('channel_id', 4)->nullable();
            $table->string('channelUuid', 255)->nullable();
            $table->longText('epg_data')->nullable();
            $table->timestamps();
            $table->softDeletes();
            // $table->foreign('channelUuid')->references('id')->on('channels')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_channles');
    }
};
