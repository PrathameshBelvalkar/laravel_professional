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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("to_user_id");
            $table->unsignedBigInteger("from_user_id");
            $table->enum("module", ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19'])->comment("1 => account 2 => qr 3 => support 4 => streaming 5 => marketplace 6 => calendar 7 => storage 8 => wallet 9 => mail 10 => game 11 => talk 12 => tv 13 => coin-exchange 14 => admin 15 => three_d 16 => publisher 17 => site 18 => community 19 => connect");
            $table->enum("seen", ['0', '1'])->default("0")->comment("0=> not seen 1 => seen");
            $table->string("link")->nullable()->comment("link to redirect");
            $table->string("title", 1000);
            $table->text("description")->nullable();
            $table->unsignedBigInteger("reference_id")->nullable()->comment("reference_id is table_id from respective module table");
            $table->enum("is_admin", ['0', '1'])->default("0")->comment("0=> no 1 => yes");
            $table->softDeletes();
            $table->timestamps();
            $table->foreign('to_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('from_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
