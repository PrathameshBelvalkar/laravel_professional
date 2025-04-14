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
        Schema::create('marketplace_store_product_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('product_id');
            $table->string('review_title')->nullable();
            $table->string('review_description')->nullable();
            $table->string('review_media')->nullable();
            $table->string('receive_email_notification')->default('N')->comment('N => Dont send, Y => Send Notification');
            $table->enum('status', [1, 2])->default('1')->comment('1 => Approved, 2 => Blocked');
            $table->integer('upvote')->unsigned()->default(0);
            $table->integer('downvote')->unsigned()->default(0);
            $table->string('upvote_ids')->nullable();
            $table->string('downvote_ids')->nullable();
            $table->integer('rating')->unsigned()->default(1);
            $table->string('approved')->default('N')->comment('N => Pending, Y => Approved');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('marketplace_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_store_product_reviews');
    }
};
