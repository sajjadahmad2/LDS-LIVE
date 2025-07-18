<?php
namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookData;
use App\Jobs\ProcessWebhookDataLead; // To handle incoming requests
use App\Models\Agent;                // Model for Campaign table
use App\Models\Campaign;             // Model for Agent table
use App\Models\CampaignAgent;        // Model for Contact table
use App\Models\Contact;              // Model for User table
use App\Models\Log as Logs;          // Model for ReserveContact table
use App\Models\ProccessContact;      // Model for ReserveContact table
use App\Models\AgentCarrierType;      // Model for Agent Carrier Type table
use App\Models\ReserveContact;       // Model for the Campaign-Agent mapping
use App\Models\SaveJobLog;          // For logging
use App\Models\User;                 // For database queries
use Carbon\Carbon;                   // For date and time manipulation
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    // public function getAgentConsent(Request $request)
    // {
    //     // Validate the email input
    //     $validated = $request->validate([
    //         'email' => 'required|email',
    //     ]);
    //     $proccessContact = ProccessContact::where('email', $validated['email'])->first();
    //     $agent           = Agent::where('id', $proccessContact->agent_id ?? '')->first();
    //     if (! $agent) {
    //         return response()->json(['error' => 'Agent not found'], 404);
    //     }
    //     return response()->json([
    //         'success'    => true,
    //         'agent_data' => $agent,
    //     ]);
    // }
