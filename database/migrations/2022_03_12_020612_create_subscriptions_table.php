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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('plans')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('food_bag_id')->constrained('food_bags')->onUpdate('cascade')->onDelete('cascade');
            $table->decimal('fees_paid')->default(0.00);
            $table->enum('delivery_method', ['delivery', 'pickup'])->default('delivery');
            $table->enum('status', ['pending', 'active', 'complete', 'withdraw', 'closed'])->default('pending');
            $table->string('interval')->nullable()->default('manual');
            $table->timestamp('next_date')->nullable();
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
        Schema::dropIfExists('subscriptions');
    }
};
