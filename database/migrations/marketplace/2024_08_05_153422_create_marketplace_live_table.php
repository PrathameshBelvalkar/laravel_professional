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
        Schema::create('marketplace_live', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('stream_title', 255);
            $table->string('stream_key_id', 255);
            $table->string('stream_key', 255);
            $table->string('stream_name', 255);
            $table->text('stream_banner')->nullable();
            $table->text('product_ids', 255)->nullable();
            $table->string('playback_url_key', 255);
            $table->enum('stream_status', [0, 1])->default(0)->comment('0 => inactive, 1 => active')->nullable();
            $table->text('stream_url_live')->nullable();
            $table->uuid('broadcast_id')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_live');
    }
};
