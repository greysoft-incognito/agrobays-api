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
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')
                ->nullable()
                ->after('remember_token');
            $table->foreignId('referrer_id')
                ->nullable()
                ->after('referral_code')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('referral_code');
            $table->dropForeign('users_referrer_id_foreign');
            $table->dropColumn('referrer_id');
        });
    }
};
