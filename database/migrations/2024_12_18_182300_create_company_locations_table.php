<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_locations', function (Blueprint $table) {
            $table->id();
            $table->string('location_id'); // Primary key
            $table->string('company_id'); // Foreign key for the company
            $table->string('location_name')->nullable();
            $table->string('location_email')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // Reference for the user
            $table->timestamps();

            // Foreign key constraint

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_locations');
    }
}

