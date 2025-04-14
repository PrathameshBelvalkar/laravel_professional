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
        Schema::create('sent_mails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('sender')->nullable(); 
            $table->text('recipients')->nullable(); 
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->text('subject')->nullable();
            $table->text('message')->nullable(); 
            $table->text('attachment')->nullable();
            $table->text('email_type')->nullable();
            $table->text('is_recipients')->nullable();
            $table->text('is_draft')->nullable();
            $table->text('is_favourites')->nullable();
            $table->text('is_archive')->nullable();
            $table->text('is_delete')->nullable();
            $table->text('is_trash')->nullable();
            $table->text('is_read')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_mails');
    }
};
