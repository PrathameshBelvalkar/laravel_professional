<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketplaceSiteSubscriptionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace_site_subscription', function (Blueprint $table) {
            $table->id(); // Automatically sets as the primary key with auto-increment
            $table->string('email_address', 255)->nullable();
            $table->integer('marketplace')->nullable();
            $table->integer('silocloud')->nullable();
            $table->text('interest_category')->nullable();
            $table->dateTime('notification_period')->nullable();
            $table->longText('product_visited_count')->nullable();
            $table->integer('notification_cnt')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('is_deleted')->default(0);
            $table->dateTime('product_visited_log')->useCurrent();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('country', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketplace_site_subscription');
    }
}
