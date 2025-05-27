<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; // To handle incoming requests
use App\Models\Campaign; // Model for Campaign table
use App\Models\Agent; // Model for Agent table
use App\Models\Contact; // Model for Contact table
use App\Models\User; // Model for User table
use App\models\GhlAuth;
use App\Models\ReserveContact; // Model for ReserveContact table
use App\Models\ProccessContact; // Model for ReserveContact table
use App\Models\CampaignAgent; // Model for the Campaign-Agent mapping
use Illuminate\Support\Facades\Log; // For logging
use Illuminate\Support\Facades\DB; // For database queries
use Carbon\Carbon; // For date and time manipulation
use App\Services\ContactServices;
use Illuminate\Support\Facades\Http;

class WebhookController extends Controller
{
    public function getAgentConsent(Request $request)
    {
        // Validate the email input
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        // Find the agent by email
        $agent = Agent::where('email', $validated['email'])->first();

        // If agent not found, return an error response
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        // Return the consent field data in the response
        return response()->json([
            'success' => true,
            'consent' => $agent->consent,
        ]);
    }
    public function handleWebhookUrl(Request $request, $location_id, $campaign_id)
    {
        //dd($request->all(),$location_id, $campaign_id);
        $campaign_id = base64_decode($campaign_id);
        //dd($campaign_id);
        $campaign = Campaign::find($campaign_id);
        \Log::info(json_encode($request->all()));
        // Check if the campaign exists
        if (!$campaign) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }
        // Extract contact data from the request
        $contactData = $request->all();
        // Check if the state exists in the contact data
        if (!isset($contactData['state'])) {
            return response()->json(['error' => 'State not provided'], 400);
        }

        $state = $contactData['state'];

        // Find the user_id using the location_id
        $user = User::where('location_id', $location_id)->first();
        //dd($user);
        if (!$user) {
            return response()->json(['error' => 'User not found for location'], 404);
        }
        $user_id = $user->id;
        $currentMonth = Carbon::now()->month;
        $currentDate = Carbon::now()->format('Y-m-d');
        // Find agents for the given state
        $agents = Agent::whereHas('states', function ($query) use ($state) {
            $query->where('state', $state);
        })
            ->withCount([
                'contacts as monthly_contacts_count' => function ($query) use ($user_id, $currentMonth) {
                    $query->where('user_id', $user_id)->whereMonth('created_at', $currentMonth);
                },
                'contacts as daily_contacts_count' => function ($query) use ($user_id, $currentDate) {
                    $query->where('user_id', $user_id)->whereDate('created_at', $currentDate);
                },
            ])
            ->orderBy('priority', 'desc')
            ->orderByRaw('(monthly_limit - monthly_contacts_count) desc')
            ->orderByRaw('(daily_limit - daily_contacts_count) desc')
            ->get();
        if ($agents->isEmpty()) {
            return response()->json(['error' => 'No agents found for the state'], 404);
        }

        // find the contact on process contact and f found   update the contact data if not then create the data
        $this->saveContactInProcess($request);
        //After Saving Check the status of process cobntact  if completed


        foreach ($agents as $agent) {
            // Check monthly limit

            $monthlyContactsCount = Contact::where('agent_id', $agent->id)
                ->where('user_id', $user_id)
                ->whereMonth('created_at', now()->month)
                ->count();

            // if ($monthlyContactsCount >= $agent->monthly_limit) {
            //     continue; // Move to the next agent
            // }
            //dd("4040");
            // Check daily limit
            $dailyContactsCount = Contact::where('agent_id', $agent->id)
                ->where('user_id', $user_id)
                ->whereDate('created_at', now()->toDateString())
                ->count();
            if ($dailyContactsCount >= $agent->daily_limit) {
                continue; // Move to the next agent
            }
            //dd("404");
            // Save the contact in the database
            $contact = new Contact();
            $contact->location_id = $location_id;
            $contact->campaign_id = $campaign->id;
            $contact->agent_id = $agent->id;
            $contact->user_id = $user_id;
            $contact->contact_id = $contactData['contact_id'] ?? null;
            $contact->address1 = $contactData['address1'] ?? null;
            $contact->city = $contactData['city'] ?? null;
            $contact->state = $state;
            $contact->company_name = $contactData['company_name'] ?? null;
            $contact->country = $contactData['country'] ?? null;
            $contact->source = $contactData['source'] ?? null;
            $contact->date_added = $contactData['dateAdded'] ?? null;
            $contact->date_of_birth = isset($contactData['date_of_birth']) ? Carbon::parse($contactData['date_of_birth'])->format('Y-m-d') : null;
            $contact->dnd = $contactData['dnd'] ?? false; // Assuming 'dnd' is boolean
            $contact->email = $contactData['email'] ?? null;
            $contact->name = $contactData['name'] ?? null;
            $contact->first_name = $contactData['first_name'] ?? null;
            $contact->last_name = $contactData['last_name'] ?? null;
            $contact->phone = $contactData['phone'] ?? null;
            $contact->postal_code = $contactData['postalCode'] ?? null;
            $contact->tags = isset($contactData['tags']) ? implode(',', $contactData['tags']) : null;
            $contact->website = $contactData['website'] ?? null;
            $contact->attachments = json_encode($contactData['attachments'] ?? []); // Assuming 'attachments' is an array
            $contact->assigned_to = $contactData['assigned_to'] ?? null;
            $contact->custom_fields = json_encode($contactData['customFields'] ?? []);
            $contact->save();
            // Forward the contact data to the destination
            $this->forwardContactData($agent, $contactData);
            return response()->json(['success' => 'Contact assigned successfully'], 200);
        }

