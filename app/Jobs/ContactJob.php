<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ContactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data, $agent, $campaign;

    public function __construct($data, $agent, $campaign)
    {
        $this->data = $data;
        $this->agent = $agent;
        $this->campaign = $campaign;
    }

    public function handle()
    {
        $type = $this->data['customData'] ?? [];
        $contact_id = $this->data['contact_id'] ?? null;
        $my_signature = json_encode($this->data['I have reviewed my application information above, and here is my signature.'] ?? null);

        if (!$contact_id) {
            Log::error('Missing contact ID in data.');
            return;
        }

        // Attempt to find an existing contact based on contact_id, email, or phone
        $contact = Contact::where('contact_id', $contact_id)
            ->orWhere('email', $this->data['email'] ?? null)
            ->orWhere('phone', $this->data['phone'] ?? null)
            ->first();

        // Prepare contact data
        $contactData = [
            'first_name' => $this->data['first_name'] ?? null,
            'last_name' => $this->data['last_name'] ?? null,
            'email' => $this->data['email'] ?? null,
            'phone' => $this->data['phone'] ?? null,
            'address1' => $this->data['address1'] ?? null,
            'tags' => isset($this->data['tags']) ? json_encode($this->data['tags']) : null,
            'full_address' => $this->data['full_address'] ?? null,
            'country' => $this->data['country'] ?? null,
            'source' => $this->data['contact_source'] ?? null,
            'date_added' => isset($this->data['date_created']) ? Carbon::parse($this->data['date_created']) : null,
            'city' => $this->data['city'] ?? null,
            'state' => $this->data['state'] ?? null,
            'postal_code' => $this->data['postal_code'] ?? null,
            'location_id' => $this->data['location']['id'] ?? null,
            'contact_id' => $contact_id,
            'location' => isset($this->data['location']) ? json_encode($this->data['location']) : null,
            'address' => $this->data['location']['fullAddress'] ?? null,
            'user_id' => $this->agent->user_id ?? null,
            'agent_id' => $this->agent->id ?? null,
            'campaign_id' => $this->campaign->id,
            'trusted_form_ping_url' => $type['trusted_form_ping_url'] ?? null,
            'ip_address' => $type['ip_address'] ?? null,
            'trusted_form_cert_url' => $type['trusted_form_cert_url'] ?? null,
            'your_gender' => $type['your_gender?'] ?? null,
            'social_security' => $type['social_security'] ?? null,
            'marital_status' => $type['marital_status'] ?? null,
            'spouses_first_name' => $type['spouses_first_name'] ?? null,
            'spouses_last_name' => $type['spouses_last_name'] ?? null,
            'spouse_date_of_birth' => $type['spouse_date_of_birth'] ?? null,
            'do_you_want_to_enroll_spouse_as_well' => $type['do_you_want_to_enroll_spouse_as_well'] ?? null,
            'spouse_ssn' => $type['spouse_ssn'] ?? null,
            'tax_dependents_typically_children' => $type['tax_dependents_typically_children'] ?? null,
            'number_of_tax_dependants_typically_children' => $type['number_of_tax_dependants_typically_children'] ?? null,
            'wish_to_enroll_your_dependents' => $type['wish_to_enroll_your_dependents'] ?? null,
            'tax_dependants_date_of_births' => $type['tax_dependants_date_of_births'] ?? null,
            'disqualify_lead' => $type['disqualify_lead'] ?? null,
            'company_name_if_self_employed' => $type['company_name_if_self_employed'] ?? null,
            'projected_annual_income' => $type['projected_annual_income'] ?? null,
            'employment_status' => $type['employment_status'] ?? null,
            'signature' => $type['signature'] ?? null,
            'my_signature' => $my_signature,
            'plan_name' => $type['plan_name'] ?? null,
            'plan_carrier_name' => $type['plan_carrier_name'] ?? null,
            'plan_id' => $type['plan_id'] ?? null,
            'plan_type' => $type['plan_type'] ?? null,
            'brochure_url' => $type['brochure_url'] ?? null,
            'benefits_url' => $type['benefits_url'] ?? null,
            'date_of_birth' => $type['date_of_birth'] ?? null,
            'selected_plan_image' => $type['selected_plan_image'] ?? null,
            'contact_json' => base64_encode(json_encode($this->data)),
        ];

        if ($contact) {
            foreach ($contactData as $key => $value) {
                $contact->$key = $value;
            }
            $contact->save();
             \Log::info("Updated  old in contact table with contact ID: {$contact_id} With status NOT SENT");
            //$this->forwardContactData($contact, $agent,$campaign,$contactData);
            SendGhlJob::dispatch($contact, $this->agent);
        } else {
            $contact = new Contact();
            foreach ($contactData as $key => $value) {
                $contact->$key = $value;
            }
            $contact->save();
            \Log::info("Created new Contact in contact table  with contact ID: {$contact_id} With status NOT SENT");
            SendGhlJob::dispatch($contact, $this->agent);

        }
    }
}
