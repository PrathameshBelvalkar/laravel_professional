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
    Schema::create('user_profiles', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id');
      $table->string('first_name', 255)->nullable();
      $table->string('last_name', 255)->nullable();
      $table->unsignedBigInteger('phone_number')->nullable();
      $table->string('address_1')->nullable();
      $table->string('address_2')->nullable();
      $table->string('country')->nullable();
      $table->string('city')->nullable();
      $table->string('state')->nullable();
      $table->date('dob')->nullable();
      $table->json('notifications')->nullable();
      $table->string('profile_image_path')->nullable();
      $table->enum('two_fact_auth', ['0', '1', '2', '3'])->default('0')->comment('0 => Off, 1 => Email, 2 => Phone, 3 => Both');
      $table->enum('two_fact_phone_verified', ["0", "1"])->default("0")->comment('0 => not verified, 1 => verified');
      $table->unsignedBigInteger('two_fact_phone')->nullable();
      $table->enum('two_fact_email_verified', ["0", "1"])->default("0")->comment('0 => not verified, 1 => verified');

      $table->string('two_fact_email', 255)->nullable();
      $table->integer('two_fact_email_otp')->nullable();
      $table->integer('two_fact_phone_otp')->nullable();
      $table->timestamp('two_fact_email_otp_time')->nullable();
      $table->timestamp('two_fact_phone_otp_time')->nullable();
      $table->unsignedBigInteger('country_id')->nullable();
      $table->string("about_me", 255)->nullable();
      $table->json("following")->nullable();
      $table->json("followers")->nullable();
      $table->unsignedBigInteger('pin_code')->nullable();
      $table->string('cover_img', 500)->nullable();
      $table->string('profileIso', 32)->nullable();

      $table->softDeletes();
      $table->timestamps();
      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('user_profiles');
  }
};
