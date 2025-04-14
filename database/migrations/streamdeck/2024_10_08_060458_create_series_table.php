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
        Schema::create('tv_series', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('genre');
            $table->date('release_date');
            $table->text('cover_image')->nullable();
            $table->enum('content_rating',['0','1','2'])->comment('0 = > U','1 = > U/A','2 = > A');
            $table->foreignId('user_id')->constrained('users')->after('id')->onDelete('cascade');
            $table->enum('status',['0','1','2'])->comment(' 0 = > series ongoing','1 = > series completed','2 = > series cancel');
            //status
            // 0 = > series ongoing
            // 1 = > series completed
            // 2 = > series cancel

            //content_rating
            //0 = > U
            //1 = > U/A
            //2 = > A

            $table->text('cast');
            $table->text('directors');
            $table->foreignId('channel_id')->constrained('channels');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tv_series');

    }
};
