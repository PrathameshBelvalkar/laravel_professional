<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketplaceSellerBusinessDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace_seller_business_details', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->unsignedBigInteger('user_id')->nullable(); // Foreign key to users table
            $table->string('person_name')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('company_name')->nullable();
            $table->string('street_address')->nullable();
            $table->string('city')->nullable();
            $table->string('state_code')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code')->nullable();
            $table->timestamps(); // created_at and updated_at fields

            // Add foreign key constraint if needed
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketplace_seller_business_details');
    }
}
