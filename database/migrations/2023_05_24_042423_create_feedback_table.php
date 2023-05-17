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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('thread_id')->nullable()->constrained('feedback')->onUpdate('cascade')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('title')->nullable();
            $table->string('type')->default('suggestion');
            $table->string('image')->nullable();
            $table->string('origin')->nullable();
            $table->text('message')->nullable()->fulltext();
            $table->integer('priority')->default(0);
            $table->string('issue_url')->nullable();
            $table->string('issue_num')->nullable();
            $table->enum('status', ['pending', 'seen', 'reviewing', 'reviewed', 'resolved'])->default('pending');
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
        Schema::dropIfExists('feedback');
    }
};
