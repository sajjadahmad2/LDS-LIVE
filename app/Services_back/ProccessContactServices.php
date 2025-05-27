<?php
namespace App\Services;
use App\Models\ProccessContact;
use Carbon\Carbon;
use App\Services\ContactServices;
class ProccessContactServices{
    public function handleProccessContact(array $data , $agent=null,$campaign){
      // dd($data , $agent);
      $type = $data['customData'];
      $contact_id = $data['contact_id'];
      $my_signature = json_encode($data['I have reviewed my application information above, and here is my signature.']);
      //dd($my_signature);
      $checkContact=Contact::where('email',$data['email'])->first();
      if($checkContact){
        \Log::info("Do Nothing");
        return;
      }
      $proccessContact = ProccessContact::where('contact_id', $contact_id)->first();
        $contactData = [
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address1' => $data['address1'] ?? null,
            'tags' => isset($data['tags']) ? json_encode($data['tags']) : null, // Encode as JSON if it's an array
            'full_address' => $data['full_address'] ?? null,
            'country' => $data['country'] ?? null,
            'source' => $data['contact_source'] ?? null,
            'date_added' => isset($data['date_created']) ? \Carbon\Carbon::parse($data['date_created']) : null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'location_id' => $data['location']['id'] ?? null,
            'contact_id' => $contact_id ?? null,
            'location' => isset($data['location']) ? json_encode($data['location']) : null, // Encode as JSON
            'address' => $data['location']['fullAddress'] ?? null,
            'status' => 'Compelete', // Default value
            'user_id'=>$agent->user_id,
            'agent_id'=>$agent->id,
            'campaign_id'=>$campaign->id,
            // Custom data
            'address' => $type['address'] ?? null,
            'city' => $type['city'] ?? null,
            'state' => $type['state'] ?? null,
            'postal_code' => $type['postal_code'] ?? null,
            'trusted_form_ping_url' => $type['trusted_form_ping_url'] ?? null,
            'ip_address' => $type['ip_address'] ?? null,
            'trusted_form_cert_url' => $type['trusted_form_cert_url'] ?? null,
            'your_gender' => $type['your_gender?'] ?? null,
            'social_security' => $type['social_security'] ?? null,
            'marital_status' => $type['marital_status'] ?? null,
            'spouses_first_name' => $type['spouses_first_name'] ?? null,
            'spouses_last_name' => $type['spouses_last_name'] ?? null,
            //'spouse_gender' => $type['spouse_gende'] ?? null,
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
            'my_signature' => $my_signature ?? null,
            'application_informatio_my_signature' => $type['application_informatio_my_signature'] ?? null,
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
        if ($proccessContact) {
            foreach ($contactData as $key => $value) {
                $proccessContact->$key = $value;
            }
            $proccessContact->status = 'Compelete';
            $proccessContact->save();
            \Log::info("yahn se guzar gyaaa");
            $contactServices = new ContactServices;
            $contactServices->handleContact($data, $agent,$campaign);
            \Log::info("Updated ProccessContact with contact ID: {$contact_id}");
        } else {
            \Log::info("yahn ayya");
            $proccessContact = new ProccessContact();
            foreach ($contactData as $key => $value) {
                $proccessContact->$key = $value;
            }
            $proccessContact->save();
            $proccessContact->status = 'Compelete';
            $contactServices = new ContactServices;
            $contactServices->handleContact($data, $agent,$campaign);
            \Log::info("Created new ProccessContact with contact ID: {$contact_id}");
        }
    }
}
