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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('image')->nullable();
            $table->string('username')->unique();
            $table->string('reg_image')->nullable();
            $table->string('id_image')->nullable();
            $table->string('id_type')->nullable()->default('passport');
            $table->string('business_reg')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_email')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('business_city')->nullable();
            $table->string('business_state')->nullable();
            $table->string('business_country')->nullable();
            $table->string('business_address')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('verified')->default(false);
            $table->json('verification_data')->nullable()->default('{}');
            $table->timestamps();
        });

        Schema::table('dispatches', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatches', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()
                    ->after('dispatchable_id')
                    ->constrained('vendors')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            };

            if (! Schema::hasColumn('dispatches', 'data')) {
                $table->json('data')->nullable()->default('{}')->after('last_location');
            };
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dispatches', function (Blueprint $table) {
            if (Schema::hasColumn('dispatches', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            };
            if (Schema::hasColumn('dispatches', 'data')) {
                $table->dropColumn('data');
            };
        });

        Schema::dropIfExists('vendors');
    }
};
