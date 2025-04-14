
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketplaceSliderTable extends Migration
{
    public function up()
    {
        Schema::create('marketplace_slider', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slider1');
            $table->string('slider2');
            $table->string('slider3');
            $table->text('image_text1')->nullable();
            $table->text('image_text2')->nullable();
            $table->text('image_text3')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketplace_slider');
    }
}
