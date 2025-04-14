<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    protected $table = "users";
    public function up(): void
    {
        if (!Schema::hasTable($this->table)) {
            // If not, create the table
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('username')->unique();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->unique();
                $table->double("account_tokens")->default(0);
                $table->double("reserved_tokens")->default(0);
                $table->string('password');
                $table->enum('register_type', ['platform', 'google', 'facebook', 'apple']);
                $table->unsignedBigInteger('role_id')->default("3");
                $table->double("storage")->default("500")->comment('storage in mb');
                $table->text('verify_code')->nullable();
                $table->text('verify_token')->nullable();
                $table->enum('verify_email', ['0', '1'])->default('0')->comment('0 => Unverified, 1 => Verified');
                $table->enum('status', ['0', '1', '2'])->default('0')->comment('0=> Verified, 1=> Unverified, 2=> Suspended ');
                $table->timestamp('verify_link_time')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('public_key')->nullable();
                $table->string('private_key')->nullable();
                $table->enum('forgot_verify', ['0', '1'])->default('0')->comment('0 => Unverified, 1 => Verified');
                $table->unsignedBigInteger('package_subscription_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->foreign('role_id')->references('id')->on('roles');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
