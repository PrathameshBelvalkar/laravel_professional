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
        Schema::create('file_manager', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('file_id', 255);
            $table->string('parent_folder_id', 255)->nullable();
            $table->string('file_path', 255);
            $table->string('file_name', 255);
            $table->string('file_size')->nullable();
            $table->string('file_type');
            $table->enum('is_star', ['0', '1'])->default('0')->comment('0 => not stared, 1 => file is stared');
            $table->enum('is_shared', ['0', '1'])->default('0')->comment('0 => not shared, 1 => file is shared');
            $table->enum('is_deleted', ['0', '1'])->default('0')->comment('0 => not deleted, 1 => deleted');
            $table->json('shared_permission')->nullable()->comment('1 => view, 2 => download, 3 => edit');
            $table->json('shared_with')->nullable();
            $table->string('file_creation_date', 255)->nullable();
            $table->string('share_message')->nullable();
            $table->enum('visibility', ['1', '2', '3'])->default('1')->comment('1 => private, 2 => shared olny with, 3 => public');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_manager');
    }
};
