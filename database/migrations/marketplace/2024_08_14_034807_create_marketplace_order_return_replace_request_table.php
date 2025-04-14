
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketplaceOrderReturnReplaceRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketplace_order_return_replace_request', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedInteger('quantity')->default(1); // corrected duplicate `quantity` column
            $table->enum('order_type', ['1', '2', '3', '4'])
                ->default(null)
                ->comment("1 => 'Only Replace', 2 => 'Only Return', 3 => 'Both return and replace', 4 => 'No return and replace'");
            $table->enum('request_type', ['1', '2'])
                ->default(null)
                ->comment("1 => 'Replace', 2 => 'Return'");
            $table->unsignedInteger('reshipment_order_otp')->nullable();
            $table->enum('product_collect_status', ['0', '1'])
                ->default('0')
                ->comment("0 => 'Not Collected', 1 => 'Collected'");
            $table->text('reason')->nullable();
            $table->text('media')->nullable();
            $table->enum('request_status', ['0', '1', '2'])
                ->default('0')
                ->comment("0 => 'Pending', 1 => 'Accept', 2 => 'Reject'");
            $table->unsignedInteger('carrier_person')->nullable();
            $table->unsignedInteger('request_count')->default(0);
            $table->dateTime('product_collect_date')->nullable();
            $table->enum('product_approve', ['1', '2', '3'])
                ->default('3')
                ->comment("1 => 'Approve Product Reshipment', 2 => 'Reject Product Reshipment', 3 => 'Pending'");
            $table->dateTime('product_approve_date')->nullable();
            $table->enum('closed_request', ['0', '1', '2'])
                ->default('0')
                ->comment("0 => 'Not Closed', 1 => 'Closed', 2 => 'Replace Process'");
            $table->timestamps(); // created_at and updated_at

            // Indexes (optional)
            $table->index('order_id');
            $table->index('request_status');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('order_id', 36)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketplace_order_return_replace_request');
    }
}
