<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketplaceStoreUserPermissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace_store_user_permission', function (Blueprint $table) {
            $table->id();
            $table->integer('store_id')->nullable();
            $table->longText('allowed_permissions')->nullable()->comment('1=>all,2=>added,3=>owned');
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
        Schema::dropIfExists('marketplace_store_user_permission');
    }
}
