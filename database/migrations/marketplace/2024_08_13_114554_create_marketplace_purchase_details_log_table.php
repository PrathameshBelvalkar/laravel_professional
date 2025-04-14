<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketplacePurchaseDetailsLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace_purchase_details_log', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID field
            $table->unsignedBigInteger('order_id')->nullable(); // Nullable foreign key for orders
            $table->enum('order_status', ['0', '1', '2', '3', '4', '5', '6'])
                ->default('0')
                ->comment('0: order Placed, 1: Admin Approved, 2: Canceled, 3: Shipped, 4: Closed, 5: Refund Period Start/Delivered, 6: Refund order');
            $table->unsignedInteger('return_days')->nullable(); // Number of days for return
            $table->enum('order_type', ['1', '2', '3', '4'])
                ->default('4')
                ->comment('1: Only Replace, 2: Only Return, 3: Both return and replace, 4: No return and replace');
            $table->dateTime('shipped_date')->nullable();
            $table->dateTime('delivery_date')->nullable();
            $table->dateTime('order_closed_date')->nullable();
            $table->dateTime('order_canceling_date')->nullable();
            $table->dateTime('request_add_date')->nullable();
            $table->dateTime('refund_date')->nullable();
            $table->string('order_id', 36)->change();
            $table->timestamps(); // Created at and Updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketplace_purchase_details_log');
    }
}
