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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table
                ->foreignId('cooperative_id')
                ->after('food_bag_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table
                ->foreignId('sender_id')
                ->after('user_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');
            $table
                ->foreignId('cooperative_id')
                ->after('sender_id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');

            // Update the user_id column to be nullable
            $table
                ->foreignId('user_id')
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['cooperative_id']);
            $table->dropColumn('cooperative_id');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropForeign(['cooperative_id']);
            $table->dropColumn('cooperative_id');
            $table
                ->foreignId('user_id')
                ->nullable(false)
                ->change();
        });
    }
};
