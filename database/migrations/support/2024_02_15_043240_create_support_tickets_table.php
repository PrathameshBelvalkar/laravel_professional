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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('ticket_unique_id');
            $table->unsignedBigInteger('category_id');
            $table->string('title', 1000);
            $table->text('description')->nullable();
            $table->text('file_upload')->nullable();
            $table->text('tags')->nullable();
            $table->enum("status", ['0', '1', '2'])->comment('0=>Unsolved 1=>Solved')->default('0');
            $table->enum("is_stared", ['0', '1', '2'])->comment('0=>no 1=>yes')->default('0');
            $table->unsignedBigInteger("last_reply_id")->nullable();
            $table->json("tech_users")->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('support_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
