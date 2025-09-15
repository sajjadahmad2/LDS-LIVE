<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaign_agents', function (Blueprint $table) {
            $table->integer('priority')->nullable()->after('weightage');
            $table->string('publisher_id')->nullable()->after('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_agents', function (Blueprint $table) {
            //
        });
    }
};
