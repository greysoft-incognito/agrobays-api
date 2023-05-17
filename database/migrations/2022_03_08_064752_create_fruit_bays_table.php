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
        Schema::create('fruit_bays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fruit_bay_category_id')->constrained('fruit_bay_categories')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price')->default(0.00);
            $table->decimal('fees')->default(0.00);
            $table->string('image', 550)->nullable();
            $table->json('bag')->nullable();
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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('fruit_bays');
    }
};
