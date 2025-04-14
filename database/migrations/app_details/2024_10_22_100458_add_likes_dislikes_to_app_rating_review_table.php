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
        Schema::table('app_rating_review', function (Blueprint $table) {
            $table->json('likes')->after('ratings')->nullable();
            $table->json('dislikes')->after('likes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_rating_review', function (Blueprint $table) {
            $table->dropColumn(['likes', 'dislikes']);
        });
    }
};
