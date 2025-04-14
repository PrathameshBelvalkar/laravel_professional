<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketplaceProductOrderLogDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('marketplace_product_order_log_details', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id')->nullable();
            $table->string('description', 500)->nullable();
            $table->enum('log_type', ['1', '2'])->default('1');
            $table->timestamp('created_date')->useCurrent();
            $table->integer('user_id')->nullable();
            $table->timestamps(); 
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketplace_product_order_log_details');
    }
}
