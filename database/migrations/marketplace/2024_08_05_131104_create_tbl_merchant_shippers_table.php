<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblMerchantShippersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_merchant_shippers', function (Blueprint $table) {
            $table->id();
            $table->integer('shipper_user_id')->nullable();
            $table->integer('merchant_user_id')->nullable();
            $table->timestamps(); // this will add created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_merchant_shippers');
    }
}
