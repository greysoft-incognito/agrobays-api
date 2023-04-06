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
        Schema::table('food', function (Blueprint $table) {
            // Drop food_bag_id column only if it exists
            if (Schema::hasColumn('food', 'food_bag_id')) {
                // Disable foreign key constraints
                Schema::disableForeignKeyConstraints();
                // Drop foreign key constraint if it exists
                $table->dropForeign(['food_bag_id']);
                $table->dropColumn('food_bag_id');
                // Enable foreign key constraints
                Schema::enableForeignKeyConstraints();
            }
            if (!Schema::hasColumn('food', 'weight')) {
                $table->integer('weight')->default(1)->after('description');
            } else {
                // If the column exists, empty it first
                DB::table('food')->update(['weight' => 1]);
                $table->integer('weight')->default(1)->change()->after('description');
            }
            $table->string('unit')->default('kg')->after('weight');
            $table->decimal('price', 8, 2)->default(0.00)->after('unit');
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
            // Disable foreign key constraints
            if (!Schema::hasColumn('food', 'food_bag_id')) {
                Schema::disableForeignKeyConstraints();
                // Add food_bag_id column only if it doesn't exist
                $table->foreignId('food_bag_id')->nullable()->after('id')->constrained('food_bags')->onUpdate('cascade')->onDelete('cascade');
                // Enable foreign key constraints
                Schema::enableForeignKeyConstraints();
            }
            if (!Schema::hasColumn('food', 'weight')) {
                $table->string('weight')->after('description');
            } else {
                // If the column exists, empty it first
                $table->string('weight')->change()->after('description');
            }
            $table->dropColumn('unit')->after('weight');
            $table->dropColumn('price')->after('unit');
        });
    }
};