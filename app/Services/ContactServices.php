<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\models\Agent;
use App\Jobs\SendGhlJob;

class ContactServices
{
    public function handleContact(array $data, $agent=null,$campaign)
    {
        $type = $data['customData'] ?? [];
        $contact_id = $data['contact_id'] ?? null;
        $my_signature = json_encode($data['I have reviewed my application information above, and here is my signature.'] ?? null);

        // Check if contact ID exists
        if (!$contact_id) {
            Log::error('Missing contact ID in data.');
            return;
        }

        $contact = Contact::where('email', $data['email'])->first();
        $agent = Agent::where('id', $agent->id)->first();
       // dd($agent);
        // Prepare contact data
        $contactData = [
            // General details
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address1' => $data['address1'] ?? null,
            'tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
            'full_address' => $data['full_address'] ?? null,
            'country' => $data['country'] ?? null,
            'source' => $data['contact_source'] ?? null,
            'date_added' => isset($data['date_created']) ? Carbon::parse($data['date_created']) : null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'location_id' => $data['location']['id'] ?? null,
            'contact_id' => $contact_id,
            'location' => isset($data['location']) ? json_encode($data['location']) : null,
            'address' => $data['location']['fullAddress'] ?? null,
            'user_id'=>$agent ? $agent->user_id :null ,
            'agent_id'=>$agent ? $agent->id :null,
            'campaign_id'=>$campaign->id,
            // Custom data
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
            'contact_json' => base64_encode(json_encode($data)),
        ];
         //dd($contactData);
        // Update or create contact
        if ($contact) {
            foreach ($contactData as $key => $value) {
                $contact->$key = $value;
            }
            $contact->save();
             \Log::info("Updated  old Contact  in contact Service with contact ID: {$contact_id} No need to Send ");
            //$this->forwardContactData($contact, $agent,$campaign,$contactData);
        } else {
            $contact = new Contact();
            foreach ($contactData as $key => $value) {
                $contact->$key = $value;
            }
            $contact->save();
            \Log::info("Created new Contact  with contact ID: {$contact_id} With status NOT SENT");
            $this->forwardContactData($contact, $agent,$campaign,$contactData);

        }
    }

    private function forwardContactData($contact, $agent,$campaign,$contactData)
    {
        if (!empty($agent->destination_webhook)) {
            Http::post($agent->destination_webhook, $contact->toArray());
        } elseif (!empty($agent->destination_location)) {
            SendGhlJob::dispatch($contact->toArray(), $agent);
            return;
        }
    }

}
