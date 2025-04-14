<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_marketplace_product_purchase_details_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketplaceProductPurchaseDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('marketplace_product_purchase_details', function (Blueprint $table) {
            $table->increments('id');
            $table->text('order_id')->nullable();
            $table->unsignedInteger('user_id')->nullable(); // Foreign key
            $table->unsignedInteger('store_id')->nullable()->comment('for type\r\n1-4 = service_id\r\n5 = store_id'); // Foreign key
            $table->unsignedInteger('product_id')->nullable()->comment('for type\r\n1-4 = plan_id or package_id\r\n5 = product_id'); // Foreign key
            $table->enum('type', ['1', '2', '3', '4', '5'])->default('5')->comment('1= package\r\n2= cloud storage\r\n3= service\r\n4= mining service\r\n5= product');
            $table->string('product_name')->nullable();
            $table->string('product_type', 50)->nullable();
            $table->string('model_no', 20)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->double('price')->nullable();
            $table->string('discount_percent', 50)->nullable();
            $table->string('total_amount_with_discount', 50)->nullable();
            $table->string('coupon_code', 50)->nullable();
            $table->text('payment_id')->nullable();
            $table->string('auger_fee_payment_id', 100)->nullable();
            $table->enum('order_status', ['0', '1', '2', '3', '4', '5', '6', '7'])->default('0')->comment('{0: order Placed. 1 :Admin Approved, 2: Canceled, 3: Shipped, 4: Closed, 5: Refund Period Start/Delivered,6: Refund Order,7:Return replace request}');
            $table->string('order_otp', 4)->nullable();
            $table->string('cancel_order_otp', 4)->nullable();
            $table->enum('payment_status', ['0', '1'])->default('0')->comment('0=>unpaid, 1=>paid');
            $table->string('admin_payment_id', 10)->nullable();
            $table->enum('payment_type', ['1', '2'])->default('1')->comment('1) Silo Token 2) Silo Bank');
            $table->string('shipping_address', 500)->nullable();
            $table->string('shipping_city', 20)->nullable();
            $table->string('shipping_postal_code', 10)->nullable();
            $table->string('shipping_state', 50)->nullable();
            $table->string('shipping_country', 50)->nullable();
            $table->string('shipping_phone_number', 15)->nullable();
            $table->string('shipping_email_id', 50)->nullable();
            $table->unsignedInteger('delivery_person_id')->nullable(); // Foreign key
            $table->string('delivery_charge', 20)->default('0');
            $table->enum('delivery_type', ['0', '1', '2'])->default('0')->comment('0 => Free,1=>Regular ,2=>Express');
            $table->timestamp('created_date_time')->useCurrent();
            $table->date('refund_expire_date')->nullable();
            $table->date('order_delivery_date')->nullable();
            $table->timestamp('order_canceling_date')->nullable();
            $table->enum('return_paid_status', ['0', '1'])->default('0')->comment('0 =>Not Paid,1=>Paid');
            $table->double('token_value')->default(0);
            $table->string('shipping_service', 255)->nullable();

            // Foreign key constraints
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            // $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            // $table->foreign('delivery_person_id')->references('id')->on('delivery_persons')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketplace_product_purchase_details');
    }
}

