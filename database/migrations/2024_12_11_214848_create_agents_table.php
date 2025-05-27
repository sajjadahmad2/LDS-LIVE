<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('destination_location')->nullable();
            $table->text('destination_webhook')->nullable();
            $table->text('consent')->nullable();
            $table->integer('priority')->nullable();
            $table->integer('daily_limit')->nullable();
            $table->integer('monthly_limit')->nullable();
            $table->string('weightage')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('location_id')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
