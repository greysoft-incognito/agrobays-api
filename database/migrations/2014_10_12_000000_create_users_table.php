<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('image')->nullable();
            $table->enum('gender', ['male', 'female', 'non-binary', 'transgender', 'bisexual', 'other'])->default('male');
            $table->string('nextofkin')->nullable();
            $table->string('nextofkin_relationship')->nullable();
            $table->string('nextofkin_phone')->nullable();
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->json('address')->nullable();
            $table->json('city')->nullable();
            $table->json('state')->nullable();
            $table->json('country')->nullable();
            $table->enum('role', ['admin', 'dispatch', 'manager', 'user'])->default('user');
            $table->string('email_verify_code')->nullable();
            $table->timestamp('last_attempt')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->rememberToken();
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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('users');
    }
};
