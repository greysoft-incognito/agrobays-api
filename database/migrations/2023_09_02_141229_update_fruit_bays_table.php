<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('fruit_bays', function (Blueprint $table) {
            // Check if descriptino has a fulltext index and alter it if it doesn't
            if (! DB::select("SHOW INDEX FROM fruit_bays WHERE Key_name = 'fruit_bays_description_fulltext'")) {
                $table->text('description')->fulltext()->change();
            }
            if (! Schema::hasColumn('fruit_bays', 'weight')) {
                $table->float('protein')->nullable()->default(0.1)->after('fees');
            }
            if (! Schema::hasColumn('fruit_bays', 'unit')) {
                $table->string('unit')->default('kg')->after('weight');
            }
            if (! Schema::hasColumn('fruit_bays', 'available')) {
                $table->boolean('available')->default(true)->after('unit');
            }
            if (! Schema::hasColumn('fruit_bays', 'no_fees')) {
                $table->boolean('no_fees')->default(false)->after('available');
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
        Schema::table('fruit_bays', function (Blueprint $table) {
            if (Schema::hasColumn('fruit_bays', 'weight')) {
                $table->dropColumn('weight');
            }
            if (Schema::hasColumn('fruit_bays', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('fruit_bays', 'available')) {
                $table->dropColumn('available');
            }
            if (Schema::hasColumn('fruit_bays', 'no_fees')) {
                $table->dropColumn('no_fees');
            }
            if (DB::select("SHOW INDEX FROM fruit_bays WHERE Key_name = 'fruit_bays_description_fulltext'")) {
                $table->dropIndex('fruit_bays_description_fulltext');
            }
        });
    }
};