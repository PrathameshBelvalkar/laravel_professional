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
        Schema::create('coin_investment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('year_id');
            $table->unsignedBigInteger('coin_id');
            $table->unsignedBigInteger('transaction_id');
            $table->double('investment_amount');
            $table->text('notes');
            $table->dateTime('investment_date');
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('year_id')->references('id')->on('coin_calendar_year')->onDelete('cascade');
            $table->foreign('coin_id')->references('id')->on('coin')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('token_transaction_logs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_investment');
    }
};
