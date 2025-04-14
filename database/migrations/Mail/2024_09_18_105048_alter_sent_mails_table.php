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
        Schema::table('sent_mails', function (Blueprint $table) {
            $table->text('is_label')->nullable()->after('is_spam');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sent_mails', function (Blueprint $table) {
            $table->dropColumn('is_label');
        });
    }
};
