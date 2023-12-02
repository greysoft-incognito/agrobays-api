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
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'express_delivery')) {
                $table->boolean('express_delivery')->after('items')->default(false);
            }
        });

        Schema::table('dispatches', function (Blueprint $table) {
            if (! Schema::hasColumn('dispatches', 'placed_at')) {
                $table->timestamp('placed_at')->after('status')->nullable();
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
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'express_delivery')) {
                $table->dropColumn('express_delivery');
            };
        });

        Schema::table('dispatches', function (Blueprint $table) {
            if (Schema::hasColumn('dispatches', 'placed_at')) {
                $table->dropColumn('placed_at');
            }
        });
    }
};