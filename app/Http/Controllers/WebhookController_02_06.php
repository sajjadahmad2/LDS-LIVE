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
use App\Services\ProccessContactServices;
use App\Services\ReserveContactServices;
use Illuminate\Support\Facades\Http;
use App\Jobs\ProcessWebhookData;
use App\Jobs\ProccessContactJob;
class WebhookController extends Controller
{
    public function getAgentConsent(Request $request)
    {
        // Validate the email input
        $validated = $request->validate([
            'email' => 'required|email',
        ]);
        $proccessContact = ProccessContact::where('email',$validated['email'])->first();
        $agent = Agent::where('id', $proccessContact->agent_id ?? '')->first();
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }
        return response()->json([
            'success' => true,
            'agent_data' => $agent,
        ]);
    }

    public function handleWebhookUrl(Request $request, $location_id, $campaign_id)
    {
        //dd($request->all(), $location_id, $campaign_id);
        $data = $request->all();
        $contact_id = $data['contact_id'] ?? null;
        $campaign_id = base64_decode($campaign_id);
        //\Log::info($campaign_id);
        ProcessWebhookData::dispatch($data, $location_id, $campaign_id);

        return response()->json(['message' => 'Webhook received. Processing in background.'], 202);
        $mainCampaign = Campaign::find($campaign_id);
        if (!$mainCampaign) {
            if (!is_null($contact_id)) {
                \Log::info('Campaign  Not Provied Sent to Reserve');
                $reserveContactServices = new ReserveContactServices();
                $reserveContactServices->handleReserveContact($data, $agent, $mainCampaign);
            }
            return response()->json(['error' => 'Campaign not found'], 404);
        }
        // Extract contact data from the request
        $contactData = $request->all();
        // Check if the state exists in the contact data
        if (!isset($data['state'])) {
            if (!is_null($contact_id)) {
                \Log::info('State Not Provied Sent to Reserve');
                $reserveContactServices = new ReserveContactServices();
                $reserveContactServices->handleReserveContact($data, $agent, $mainCampaign);
            }
            return response()->json(['error' => 'State not provided'], 400);
        }

        $state = strtolower(trim($data['state']));
        $user = User::where('location_id', $location_id)->first();
        // dd($user);
        if (!$user) {
            if (!is_null($contact_id)) {
                \Log::info('user Not found Sent to Reserve');
                $reserveContactServices = new ReserveContactServices();
                $reserveContactServices->handleReserveContact($data, $agent, $mainCampaign);
            }
            return response()->json(['error' => 'User not found for location'], 404);
        }
        $user_id = $user->id;
        $currentMonth = Carbon::now('America/Chicago')->month;
        $currentDate = Carbon::now('America/Chicago')->format('Y-m-d');
        // Find agents for the given state
        $agents = Agent::whereHas('states', function ($query) use ($state) {
            $query->where(function ($query) use ($state) {
                $query->where(DB::raw('TRIM(LOWER(state))'), $state)
                      ->orWhere(DB::raw('TRIM(LOWER(short_form))'), $state);
            });
        })
            ->withCount([
                // Monthly contacts count
                'contacts as monthly_contacts_count' => function ($query) use ($user_id, $currentMonth) {
                    $query->where('user_id', $user_id)->where('status', 'Sent')->whereMonth('created_at', $currentMonth);
                },
                // Daily contacts count
                'contacts as daily_contacts_count' => function ($query) use ($user_id, $currentDate) {
                    $query->where('user_id', $user_id)->where('status', 'Sent')->whereDate('created_at', $currentDate);
                },
                // Total contacts count
                'contacts as total_contacts_count' => function ($query) use ($user_id) {
                    $query->where('user_id', $user_id)->where('status', 'Sent');
                },
            ])
            ->orderBy('priority', 'asc')
            ->orderByRaw('(monthly_limit - COALESCE(monthly_contacts_count, 0)) desc')
            ->orderByRaw('(daily_limit - COALESCE(daily_contacts_count, 0)) desc')
            ->orderByRaw('(total_limit - COALESCE(total_contacts_count, 0)) desc')
            ->get();
       // \Log::info(['agent data' => $agents]);
        //dd($agents);
        if ($agents->isEmpty()) {
            if (!is_null($contact_id)) {
                \Log::info('NO agent Found or Limit Reached  Sent to Reserve');
                $reserveContactServices = new ReserveContactServices();
                $reserveContactServices->handleReserveContact($data, $agent, $mainCampaign);
            }
            return response()->json(['error' => 'No agents found for the state'], 404);
        }
        //dd($agents);
        $filteragents=[];
        $weightagefull=true;
        foreach ($agents as $agent ) {
            // Check total limit
            $total = true;
            $monthly = true;
            $daily = true;
            $totalContactsCount = Contact::where('agent_id', $agent->id)
                ->where('user_id', $user_id)
                ->count();
            if ($totalContactsCount >= $agent->total_limit) {
                $total = false; // Skip this agent if total limit is reached
            }

            // Check monthly limit
            $monthlyContactsCount = Contact::where('agent_id', $agent->id)
                ->where('user_id', $user_id)
                ->whereMonth('created_at', $currentMonth)
                ->count();
            if ($monthlyContactsCount >= $agent->monthly_limit) {
                $monthly = false; // Skip this agent if monthly limit is reached
            }

            // Check daily limit
            $dailyContactsCount = Contact::where('agent_id', $agent->id)
                ->where('user_id', $user_id)
                ->whereDate('created_at', $currentDate)
                ->count();
            if ($dailyContactsCount >= $agent->daily_limit) {
                $daily = false; // Skip this agent if daily limit is reached
            }
            if ($total && $monthly && $daily) {
                $filteragents[]=$agent;
                if ($agent->agent_count_weightage < $agent->weightage) {
                    // Process contact and assign it to the agent
                    $proccessContactServices = new ProccessContactServices();
                    $proccessContactServices->handleProccessContact($data, $agent, $mainCampaign);
                    $weightagefull=false;
                }
            }

            // Check contact status and stop if it contains 'Sent'
            $contact = Contact::where('contact_id', $contact_id)->first();
            if ($contact && strpos($contact->status, 'Sent') !== false) {
                break; // Exit the loop if contact is processed successfully
            }
        }

        // $contact = Contact::where('contact_id', $contact_id)->first();
        // if ($contact && strpos($contact->status, 'Not Sent') !== false) {
        //     $reserveContactServices = new ReserveContactServices();
        //     $reserveContactServices->handleReserveContact($data, $agent, $mainCampaign);
        // }
        if(!$contact && $weightagefull && count($filteragents) > 0){
            $agentsCollection = collect($filteragents);
            $agentsCollection->each(function($agent) {
                $agent->agent_count_weightage = 0;
                $agent->save();
            });
            $minPriority = $agentsCollection->min('priority');
            $topAgent = $agentsCollection->filter(function($agent) use ($minPriority) {
                return $agent->priority == $minPriority;
            })->first(); // Get the first agent (in case there are multiple agents with the same weightage)
            $proccessContactServices = new ProccessContactServices();
            $proccessContactServices->handleProccessContact($data, $topAgent, $mainCampaign);
            // Log the result (optional)
        }
        $contact = Contact::where('contact_id', $contact_id)->first();
        if (!$contact) {
            $reserveContactServices = new ReserveContactServices();
            $reserveContactServices->handleReserveContact($data, $agent, $mainCampaign);
            if($weightagefull && count($filteragents) > 0 ){
                $agentsCollection = collect($filteragents);
                $agentsCollection->each(function($agent) {
                    $agent->agent_count_weightage = 0;
                    $agent->save();
                });

            }
        }
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
        //dd($data);
        $contact_id = $data['id'];
        $location_id = $data['locationId'] ?? null;
        $type = $data['type'] ?? null;
        if (in_array($type, ['ContactCreate'])) {
            $proccessContact = ProccessContact::where('email', $data['email'])->first();

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
                'location_id' => $location_id,
                'contact_id' => $contact_id ?? null,
                'location' => isset($data['location']) ? json_encode($data['location']) : null, // Encode as JSON
                'address' => $data['location']['fullAddress'] ?? null,
                'status' => 'In Compelete',
            ];

            //dd($contactData);
            if ($proccessContact) {
                foreach ($contactData as $key => $value) {
                    $proccessContact->$key = $value;
                }
                $proccessContact->save();
                $this->findAgent($proccessContact);
                \Log::info("Updated contact from Webhook contact ID: {$contact_id}");
            } else {
                $proccessContact = new ProccessContact();
                foreach ($contactData as $key => $value) {
                    $proccessContact->$key = $value;
                }
                $proccessContact->save();
                $this->findAgent($proccessContact);
                \Log::info("Created new contact from webhook contact ID: {$contact_id}");
            }

        } else {
            \Log::info("Webhook type not found: {$type}");
            return response()->json(['status' => 'error', 'message' => "Webhook type not found: {$type}"], 400);
        }
    }
    protected function findAgent($proccessContact){
        //dd("404");
        //dd($proccessContact);
        $state = $proccessContact->state;
        //dd($state);
        if(!is_null($state)){
        $user = User::where('location_id', $proccessContact->location_id)->first();
        \Log::info('User for the ' . $proccessContact->location_id );
        $user_id = $user->id ?? null;
        $currentMonth = Carbon::now('America/Chicago')->month;
        $currentDate = Carbon::now('America/Chicago')->format('Y-m-d');

        // Find agents for the given state
        $agents = Agent::whereHas('states', function ($query) use ($state) {
            $query->where(function ($query) use ($state) {
                $query->where(DB::raw('TRIM(LOWER(state))'), $state)
                      ->orWhere(DB::raw('TRIM(LOWER(short_form))'), $state);
            });
        })
            ->withCount([
                // Monthly contacts count
                'contacts as monthly_contacts_count' => function ($query) use ($user_id, $currentMonth) {
                    $query->where('user_id', $user_id)->where('status', 'Sent')->whereMonth('created_at', $currentMonth);
                },
                // Daily contacts count
                'contacts as daily_contacts_count' => function ($query) use ($user_id, $currentDate) {
                    $query->where('user_id', $user_id)->where('status', 'Sent')->whereDate('created_at', $currentDate);
                },
                // Total contacts count
                'contacts as total_contacts_count' => function ($query) use ($user_id) {
                    $query->where('user_id', $user_id)->where('status', 'Sent');
                },
            ])
            ->orderBy('priority', 'asc')
            ->orderByRaw('(monthly_limit - COALESCE(monthly_contacts_count, 0)) desc')
            ->orderByRaw('(daily_limit - COALESCE(daily_contacts_count, 0)) desc')
            ->orderByRaw('(total_limit - COALESCE(total_contacts_count, 0)) desc')
            ->get();
       // \Log::info(['agent data' => $agents]);
        //dd($agents);
        if ($agents->isEmpty()) {

        }
        //dd($agents);
        $filteragents=[];
        $weightagefull=true;
        foreach ($agents as $agent ) {
            // Check total limit
            $total = true;
            $monthly = true;
            $daily = true;
            $totalContactsCount = Contact::where('agent_id', $agent->id)
                ->where('user_id', $user_id)
                ->count();
            if ($totalContactsCount >= $agent->total_limit) {
                $total = false; // Skip this agent if total limit is reached
            }

            // Check monthly limit
            $monthlyContactsCount = Contact::where('agent_id', $agent->id)
                ->where('user_id', $user_id)
                ->whereMonth('created_at', $currentMonth)
                ->count();
            if ($monthlyContactsCount >= $agent->monthly_limit) {
                $monthly = false; // Skip this agent if monthly limit is reached
            }

            // Check daily limit
            $dailyContactsCount = Contact::where('agent_id', $agent->id)
                ->where('user_id', $user_id)
                ->whereDate('created_at', $currentDate)
                ->count();
            if ($dailyContactsCount >= $agent->daily_limit) {
                $daily = false; // Skip this agent if daily limit is reached
            }
            if ($total && $monthly && $daily) {
                $filteragents[]=$agent;
                if ($agent->agent_count_weightage < $agent->weightage) {
                    // Process contact and assign it to the agent
                    $proccessContact->agent_id=$agent->id;
                    $proccessContact->save();
                    $weightagefull=false;
                    break;
                }
            }
        }

        if($weightagefull && count($filteragents) > 0){
            $agentsCollection = collect($filteragents);
            $minPriority = $agentsCollection->min('priority');
            $topAgent = $agentsCollection->filter(function($agent) use ($minPriority) {
                return $agent->priority == $minPriority;
            })->first(); // Get the first agent (in case there are multiple agents with the same weightage)
            $proccessContact->agent_id=$topAgent->id;
            $proccessContact->save();
            // Log the result (optional)
        }
        }
    }
}
