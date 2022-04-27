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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->string('reference')->nullable();
            $table->decimal('amount')->default(0.00);
            $table->decimal('due')->default(0.00);
            $table->decimal('tax')->default(0.00);
            $table->json('items');
            $table->enum('delivery_method', ['delivery', 'pickup'])->default('delivery');
            $table->enum('payment', ['pending', 'rejected', 'complete'])->default('pending');
            $table->enum('status', ['pending', 'rejected', 'shipped', 'delivered'])->default('pending');
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
        Schema::dropIfExists('orders');
    }
};