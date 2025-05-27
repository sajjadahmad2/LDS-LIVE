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
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('state');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('location_id')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        // Insert predefined list of US states
DB::table('states')->insert([
    ['state' => 'Alabama'],
    ['state' => 'Alaska'],
    ['state' => 'Arizona'],
    ['state' => 'Arkansas'],
    ['state' => 'California'],
    ['state' => 'Colorado'],
    ['state' => 'Connecticut'],
    ['state' => 'Delaware'],
    ['state' => 'Florida'],
    ['state' => 'Georgia'],
    ['state' => 'Hawaii'],
    ['state' => 'Idaho'],
    ['state' => 'Illinois'],
    ['state' => 'Indiana'],
    ['state' => 'Iowa'],
    ['state' => 'Kansas'],
    ['state' => 'Kentucky'],
    ['state' => 'Louisiana'],
    ['state' => 'Maine'],
    ['state' => 'Maryland'],
    ['state' => 'Massachusetts'],
    ['state' => 'Michigan'],
    ['state' => 'Minnesota'],
    ['state' => 'Mississippi'],
    ['state' => 'Missouri'],
    ['state' => 'Montana'],
    ['state' => 'Nebraska'],
    ['state' => 'Nevada'],
    ['state' => 'New Hampshire'],
    ['state' => 'New Jersey'],
    ['state' => 'New Mexico'],
    ['state' => 'New York'],
    ['state' => 'North Carolina'],
    ['state' => 'North Dakota'],
    ['state' => 'Ohio'],
    ['state' => 'Oklahoma'],
    ['state' => 'Oregon'],
    ['state' => 'Pennsylvania'],
    ['state' => 'Rhode Island'],
    ['state' => 'South Carolina'],
    ['state' => 'South Dakota'],
    ['state' => 'Tennessee'],
    ['state' => 'Texas'],
    ['state' => 'Utah'],
    ['state' => 'Vermont'],
    ['state' => 'Virginia'],
    ['state' => 'Washington'],
    ['state' => 'West Virginia'],
    ['state' => 'Wisconsin'],
    ['state' => 'Wyoming'],
    ['state' => 'District of Columbia'], // Added D.C. for completeness
]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
