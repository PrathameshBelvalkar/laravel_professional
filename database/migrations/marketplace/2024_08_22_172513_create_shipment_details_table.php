<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipment_details', function (Blueprint $table) {
            $table->id(); // Equivalent to `id` int(11) NOT NULL
            $table->unsignedBigInteger('order_id')->nullable(); // Equivalent to `order_id` int(11) DEFAULT NULL
            $table->enum('status', ['1', '2', '3'])->default('1')->comment("1 => 'Create Shipment', 2 => 'Pickup Shipment', 3 => 'Tracking Details'"); // Equivalent to `status` enum('1','2','3') CHARACTER SET utf8mb4 NOT NULL DEFAULT '1' COMMENT '1 => ''Create Shipment'' , 2=>''Pickup Shipment'',3=>''Tracking Details'''
            $table->text('location')->nullable(); // Equivalent to `location` text DEFAULT NULL
            $table->text('create_shipment')->nullable(); // Equivalent to `create_shipment` text DEFAULT NULL
            $table->text('product_pickup')->nullable(); // Equivalent to `product_pickup` text DEFAULT NULL
            $table->text('tracking_details')->nullable(); // Equivalent to `tracking_details` text CHARACTER SET utf8mb4 DEFAULT NULL
            $table->timestamps(); // Equivalent to `created_at` and `updated_at` with current timestamps

            // If you need to add a foreign key constraint to `order_id` (assuming it references a column in another table):
            // $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipment_details');
    }
}
