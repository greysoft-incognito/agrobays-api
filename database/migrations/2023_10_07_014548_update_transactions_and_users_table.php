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
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'webhook')) {
                $table->json('webhook')->after('status')->nullable();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'pen_code')) {
                $table->string('pen_code')->after('remember_token')->index()->nullable();
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
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'webhook')) {
                $table->dropColumn('webhook');
            };
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'pen_code')) {
                $table->dropIndex('users_pen_code_index');
                $table->dropColumn('pen_code');
            }
        });
    }
};