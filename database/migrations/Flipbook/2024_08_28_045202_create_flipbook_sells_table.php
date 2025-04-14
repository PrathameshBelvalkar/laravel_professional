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
        Schema::create('flipbook_sells', function (Blueprint $table) {
            $table->id();
            $table->foreignId("seller_id")->references("id")->on("users")->onDelete("cascade");
            $table->foreignId("buyer_id")->references("id")->on("users")->onDelete("cascade");
            $table->foreignId("publication_id")->references("id")->on("flipbook_publications")->onDelete("cascade");
            $table->foreignId("flipbook_id")->references("id")->on("flipbooks")->onDelete("cascade");
            $table->double("price");
            $table->string("transaction_id");
            $table->string("auger_transaction_id")->nullable();
            $table->json("pages")->nullable();
            $table->string("promo_code")->nullable();
            $table->string("currency")->nullable();
            $table->string("path")->nullable();
            $table->string("thumbnail_path")->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flipbook_sells');
    }
};
