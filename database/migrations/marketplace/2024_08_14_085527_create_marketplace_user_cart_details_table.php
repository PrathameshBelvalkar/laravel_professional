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
        Schema::create('marketplace_user_cart_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->json('products')->nullable();  // JSON column to store multiple products
            $table->unsignedBigInteger('product_id');
            $table->string('name')->nullable();
            $table->integer('product_quantity')->nullable();
            $table->double('product_price')->nullable();
            $table->string('delivery_charge')->nullable();
            $table->double('total_amount')->nullable();
            $table->string('discount_amount')->nullable();
            $table->string('total_amount_with_discount')->nullable();
            $table->string('user_ip')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->integer('shipping_postal_code')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_country')->nullable();
            $table->string('shipping_phone_number')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('marketplace_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_user_cart_details');
    }
};
