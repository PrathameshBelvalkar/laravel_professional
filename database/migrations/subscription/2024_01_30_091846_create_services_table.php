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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('key', 255);
            $table->text('description')->nullable();
            $table->string('category', 255)->nullable();
            $table->string('logo', 255)->nullable();
            $table->string('icon', 255)->nullable();
            $table->string('bs_icon', 255)->nullable();
            $table->string('thumbnail', 255)->nullable();
            $table->string('link', 255);
            $table->integer('sequence_no')->nullable();
            $table->enum('status', ['0', '1'])->default("0")->comment('0 => active, 1 => deleted/inactive');
            $table->enum('is_external_app', ['0', '1'])->comment('0 => no, 2 => yes');
            $table->enum('is_free', ['0', '1'])->comment('0 => no, 1 => yes');
            $table->integer('trial_period')->nullable()->comment('null for free service, 0 for no trial period, n for n days trial period');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
