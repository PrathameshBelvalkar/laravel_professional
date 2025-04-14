<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('qr_skus', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->uuid('sku_id')->nullable();
      $table->string('product_name')->nullable();
      $table->string('brand')->nullable();
      $table->string('stock')->nullable();
      $table->string('sku_code')->nullable();
      $table->string('category')->nullable();
      $table->string('sub_category')->nullable();
      $table->string('material')->nullable();
      $table->string('color')->nullable();
      $table->string('size')->nullable();
      $table->string('weight')->nullable();
      $table->string('price')->nullable();
      $table->string('cost_price')->nullable();
      $table->string('currency')->nullable();
      $table->string('quantity_in_stock')->nullable();
      $table->string('reorder_level')->nullable();
      $table->string('supplier')->nullable();
      $table->string('minimum_order_quantity')->nullable();
      $table->string('short_description')->nullable();
      $table->text('full_description')->nullable();
      $table->text('file_path')->nullable();
      $table->string('sku_pdf')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('qr_skus');
  }
};