        // If no agent matched the criteria, save to the reserve table
        $this->saveToReserveTable($contactData, $campaign->id, $user_id);

        return response()->json(['success' => 'Contact saved to reserve table'], 200);
    }
    private function forwardContactData($agent, $contactData)
    {
        //dd($agent, $contactData);
        if (!empty($agent->destination_webhook)) {
            Http::post($agent->destination_webhook, $contactData);
        } elseif ($agent->destination_location) {
            // Call GHL API
            $response = $this->sendContactToGHL($agent, $contactData);
        }
    }

    private function saveToReserveTable($contactData, $campaign_id, $user_id)
    {
        ReserveContact::create([
            'location_id' => $contactData['location']['id'],
            'campaign_id' => $campaign_id,
            'user_id' => $user_id,
            'contact_data' => json_encode($contactData),
        ]);
    }
    public function sendContactToGHL($agent, $contactData)
    {
        $data = [
            'locationId' => $agent->destination_location,
            'firstName' => $contactData['first_name'] ?? null,
            'lastName' => $contactData['last_name'] ?? null,
            'email' => $contactData['email'] ?? null,
            'phone' => $contactData['phone'] ?? null,
            'tags' => isset($contactData['tags']) ? explode(',', $contactData['tags']) : [], // Convert back to array
            'address1' => $contactData['address1'] ?? null,
            'city' => $contactData['city'] ?? null,
            'state' => $contactData['state'] ?? null,
            'postalCode' => $contactData['postal_code'] ?? null,
            'country' => $contactData['country'] ?? null,
            'dateOfBirth' => $contactData['date_of_birth'] ?? null,
            'customFields' => json_decode($contactData['custom_fields'] ?? '{}', true), // Decode JSON into array
        ];
        $url = 'contacts/';
        $location = User::where('id', $agent->user_id)->first();
        $location = User::where('id', $agent->user_id)->first();
        $token = GhlAuth::where('location_id', $location->location_id)->first();
        // dd($token);
        $response = \App\Helpers\CRM::CrmV2($location->id, $url, 'POST', $data, [], false, $location->location_id, $token);
        \Log::error('GHL API Call failed.', ['url' => $url, 'response' => $response]);
        return ['error' => 'Failed to make API call', 'details' => $response];
    }
    public function ContactWebhook(Request $request)
    {
        $data = $request->all();
        $type = $data['type'] ?? null;

        if (in_array($type, ['ContactCreate', 'ContactUpdate', 'ContactDelete', 'ContactTagUpdate', 'ContactDndUpdate'])) {
            $contactService = new ContactServices();
            $contactService->handleContact($data);
            return response()->json(['status' => 'success', 'type' => $type]);
        } else {
            Log::info("Webhook type not found: {$type}");
            return response()->json(['status' => 'error', 'message' => "Webhook type not found: {$type}"], 400);
        }
    }
    public function saveContactInProcess(Request $request)
    {
        $data = $request->all();
        $type = $data['customData'];
        $contact_id = $data['contact_id'];

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
            'status' => 'In Compelete', // Default value

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
        //dd($contactData);
        if ($proccessContact) {
            foreach ($contactData as $key => $value) {
                $proccessContact->$key = $value;
            }
            $proccessContact->status = 'Compelete';
            $proccessContact->save();
            \Log::info("Updated ProccessContact with contact ID: {$contact_id}");
        } else {
            $proccessContact = new ProccessContact();
            foreach ($contactData as $key => $value) {
                $proccessContact->$key = $value;
            }
            $proccessContact->save();
            \Log::info("Created new ProccessContact with contact ID: {$contact_id}");
        }
    }

}
