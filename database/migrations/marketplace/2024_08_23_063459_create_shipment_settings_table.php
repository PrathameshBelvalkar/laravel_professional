<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipment_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('service_provider', ['1', '2'])->default('1')->comment('1=>\'Fedex\',2=>\'POS\'');
            $table->enum('type', ['1', '2', '3'])->nullable()->comment('1=>\'Service\',2=>\'Pickup\',3=>\'Package\'');
            $table->text('key')->nullable();
            $table->text('value')->nullable();
            $table->enum('status', ['0', '1'])->default('1')->comment('0=>\'Inactive\',1=>\'Active\'');
            $table->timestamps(); // This will add `created_at` and `updated_at` columns
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipment_settings');
    }
}
