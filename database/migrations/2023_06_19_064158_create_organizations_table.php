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
        Schema::create('cooperatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('website')->nullable();
            $table->string('classification')->nullable()->default('agriculture');
            $table->string('address')->nullable();
            $table->string('lga')->nullable();
            $table->string('state')->nullable();
            $table->text('about')->nullable()->fulltext();
            $table->string('image')->nullable();
            $table->string('cover')->nullable();
            $table->json('meta')->nullable();
            $table->json('settings')->nullable();
            $table->json('publishing')->nullable();
            $table->boolean('verified')->default(false);
            $table->boolean('is_active')->default(false);
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
        Schema::dropIfExists('cooperatives');
    }
};
