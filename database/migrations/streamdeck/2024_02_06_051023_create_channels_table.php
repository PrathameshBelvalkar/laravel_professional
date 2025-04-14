<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('user_id');
            $table->string('channel_name', 255)->default('Untitled Channel');
            $table->enum('channel_type', [0, 1, 2])->default('0')->comment('0 => Linear, 1 => Fast, 2 => On demand');
            $table->enum('linear_channel_type', [0, 1, 2])->nullable()->comment('0 => Scheduled, 1 => Looped, 2 => Fast');
            $table->enum('schedule_duration', ['0', '1'])->nullable()->comment('0 => Daily, 1 => weekly');
            $table->time('start_time')->nullable();
            $table->string('logo')->nullable();
            $table->enum('logo_on_off', ['0', '1'])->nullable()->default('0')->comment('0 => Off, 1 => On');
            $table->enum('logo_position', ['0', '1'])->nullable()->comment('0 => Right, 1 => left');
            $table->string('main_color')->nullable();
            $table->enum('channel_embedded', ['0', '1'])->default('0')->comment('0 => Anywhere, 1 => Only on domains I choose');
            $table->string('add_tag_url')->nullable();
            $table->enum('no_of_adds_in_hour', ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10'])->nullable();
            $table->enum('Seconds_per_add_break', ['15', '30', '45', '60', '90', '120'])->nullable();
            $table->string('concatenate_path')->nullable();
            $table->string('output_file_path')->nullable();
            $table->json('views')->nullable()->change();
            $table->string('channelUuid',60)->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
