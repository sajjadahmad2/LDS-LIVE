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
        Schema::create('agent_lead_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->onDelete('cascade');
            $table->enum('lead_type', ['ACA', 'Medicare', 'Expense']);
            $table->text('consent')->nullable();
            $table->string('npm_number')->nullable();
            $table->integer('total_limit')->nullable();
            $table->integer('monthly_limit')->nullable();
            $table->integer('daily_limit')->nullable();
            $table->string('cross_link')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('agent_lead_types');
    }
};