public function getAgentConsent(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);
        $proccessContact = ProccessContact::where('email', $validated['email'])->first();
        if (!$proccessContact) {
            return response()->json(['error' => 'Contact with this email is not found'], 404);
        }
        $agent = Agent::where('id', $proccessContact->agent_id ?? '')->first();
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }
        $formattedCarrierTypes = [];
        $carrierTypes = AgentCarrierType::select('carrier_type')->where('agent_id', $agent->id)->get();
        foreach ($carrierTypes as $type) {
            $formattedCarrierTypes[] = [$type->carrier_type];
        }
        $agent->carrierType = $formattedCarrierTypes;
        //dd($agent);

        return response()->json([
            'success' => true,
            'agent_data' => $agent,
        ]);
    }
    public function getAgentCarrierTypes(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);
        $agent = Agent::where('email', $validated['email'])->first();
        $formattedCarrierTypes = [];
        $carrierTypes = AgentCarrierType::select('carrier_type')->where('agent_id', $agent->id)->get();
        foreach ($carrierTypes as $type) {
            $formattedCarrierTypes[] = [$type->carrier_type];
        }
        $agent->carrierType = $formattedCarrierTypes;
        //dd($agent);
        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }
        return response()->json([
            'success' => true,
            'agent_carriers' => $formattedCarrierTypes,
        ]);
    }
    public function handleWebhookUrl(Request $request, $campaignIdParam)
    {
        $data           = $request->all();
        $type           = $data['type'] ?? null;
        $customType     = $data['customData']['type'] ?? null;
        $contactId      = $data['contact_id'] ?? null;
        $state          = $data['state'] ?? null;
        $campaignId     = base64_decode($campaignIdParam);
        $requiredFields = ['email', 'state', 'type'];
        $dataKeys       = array_keys($data);
        appendJobLog($contactId, 'Contact came from source ' . ($data['contact_source'] ?? null) . ' having the type ' . ($type ?? null) . ' and the custom Type ' . ($customType ?? null));
        if ($type === 'ContactCreate') {
            return $this->handleContactCreateType($data, $dataKeys, $requiredFields, $contactId, $state, $request, $campaignId);
        }

        if ($customType === 'ContactCreate') {
            return $this->handleCustomContactCreateType($contactId, $state, $request, $campaignId);
        }

        return $this->handleSurveySubmission($contactId, $state, $data, $campaignId);
    }

    protected function handleContactCreateType($data, $dataKeys, $requiredFields, $contactId, $state, $request, $campaignId)
    {
        if (count($dataKeys) === count($requiredFields) && empty(array_diff($dataKeys, $requiredFields))) {
            // \Log::info('Contact from Survey Script', [
            //     'email' => $data['email'],
            //     'state' => $data['state'],
            // ]);

            appendJobLog($contactId, 'ContactCreate from Survey Script');

            $this->contactWebhook($request, $campaignId);
            return response()->json(['message' => 'Webhook received with exact fields'], 202);
        }

        // \Log::info('Contact from App', ['contact_id' => $contactId, 'state' => $state]);
        appendJobLog($contactId, 'ContactCreate from App');
        return response()->json(['message' => 'Webhook received'], 202);
    }

    protected function handleCustomContactCreateType($contactId, $state, $request, $campaignId)
    {
        // \Log::info('Contact creation from Automation', ['contact_id' => $contactId, 'state' => $state]);
        appendJobLog($contactId, 'Custom ContactCreate from Automation');

        $this->contactWebhook($request, $campaignId);
        return response()->json(['message' => 'Webhook received'], 202);
    }

    protected function handleSurveySubmission($contactId, $state, $data, $campaignId)
    {
        // \Log::info('Survey Submission from Automation', ['contact_id' => $contactId, 'state' => $state]);

        Logs::updateOrCreate(
            ['contact_id' => $contactId],
            [
                'contact_id' => $contactId,
                'name'       => $data['first_name'] ?? 'No name',
                'email'      => $data['email'] ?? null,
                'state'      => $state,
                'reason'     => 'Only save this webhook data ' . $data['contact_source'] ?? null,
                'message'    => json_encode($data),
            ]
        );

        appendJobLog($contactId, 'Survey Submitted');

        ProcessWebhookData::dispatch($data, $campaignId);

        return response()->json(['message' => 'Webhook received. Processing in background.'], 202);
    }

    public function ContactWebhook($request, $camid = null)
    {
        $data = $request->all();

        $contact_id  = $data['contact_id'] ?? null;
        $location_id = $data['location']['id'] ?? null;
        $type        = $data['type'] ?? null;
        $email       = $data['email'] ?? null;
        if (is_null($camid)) {
            \Log::info("Webhook Campaign id not found: {$camid}");
            return response()->json(['status' => 'error', 'message' => "Webhook campaign id not found: {$camid}"], 400);
        }

        //$user=User::where('role', 1)->pluck('location_id')->toArray();
        //&& in_array($location_id, $user)
        appendJobLog($contact_id, 'Contact Came For Saving in Process Contact with Email : ' . $email);
        if (! is_null($email)) {
            $proccessContact = ProccessContact::where('email', $data['email'])->first();
            // $user=User::where('location_id', $location_id)->first();
            $contactData = [
                'first_name'   => $data['first_name'] ?? null,
                'last_name'    => $data['last_name'] ?? null,
                'email'        => $data['email'] ?? null,
                'phone'        => $data['phone'] ?? null,
                'address1'     => $data['address1'] ?? null,
                'tags'         => isset($data['tags']) ? json_encode($data['tags']) : null, // Encode as JSON if it's an array
                'full_address' => $data['full_address'] ?? null,
                'country'      => $data['country'] ?? null,
                'source'       => $data['contact_source'] ?? null,
                'date_added'   => isset($data['date_created']) ? \Carbon\Carbon::parse($data['date_created']) : null,
                'city'         => $data['city'] ?? null,
                'state'        => $data['state'] ?? null,
                'postal_code'  => $data['postal_code'] ?? null,
                'location_id'  => $location_id,
                'contact_id'   => $contact_id ?? null,
                'location'     => isset($data['location']) ? json_encode($data['location']) : null, // Encode as JSON
                'address'      => $data['location']['fullAddress'] ?? null,
                'status'       => 'In Compelete',
                // 'user_id' => $user->id ?? null,
            ];

            if ($proccessContact) {
                // foreach ($contactData as $key => $value) {
                //     $proccessContact->$key = $value;
                // }
                // $proccessContact->save();
                // \Log::info("Send this Campaign id to the Find Agent: {$camid}");
                // $this->findAgent($proccessContact, $camid);
                // // dd($proccessContact);
                // \Log::info("Updated contact from Webhook contact ID: {$contact_id}");
            } else {
                $proccessContact = new ProccessContact();
                foreach ($contactData as $key => $value) {
                    $proccessContact->$key = $value;
                }
                $proccessContact->save();
                \Log::info("Send this Campaign id to the Find Agent: {$camid}");
                $this->findAgent($proccessContact, $camid);
                \Log::info("Created new contact from webhook contact Email: {$email}");
            }
            return response()->json(['status' => 'success', 'message' => "webhook receieved and processed"], 200);
        } else {
            \Log::info("Webhook type not found: {$type}");
            return response()->json(['status' => 'error', 'message' => "Webhook type not found: {$type}"], 400);
        }
    }
    protected function findAgent($proccessContact, $camid = null)
    {
        $state      = $proccessContact->state;
        $contact_id = $proccessContact->contact_id;
        \Log::info('Agent Find for ' . $state . ' and Campaign ' . $camid);
        appendJobLog($contact_id, 'Contact Came to Agent Find for ' . $state . ' and Campaign ' . $camid);
        if (! is_null($state)) {
            $currentMonth = Carbon::now('America/Chicago')->month;
            $currentDate  = Carbon::now('America/Chicago')->format('Y-m-d');
            $mainCampaign = Campaign::find($camid);
            $agentIds     = CampaignAgent::where('campaign_id', $camid)->pluck('agent_id')->toArray();

            // Fetch agents sorted by priority (asc) and weightage (desc)
            $agents = Agent::whereHas('states', function ($query) use ($state) {
                $query->where(DB::raw('TRIM(LOWER(state))'), $state)
                    ->orWhere(DB::raw('TRIM(LOWER(short_form))'), $state);
            })
                ->whereIn('id', $agentIds)
                ->withCount([
                    'contacts as monthly_contacts_count' => function ($query) use ($currentMonth) {
                        $query->where('status', 'Sent')->whereMonth('created_at', $currentMonth);
                    },
                    'contacts as daily_contacts_count'   => function ($query) use ($currentDate) {
                        $query->where('status', 'Sent')->whereDate('created_at', $currentDate);
                    },
                    'contacts as total_contacts_count'   => function ($query) {
                        $query->where('status', 'Sent');
                    },
                ])
                ->orderBy('priority', 'asc')
                ->orderByDesc('weightage')
                ->get();

            $groupedAgents = $agents->groupBy('priority'); // Group agents by priority
            $agentIdss     = $groupedAgents->map(function ($group) {
                return $group->pluck('id')->toArray();
            });

            \Log::info('Agent Having the State matched in consent for Contact id: ' . $contact_id . ' : ' . json_encode($agentIdss));
            foreach ($groupedAgents as $priority => $priorityAgents) {
                $weightageFull = true;

                foreach ($priorityAgents as $agent) {
                    // Check limits before considering the agent
                    $total   = $agent->total_contacts_count < $agent->total_limit;
                    $monthly = $agent->monthly_contacts_count < $agent->monthly_limit;
                    $daily   = $agent->daily_contacts_count < $agent->daily_limit;

                    if ($total && $monthly && $daily) {

                        if ($agent->agent_count_weightage < $agent->weightage) {
                            // Assign lead to this agent
                            $proccessContact->agent_id = $agent->id;
                            $proccessContact->save();
                            $weightageFull = false;
                            \Log::info('Agent found. Contact dispatched. Agent ID: ' . ($agent->name ?? ''));
                            appendJobLog($contact_id, 'Agent found. Contact dispatched. Agent ID: ' . ($agent->name ?? ''));
                            $agent->increment('agent_count_weightage', 1);
                            return; // Exit function after assignment
                        }
                    }
                }

                // If all agents in this priority group have reached weightage limit, reset weightage count
                if ($weightageFull) {
                    foreach ($priorityAgents as $agent) {
                        // Check limits again before resetting weightage
                        $total   = $agent->total_contacts_count < $agent->total_limit;
                        $monthly = $agent->monthly_contacts_count < $agent->monthly_limit;
                        $daily   = $agent->daily_contacts_count < $agent->daily_limit;

                        if ($total && $monthly && $daily) {
                            $agent->update(['agent_count_weightage' => 0]);
                        }
                    }

                    // Retry assignment after reset
                    foreach ($priorityAgents as $agent) {
                        $total   = $agent->total_contacts_count < $agent->total_limit;
                        $monthly = $agent->monthly_contacts_count < $agent->monthly_limit;
                        $daily   = $agent->daily_contacts_count < $agent->daily_limit;

                        if ($total && $monthly && $daily) {
                            $proccessContact->agent_id = $agent->id;
                            $proccessContact->save();
                            \Log::info('Re-attempting assignment after weightage reset. Agent ID: ' . ($agent->name ?? ''));
                            appendJobLog($contact_id, 'Re-attempting assignment after weightage reset. Agent ID: ' . ($agent->name ?? ''));
                            $agent->increment('agent_count_weightage', 1);
                            return; // Exit function after successful assignment
                        }
                    }
                }
            }
        }
    }

    protected function ReserveContact($data, $agent = null, $campaign)
    {
        $type           = $data['customData'];
        $contact_id     = $data['contact_id'];
        $my_signature   = json_encode($data['I have reviewed my application information above, and here is my signature.']);
        $reserveContact = ReserveContact::where('email', $data['email'])->first();
        $contactData    = CreateContactData($data, $agent, $campaign, false);
        if ($reserveContact) {
            foreach ($contactData as $key => $value) {
                $reserveContact->$key = $value;
            }
            $reserveContact->status = 'Not Sent';
            $reserveContact->save();
            \Log::info("Updated ReserveContact with contact ID: {$contact_id}");
        } else {
            $reserveContact = new ReserveContact();
            foreach ($contactData as $key => $value) {
                $reserveContact->$key = $value;
            }
            $reserveContact->status = 'Not Sent';
            $reserveContact->save();
            \Log::info("Created new ReserveContact with contact ID: {$contact_id}");
        }
    }

    protected function findAgentOld($proccessContact, $camid = null)
    {
        $state      = $proccessContact->state;
        $contact_id = $proccessContact->contact_id;

        \Log::info('Agent Find for ' . $proccessContact->state . ' and Campaign ' . $camid);

        if (! is_null($state)) {
            $user_id      = null;
            $currentMonth = Carbon::now('America/Chicago')->month;
            $currentDate  = Carbon::now('America/Chicago')->format('Y-m-d');
            $mainCampaign = Campaign::find($camid);
            $agentIds     = CampaignAgent::where('campaign_id', $camid)->pluck('agent_id')->toArray();

            // Find agents matching the state and campaign
            $agents = Agent::whereHas('states', function ($query) use ($state) {
                $query->where(DB::raw('TRIM(LOWER(state))'), $state)
                    ->orWhere(DB::raw('TRIM(LOWER(short_form))'), $state);
            })
                ->whereIn('id', $agentIds)
                ->withCount([
                    'contacts as monthly_contacts_count' => function ($query) use ($currentMonth) {
                        $query->where('status', 'Sent')->whereMonth('created_at', $currentMonth);
                    },
                    'contacts as daily_contacts_count'   => function ($query) use ($currentDate) {
                        $query->where('status', 'Sent')->whereDate('created_at', $currentDate);
                    },
                    'contacts as total_contacts_count'   => function ($query) {
                        $query->where('status', 'Sent');
                    },
                ])
                ->orderBy('priority', 'asc') // Sort by priority first (lower number = higher priority)
                ->orderByDesc('weightage')   // Within same priority, sort by weightage
                ->get();
            if ($agents->isEmpty()) {
                \Log::info('No matching agents found, sending to reserve.');
                // $this->ReserveContact($proccessContact, null, $mainCampaign);
                return;
            }
            $filteredAgents = [];
            $weightagefull  = true;

            foreach ($agents as $agent) {
                $total   = $agent->total_contacts_count < $agent->total_limit;
                $monthly = $agent->monthly_contacts_count < $agent->monthly_limit;
                $daily   = $agent->daily_contacts_count < $agent->daily_limit;

                if ($total && $monthly && $daily) {
                    $filteredAgents[] = $agent;
                    if ($agent->agent_count_weightage < $agent->weightage) {
                        $proccessContact->agent_id = $agent->id;
                        $proccessContact->save();
                        $agent->increment('agent_count_weightage', 1);
                        $weightagefull = false;
                        break;
                    }
                }
            }

            \Log::info(["filteragents" => json_encode($filteredAgents)]);

            // Step 4: If all agents are at max weightage, reset weightage & continue
            if ($weightagefull && count($filteredAgents) > 0) {
                $agentsCollection = collect($filteredAgents);

                // Reset agent_count_weightage for all filtered agents
                $agentsCollection->each(function ($agent) {
                    $agent->agent_count_weightage = 0;
                    $agent->save();
                });

                // Find the highest priority (smallest number means highest priority)
                $minPriority = $agentsCollection->min('priority');

                // Get agents that match the highest priority
                $topAgents = $agentsCollection->where('priority', $minPriority);

                // Pick the agent with the highest weightage among them
                $topAgent = $topAgents->sortByDesc('weightage')->first();

                if ($topAgent) {
                    $proccessContact->agent_id = $topAgent->id;
                    $proccessContact->save();

                    \Log::info('Agent found. Contact assigned. Agent ID: ' . $topAgent->id);

                    // Increment agent weightage count
                    $topAgent->increment('agent_count_weightage', 1);
                }
            }

            // // Step 5: If no matching agents found, send to reserve
            // if (empty($filteredAgents) && !is_null($contact_id)) {
            //     \Log::info('No Agent Found or Limit Reached, Sent to Reserve');
            //     $this->ReserveContact($proccessContact, null, $mainCampaign);
            // }
        }
    }

}
