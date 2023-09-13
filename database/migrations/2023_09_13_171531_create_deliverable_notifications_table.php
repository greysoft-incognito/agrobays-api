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
        Schema::create('deliverable_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onUpdate('cascade');
            $table->string('type')->nullable()->default('mail');
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->bigInteger('count_sent')->default(0);
            $table->bigInteger('count_failed')->default(0);
            $table->bigInteger('count_pending')->default(0);
            $table->boolean('draft')->default(false);
            $table->json('recipient_ids')->nullable()->default('[]');
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
        Schema::dropIfExists('deliverable_notifications');
    }
};
