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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('event_title')->nullable();
            $table->dateTime('start_date_time');
            $table->dateTime('end_date_time');
            $table->string('category')->nullable();
            $table->string('subCategory')->nullable();
            $table->string('meetingLink')->nullable();
            $table->longText('event_description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('reminder')->nullable();
            $table->enum('visibility', ['1', '2', '3'])->default('1')->comment('1 => Private, 2 => Followers only, 3 => Public');
            $table->string('event_attachment', 255)->nullable();
            $table->string('location', 500)->nullable();
            $table->integer('parent_id')->nullable();
            $table->longText('invited_by_username')->nullable();
            $table->longText('invited_by_email')->nullable();
            $table->string('link')->nullable();
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
        Schema::dropIfExists('calendar_events');
    }
};
