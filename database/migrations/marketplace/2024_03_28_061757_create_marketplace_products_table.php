<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('marketplace_products', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id');
      $table->string('product_name');
      $table->string('product_short_name')->nullable();
      $table->double('price');
      $table->double('discount_percentage');
      $table->string('features');
      $table->text('description');
      $table->string('product_images')->nullable();
      $table->string('thumbnail')->nullable();
      $table->enum('is_accessory', [0, 1])->default('0')->comment('0 => Not an accessories, 1 => it is accessory ');
      $table->unsignedBigInteger('category_id');
      $table->unsignedBigInteger('sub_category_id');
      $table->unsignedBigInteger('store_id');
      $table->string('product_color')->nullable();
      $table->unsignedBigInteger('best_seller_count')->default(0);
      $table->string('brand_name')->nullable();
      $table->string('threed_image')->nullable();
      $table->string('product_video')->nullable();
      $table->text('specifications')->nullable();
      $table->unsignedBigInteger('added_by_user')->nullable();
      $table->unsignedBigInteger('prod_status_user_id')->nullable();
      $table->unsignedBigInteger('company_id')->nullable();
      $table->enum('status', ['1', '2', '3', '4'])->default('3')->comment('1 => Approve, 2 => Reject, 3 => Pending, 4 => Block');
      $table->string('category_tag_id')->nullable();
      $table->text('qr_code_image')->nullable();
      $table->string('qr_code_image_ext', 5)->nullable();
      $table->string('product_type')->nullable();
      $table->string('model_no_item_no')->nullable();
      $table->string('paid_price', 11)->nullable();
      $table->string('delivery_charge', 10)->default('0');
      $table->string('express_delivery_charges', 10)->default('0');
      $table->enum('delivery_status', ['0', '1'])->default('0')->comment('0 => "Free shipping", 1 => "Calculate shipping"');
      $table->string('shipping_place_id')->nullable();
      $table->string('shipping_place_name')->nullable();
      $table->bigInteger('quantity')->default(1);
      $table->unsignedInteger('max_order_quantity')->default(5);
      $table->unsignedInteger('return_days')->default(1);
      $table->enum('order_type', ['1', '2', '3', '4'])->default('4')->comment('1 => "Only Replace", 2 => "Only Return", 3 => "Both return and replace", 4 => "No return and replace"');
      $table->enum('cancel_order_type', ['1', '2'])->default('1')->comment('1 => "Before shipping", 2 => "Cancellation period"');
      $table->unsignedInteger('cancel_order_days')->nullable();
      $table->string('delivery_type')->nullable();
      $table->string('product_image_path')->nullable();
      $table->string('product_thumb_path')->nullable();
      $table->string('product_video_path')->nullable();
      $table->text('3d_product_file')->nullable();
      $table->string('product_document_attachment', 500)->nullable();
      $table->unsignedBigInteger('publisher_application_id')->nullable();
      $table->text('checkout_qr_code_image')->nullable();
      $table->string('checkout_qr_code_image_ext', 5)->nullable();
      $table->text('machine_checkout_qr_code_image')->nullable();
      $table->string('machine_checkout_qr_code_image_ext', 5)->nullable();
      $table->enum('featured_product_id', ['0', '1'])->nullable();
      $table->text('wishlist')->nullable();
      $table->text('stock_notify_users')->nullable();
      $table->unsignedInteger('stock_notify_before_qnt')->nullable();
      $table->enum('stock_notify_send', ['0', '1'])->default('0')->comment('0 => "Not Sent", 1 => "Sent"');
      $table->unsignedBigInteger('delivery_person_id')->nullable();
      $table->string('is_public', 1)->default('N')->comment('Y => "Public", N => "Private"');
      $table->text('shipping_service')->default('STANDARD_OVERNIGHT');
      $table->text('pickup_type')->default('DROPOFF_AT_FEDEX_LOCATION');
      $table->text('weight')->default('{"weight":{"units":"LB","value":"10"}}');
      $table->unsignedBigInteger('stock')->default(0);
      $table->unsignedInteger('max_buy')->default(5);
      $table->timestamps();
      $table->softDeletes();
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->foreign('category_id')->references('id')->on('marketplace_category')->onDelete('cascade');
      $table->foreign('sub_category_id')->references('id')->on('marketplace_sub_category')->onDelete('cascade');
      // $table->foreign('store_id')->references('id')->on('marketplace_stores')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('marketplace_products');
  }
};
