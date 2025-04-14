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
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id');
            $table->string('qrcode_id');
            $table->string('qr_name');
            $table->text('qrcode_data');
            $table->string('qrcode_type', 255);
            $table->text('file_path');
            $table->text('pdf_path')->nullable();
            $table->text('file_key')->nullable();
            $table->enum('qrscan_type', ['0', '1', '2']);
            $table->integer('scans')->default(0);
            $table->string('product_price')->nullable();
            $table->string('product_stock')->nullable();
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
        Schema::dropIfExists('qr_codes');
    }
};
