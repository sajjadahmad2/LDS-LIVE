<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address1')->nullable();
            $table->text('tags')->nullable();
            $table->string('full_address')->nullable();
            $table->string('company_name')->nullable();
            $table->string('country')->nullable();
            $table->string('source')->nullable();
            $table->timestamp('date_added')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('dnd')->nullable();
            $table->string('location')->nullable();
            $table->string('name')->nullable();

            $table->string('website')->nullable();
            $table->json('attachments')->nullable();
            $table->string('assigned_to')->nullable();
            $table->json('custom_fields')->nullable();


            $table->string('location_id')->nullable();
            $table->string('contact_id')->nullable(); // Corresponds to `id` in the JSON
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('trusted_form_ping_url')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('trusted_form_cert_url')->nullable();
            $table->string('your_gender')->nullable();
            $table->string('social_security')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('spouses_first_name')->nullable();
            $table->string('spouses_last_name')->nullable();

            $table->string('spouse_gende')->nullable();
            $table->string('spouse_date_of_birth')->nullable();
            $table->string('do_you_want_to_enroll_spouse_as_well')->nullable();
            $table->string('spouse_ssn')->nullable();
            $table->string('tax_dependents_typically_children')->nullable();
            $table->string('number_of_tax_dependants_typically_children')->nullable();
            $table->string('wish_to_enroll_your_dependents')->nullable();
            $table->string('tax_dependants_date_of_births')->nullable();
            $table->string('disqualify_lead')->nullable();

            $table->string('company_name_if_self_employed')->nullable();
            $table->string('projected_annual_income')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('application_informatio_my_signature')->nullable();
            $table->string('plan_name')->nullable();
            $table->string('plan_carrier_name')->nullable();
            $table->string('plan_id')->nullable();
            $table->string('plan_type')->nullable();
            $table->string('brochure_url')->nullable();
            $table->longText('my_signature')->nullable();
            $table->string('benefits_url')->nullable();
            $table->string('selected_plan_image')->nullable();
            $table->longText('signature')->nullable();
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('status', ['Not Sent', 'Sent'])->default('Not Sent');
            $table->longtext('contact_json')->nullable();
            // Foreign key constraints
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
}

