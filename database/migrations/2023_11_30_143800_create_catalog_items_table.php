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
        Schema::create('vendor_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()
                ->constrained('vendors')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->morphs('catalogable');
            $table->integer('quantity')->nullable()->default(0);
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
        Schema::dropIfExists('vendor_catalog_items');
    }
};
