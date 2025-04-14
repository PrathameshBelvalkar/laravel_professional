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
        Schema::create('reply_mails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('mail_id')->nullable();
            $table->text('from')->nullable();
            $table->text('recipients')->nullable();  
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->text('message')->nullable(); 
            $table->text('attachment')->nullable();
            $table->text('videos')->nullable();
            $table->text('audios')->nullable();
            $table->text('is_recipients')->nullable();
            $table->text('is_delete')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reply_mails');
    }
};
