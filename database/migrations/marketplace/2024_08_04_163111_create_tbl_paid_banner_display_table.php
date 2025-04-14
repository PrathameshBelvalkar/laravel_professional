<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblPaidBannerDisplayTable extends Migration
{
    public function up()
    {
        Schema::create('tbl_paid_banner_display', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('product_store_id')->nullable();
            $table->string('banner_image', 500)->nullable();
            $table->string('title', 500)->nullable();
            $table->string('subtitle', 255)->nullable();
            $table->string('link', 255)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['1', '2', '3'])->nullable()->comment('1=>approved,2=>reject,3=>pending');
            $table->enum('type', ['1', '2'])->default('1')->comment('1=>Product,2=>Store');
            $table->double('price')->nullable();
            $table->text('payment_id')->nullable();
            $table->date('ad_show_date')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_paid_banner_display');
    }
}
