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
        Schema::table('food', function (Blueprint $table) {
            if (! Schema::hasColumn('food', 'available')) {
                $table->boolean('available')->after('price')->default(true);
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'custom_foodbag')) {
                $table->boolean('custom_foodbag')->after('interval')->default(false);
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'customizable')) {
                $table->boolean('customizable')->after('status')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('food', function (Blueprint $table) {
            if (Schema::hasColumn('food', 'available')) {
                $table->dropColumn('available');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'custom_foodbag')) {
                $table->dropColumn('custom_foodbag');
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'customizable')) {
                $table->dropColumn('customizable');
            }
        });
    }
};
