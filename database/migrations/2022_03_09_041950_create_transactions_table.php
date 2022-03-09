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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->index();
            $table->morphs('transactable');
            $table->string('reference')->nullable();
            $table->string('method')->nullable();
            $table->decimal('amount')->default(0.00);
            $table->decimal('due')->default(0.00);
            $table->decimal('tax')->default(0.00);
            $table->enum('status', ['pending', 'complete', 'rejected'])->default('complete');
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
        Schema::dropIfExists('transactions');
    }
};
